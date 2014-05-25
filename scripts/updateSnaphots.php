<?php
/**
 * Script to generate snapshots (run from the command-line)
 *
 * @author Timo Tijhof, 2012-2014
 * @license http://krinkle.mit-license.org/
 * @package mw-tool-snapshots
 */
require_once __DIR__ . '/../common.php';

$snapshotInfo = array();

// Get old index file
$oldSnapshotInfo = $kgTool->getInfoCache();

print '
--
-- ' . date( 'r' ) . '
-- Starting update process for snapshots of mediawiki-core...
--
';
$snapshotInfo['mediawiki-core'] = array(
	'_updateStart' => time(),
	'_updateEnd' => time(),
	'branches' => array(),
	'tags' => array(),
);

/**
 * Set up
 * -------------------------------------------------
 */
// Verify mediawikiCoreRepoDir
if ( !$kgTool->hasValidRepoDir() ) {
	print "Fatal: Not a valid repo dir.\n";
	exit;
}

// Prepare cache
if ( !$kgTool->prepareCache() ) {
	print "Fatal: Cannot write to cache dir.\n";
	exit;
}

$archiveDir = $kgTool->getSetting( 'cacheDir' ) . '/snapshots/mediawiki-core';
if ( !file_exists( $archiveDir ) && !mkdir( $archiveDir, 0755 ) && !is_writable( $archiveDir ) ) {
	print "Fatal: Can't write to snapshots directory: $archiveDir\n";
	exit;
}

// Browser to the repository dir
chdir( $kgTool->getSetting( 'mediawikiCoreRepoDir' ) );

/**
 * Removes any trailing and leading whitespace (even multiple lines).
 * Then splits everythign by line and trims those.
 * @param string $input
 * @return array
 */
function kfSnapshotsUtil_trimSplitCleanLines( $input ) {
	return array_map( 'trim', explode( "\n", trim( $input ) ) );
}

function kfSnapshotsUtil_isGoodBranch( $input ) {
	if (
		// Skip stuff like "HEAD -> origin/master"
		strpos( $input, '->' ) === false

			// Skip the <remote>/sandbox/<user>/<topic> branches
			&& strpos( $input, '/sandbox/' ) === false

			// Only keep master, REL* and wmf*
			&& ( strpos( $input, '/REL' ) !== false
				|| strpos( $input, '/wmf' ) !== false
				|| strpos( $input, '/master' ) !== false
			)
	) {
		return true;
	}

	return false;
}

/** @return string: filtered string */
function kfSnapshotsUtil_archiveNameSnippetFilter( $input ) {
	return str_replace( array( '/', '\\', '-', '.', ' ' ), '_', $input );
}

/**
 * Update
 * -------------------------------------------------
 */

print "Clean worktree...\n";
print kfGitCleanReset( array( 'unlock' => true ) );


// Get remotes (in order to check if there are multiple (which we don't support),
// and so that we can use this name to substract it from the remote branche names.
// e.g. this will probably return "origin" or "gerit".
// So we can remove the "gerrit/" preifx from "gerrit/REL1_19", "gerrit/master" etc.
print "Getting names of remotes...\n";
$remoteRepository = 'origin';
$remoteRepositories = kfSnapshotsUtil_trimSplitCleanLines( kfShellExec( "git remote" ) );
if ( !in_array( $remoteRepository, $remoteRepositories ) && count( $remoteRepositories ) > 1 ) {
	print "Fatal: This tool requires there be a remote called '{$remoteRepository}'.\n";
	exit;
}
if ( count( $remoteRepositories ) > 1 ) {
	// Because of "git branch -r"
	print "Fatal: This tool does not support multiple remotes..\n";
	exit;
}

print "Fetch updates from remote...\n";

kfShellExec( 'git fetch ' . kfEscapeShellArg( $remoteRepository ) );
kfShellExec( 'git fetch --prune' );


// Get branches: http://gitready.com/intermediate/2009/02/13/list-remote-branches.html
print "Getting list of remote branches...\n";
$remoteBranchNames = kfShellExec( "git branch -r --color='never'" );
$remoteBranchNames = kfSnapshotsUtil_trimSplitCleanLines( $remoteBranchNames );
natsort( $remoteBranchNames );
print "Remote branches: \n\t" . implode( "\n\t", $remoteBranchNames ) . "\n";

