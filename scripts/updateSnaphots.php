<?php
/**
 * updateSnaphots.php: Maintenance script to be ran from the command-line
 * Created on May 7, 2012
 *
 * @package ts-krinkle-mwSnapshots
 * @author Timo Tijhof <krinklemail@gmail.com>, 2012
 * @license CC-BY-SA 3.0 Unported: creativecommons.org/licenses/by/3.0/
 */
require_once( __DIR__ . '/../common.php' );

$snapshotInfo = array();

// Get old index file
$oldSnapshotInfo = $kgTool->getInfoCache();

print "
--
-- " . date( 'r' ) . "
-- Starting update process for snapshots of mediawiki-core...
--
";
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
function kfMwSnapUtil_trimSplitCleanLines( $input ) {
	return array_map( 'trim', explode( "\n", trim( $input ) ) );
}

function kfMwSnapUtil_isGoodBranch( $input ) {
	// Skip stuff like "HEAD -> origin/master"
	return strpos( $input, '->' ) === false;
}

/** @return string: filtered string */
function kfMwSnapUtil_archiveNameSnippetFilter( $input ) {
	return str_replace( array( '/', '\\', '-', '.', ' ' ), '_', $input );
}

function kfMwSnapUtil_gitCleanAndReset() {
	// When checking out a whole bunch of remote branches, creating
	// archives, moving stuff around. The working copy sometimes leaves
	// files behind from old mediawiki versions that fall under gitignore
	// and other crap. Beware that if you run this locally, dont use your
	// main "dev wiki" repo dir for this, because it'll nuke stuff like
	// LocalSettings.php away as well.
	print "Brute force clean up and reset...\n";
	foreach( array(
		"git clean -d -x --force;",
		"git reset --hard HEAD;",
		"git checkout master;",
	) as $cmd ) {
		print "* $cmd\n";
		kfShellExec( $cmd );
	}
	print "\n";
}

/**
 * Update
 * -------------------------------------------------
 */

kfMwSnapUtil_gitCleanAndReset();

print "Pull updates from remote...\n";
kfShellExec( "git pull --all --force" );

// Get remotes (in order to check if there are multiple (which we don't support),
// and so that we can use this name to substract it from the remote branche names.
// e.g. this will probably return "origin" or "gerit".
// So we can remove the "gerrit/" preifx from "gerrit/REL1_19", "gerrit/master" etc.
print "Getting names of remotes...\n";
$remoteRepository = kfShellExec( "git remote" );
$remoteRepository = kfMwSnapUtil_trimSplitCleanLines( $remoteRepository );
if ( count( $remoteRepository )  > 1 ) {
	print "Fatal: This tool does not support working with branches from multiple remotes\n";
	exit;
}
$remoteRepository = $remoteRepository[0];


// Get branches: http://gitready.com/intermediate/2009/02/13/list-remote-branches.html
print "Getting list of remote branches...\n";
$remoteBranchNames = kfShellExec( "git branch -r --color='never'" );
$remoteBranchNames = kfMwSnapUtil_trimSplitCleanLines( $remoteBranchNames );
natsort( $remoteBranchNames );
print "Remote branches: \n\t" . implode( "\n\t", $remoteBranchNames ) . "\n";

###debug: Hard limit the branches to be tested
##$remoteBranchNames = array_slice( $remoteBranchNames, 0, 4 );

/**
 * Loop over branches and create snapshots
 * -------------------------------------------------
 */