###debug: Hard limit the branches to be tested
##$remoteBranchNames = array_slice( $remoteBranchNames, 0, 4 );

/**
 * Loop over branches and create snapshots
 * -------------------------------------------------
 */
foreach ( $remoteBranchNames as $remoteBranchName ) {

	print "\n== Branch: {$remoteBranchName} ==\n\n";
	if ( !kfSnapshotsUtil_isGoodBranch( $remoteBranchName ) ) {
		print "..skipping, not a good branch name.\n";
		continue;
	}
	// "gerrit/foobar" or "origin/foobar" -> "foobar"
	$branchName = preg_replace( '/^(' . preg_quote( $remoteRepository . '/', '/' ) . ')/', '', $remoteBranchName );
	print "* Normalized: {$branchName}\n";

	$oldBranchInfo = isset( $oldSnapshotInfo['mediawiki-core']['branches'][$branchName] )
		? $oldSnapshotInfo['mediawiki-core']['branches'][$branchName]
		: false;

	// Defaults, these are the value used if the snapshot creation failed for some reason.
	// If this is a new branch, default to false. otherwise use the previous run info
	// (which is either false or an array depending on what the previous run did).
	if ( $oldBranchInfo ) {
		$snapshotInfo['mediawiki-core']['branches'][$branchName] = $oldBranchInfo;
	}

	$branchHead = trim( kfShellExec( 'git rev-parse --verify ' . kfEscapeShellArg( $remoteBranchName ) ) );
	print "* Branch head: $branchHead\n";

	$archiveFileName = 'mediawiki-snapshot-'
		. kfSnapshotsUtil_archiveNameSnippetFilter( $branchName )
		. '-'
		. kfSnapshotsUtil_archiveNameSnippetFilter( substr( $branchHead, 0, 7 ) )
		. '.tar.gz';
	$archiveFilePath = "$archiveDir/$archiveFileName";

	print "* Target file name: $archiveFileName\n";
	if ( file_exists( $archiveFilePath ) ) {
		print "> Already in cache, no update needed.\n";
		continue;
	}
	print "Preparing to create archive...";

	// When checking out a whole bunch of remote branches, creating
	// archives, moving stuff around. The working copy will leave build artificants
	// behind. It will also contain lots of files that will be considered "untracked"
	// according to a different branch pointer.
	// Beware that if you run this locally, don't use your main wiki as this also
	// removes things like "LocalSettings.php".
	print "Clean workspace and checkout $remoteBranchName...\n";
	$execOut = kfGitCleanReset( array( 'checkout' => $remoteBranchName ) );

	// Get revision of this branch. Used so we can check that the checkout worked
	// (in the past the script failed once due to a .git/index.lock error, in which case
	// the checkout command was aborted, and all the archives were for the same revision).
	// This will not happen again because we're now verifying that the remote branch head
	// matches the HEAD of the working copy after the checkout.
	print "Verifiying checkout succeeded...\n";
	$currHead = trim( kfShellExec( "git rev-parse --verify HEAD" ) );
	if ( !GitInfo::isSHA1( $currHead ) ) {
		print "> rev-parse failed for HEAD: {$currHead}\n";
		print "> Skipping $remoteBranchName...\n";
		continue;
	}

	if ( $branchHead !== $currHead ) {
		// Checkout likely failed
		print "> ERROR: Current HEAD does not match remote branch pointer. Skipping...\n";
		continue;
	}

	// Get AuthorDate of latest commit this branch (Author instead of Commit)
	$headTimestamp = kfShellExec( "git show HEAD --format='%at' -s" );

	print "Generating new archive...\n";
	$archiveFilePathEscaped = kfEscapeShellArg( $archiveFilePath );
	// Toolserver's git doesn't support --format='tar.gz', using 'tar' and piping to gzip instead
	$execOut = kfShellExec( "git archive HEAD --format='tar' | gzip > {$archiveFilePathEscaped}" );
	if ( file_exists( $archiveFilePath ) ) {
		print "> Done!\n";
	} else {
		$archiveFilePath =  false;
		print "> FAILED!\n";
	}

	if ( $branchName === 'master' ) {
		print "There is a new archive of the master branch, update '-latest' symlink...\n";
		$masterSymlinkName = 'mediawiki-latest.tar.gz';
		$masterSymlinkPath = "$archiveDir/$masterSymlinkName";
		if ( file_exists( $masterSymlinkPath ) ) {
			if ( !unlink( $masterSymlinkPath ) ) {
				print "> ERROR: Could not remove old symlink";
			}
		}
		if ( link( /* target = */ $archiveFilePath, /* link = */ $masterSymlinkPath ) ) {
			print "> Done\n";
		} else {
			print "> ERROR: Failed to create symlink\n";
		}
	}

	$snapshotInfo['mediawiki-core']['branches'][$branchName] = array(
		'headSHA1' => $branchHead,
		'headTimestamp' => intval( $headTimestamp ),
		'snapshot' => !$archiveFilePath
			? false
			: array(
				'path' => basename( $archiveFilePath ) ,
				'hashSHA1' => sha1_file( $archiveFilePath ),
				'hashMD5' => md5_file( $archiveFilePath ),
				'byteSize' => filesize( $archiveFilePath ),
			),
	);

	unset(
		$branchName,
		$oldBranchInfo,
		$branchHead,
		$archiveFileName,
		$archiveFilePath,
		$execOut,
		$currHead,
		$headTimestamp,
		$archiveFilePathEscaped
	);
}

print "\n";

/**
 * Save index and delete outdated snapshots
 * -------------------------------------------------
 */

print "Writing new info to cache file...\n";
$snapshotInfo['mediawiki-core']['_updateEnd'] = time();
$kgTool->setInfoCache( $snapshotInfo );

print "\n";

// Loop through and if there was an update, nuke the old snapshot
print "Remove unused snapshots...\n";
if ( !isset( $oldSnapshotInfo['mediawiki-core']['branches'] ) ) {
	print "> ERROR. Previous index file is in invalid format. Content: \n-- START OF FILE\n$oldSnapshotInfo\n-- END OF FILE\n";
} else {
	$oldBranchInfos = $oldSnapshotInfo['mediawiki-core']['branches'];
	$newBranchInfos = $snapshotInfo['mediawiki-core']['branches'];
	foreach ( $oldBranchInfos as $branch => $oldBranchInfo ) {
		print "* $branch:\n";
		if ( !isset( $newBranchInfos[$branch] ) || $newBranchInfos[$branch]['snapshot'] == false ) {
			print "  > DELETE. New index does not have this branch. Removing old snapshot {$oldBranchInfo['snapshot']['path']}\n";
		} elseif ( $oldBranchInfo['snapshot'] == false || $oldBranchInfo['snapshot']['path'] === $newBranchInfos[$branch]['snapshot']['path'] ) {
			// New branch or old version was the same. Nothing to remove.
			continue;
		} else {
			print "  > UPDATE. Remove old snapshot {$oldBranchInfo['snapshot']['path']}\n";
		}
		if ( !file_exists( $archiveDir . '/' . $oldBranchInfo['snapshot']['path'] ) ) {
			print "  > > WARNING. Old snapshot was already deleted.\n";
		} else {
			$del = unlink( $archiveDir . '/' . $oldBranchInfo['snapshot']['path'] );
			if ( $del === false ) {
				print "  > > ERROR! Failed to delete {$oldBranchInfo['snapshot']['path']}\n";
			}
		}
	}

	print "\n";
}

print "\n";

// Clean up afterwards as well, leaving behind a fresh master
print "Clean worktree...\n";
print kfGitCleanReset( array( 'unlock' => true , 'checkout' => "{$remoteRepository}/master" ) );

print "
--
-- " . date( 'r' ) . "
-- Done updating snapshots for mediawiki-core!
-- Took: " . number_format( time() - $snapshotInfo['mediawiki-core']['_updateStart'] ) . " seconds
--

";