foreach ( $remoteBranchNames as $remoteBranchName ) {
	print "\nBranch: {$remoteBranchName}\n";
	if ( !kfMwSnapUtil_isGoodBranch( $remoteBranchName ) ) {
		print "..skipping, not a good branch.\n";
		continue;
	}
	// "gerrit/foobar" or "origin/foobar" -> "foobar"
	$branchName = preg_replace( '/^(' . preg_quote( $remoteRepository . '/', '/' ) . ')/', '', $remoteBranchName );
	print "Normalized: {$branchName}\n";

	print "* Checking out...\n";
	$execOut = kfShellExec( 'git checkout ' . kfEscapeShellArg( $remoteBranchName ) );

	print "* Getting SHA1... \n";
	$headSha1 = trim( kfShellExec( "git rev-parse --verify HEAD" ) );
	if ( !GitInfo::isSHA1( $headSha1 ) ) {
		print "* Could not get SHA1: {$headSha1}\n";
		print "* Skipping branch $remoteBranchName\n";
		continue;
	}

	// Get AuthorDate of latest commit this branch (Author instead of Commit)
	print "* Getting timestamp... \n";
	$headTimestamp = kfShellExec( "git show HEAD --format='%at' -s" );

	$archiveFileName = 'mediawiki-snapshot-'
		. kfMwSnapUtil_archiveNameSnippetFilter( $branchName )
		. '-'
		. kfMwSnapUtil_archiveNameSnippetFilter( substr( $headSha1, 0, 7 ) )
		. '.tar.gz';
	$archiveFilePath = "$archiveDir/$archiveFileName";
	print "* Preparing archive: $archiveFilePath \n";
	if ( file_exists( $archiveFilePath ) ) {
		print "  An archive for this version already exists, no update needed.\n";
	} else {
		print "* Existing archive is outdated, generating new archive...\n";
		$archiveFilePathEscaped = kfEscapeShellArg( $archiveFilePath );
		// Toolserver's git doesn't support --format='tar.gz', using 'tar' and piping to gzip instead
		$execOut = kfShellExec( "git archive HEAD --format='tar' | gzip > {$archiveFilePathEscaped}" );
		if ( file_exists( $archiveFilePath ) ) {
			print "  Done!\n";
		} else {
			$archiveFilePath =  false;
			print "  FAILED!\n";
		}
	}

	$snapshotInfo['mediawiki-core']['branches'][$branchName] = array(
		'headSHA1' => $headSha1,
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

	unset( $execOut, $headSha1, $headTimestamp );
	unset( $archiveFileName, $archiveFilePath, $archiveFilePathEscaped );
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
print "Remove old snapshots of branches that have newer versions now...\n";
if ( !isset( $oldSnapshotInfo['mediawiki-core']['branches'] ) ) {
	print "* ERROR. Previous index file is in invalid format. Content: \n-- START OF FILE\n$oldSnapshotInfo\n-- END OF FILE\n";
} else {
	$oldBranchInfos = $oldSnapshotInfo['mediawiki-core']['branches'];
	$newBranchInfos = $snapshotInfo['mediawiki-core']['branches'];
	foreach ( $oldBranchInfos as $branch => $oldBranchInfo ) {
		print "* $branch:\n\t";
		if ( !isset( $newBranchInfos[$branch] ) || $newBranchInfos[$branch]['snapshot'] == false ) {
			print "NOTICE. New index does not have this branch. Leaving old snapshot {$oldBranchInfo['snapshot']['path']}";
		} else {
			if ( $oldBranchInfo['snapshot'] == false || $oldBranchInfo['snapshot']['path'] === $newBranchInfos[$branch]['snapshot']['path'] ) {
				print "OK. Previous version is the same.";
			} else {
				if ( !file_exists( $archiveDir . '/' . $oldBranchInfo['snapshot']['path'] ) ) {
					print "WARNING. Previous snapshot already deleted.";
				} else {
					$del = unlink( $archiveDir . '/' . $oldBranchInfo['snapshot']['path'] );
					if ( $del === false ) {
						print "ERROR! Could not remove old snapshot at {$oldBranchInfo['snapshot']['path']}";
					} else {
						print "UPDATED. Removed previous snapshot at {$oldBranchInfo['snapshot']['path']}";
					}
				}
			}
		}
		print "\n";
	}
}

print "\n";

// Clean up afterwards as well,
// leaving behind a fresh master
kfMwSnapUtil_gitCleanAndReset();

print "
--
-- " . date( 'r' ) . "
-- Done updating snapshots for mediawiki-core!
-- Took: " . number_format( time() - $snapshotInfo['mediawiki-core']['_updateStart'] ) . " seconds
--

";
