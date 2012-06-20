<?php
/**
 * class.php: Helper functions
 * Created on May 7, 2012
 *
 * @package ts-krinkle-mwSnapshots
 * @author Timo Tijhof <krinklemail@gmail.com>, 2012
 * @license CC-BY-SA 3.0 Unported: creativecommons.org/licenses/by/3.0/
 */

class KrMwSnapshots extends KrToolBaseClass {

	protected $settingsKeys = array(
		'mediawikiCoreRepoDir',
		'cacheDir',
		'logsDir',
	);

	protected $infoCache;

	public function getInfoCache() {
		if ( $this->infoCache === null ) {
			$infoCacheFile = $this->settings['cacheDir'] . '/snapshotInfo.json';
			if ( !is_readable( $infoCacheFile ) ) {
				$this->infoCache = false;
			} else {
				$infoCache = file_get_contents( $infoCacheFile );
				$this->infoCache = json_decode( $infoCache, /*assoc=*/true );
			}
		}
		return $this->infoCache;
	}

	/** @return bool: True on success */
	public function setInfoCache( $snapshotInfo ) {
		$infoCacheFile = $this->settings['cacheDir'] . '/snapshotInfo.json';
		$snapshotInfo = json_encode( $snapshotInfo );

		// Overwrites existing file
		$put = file_put_contents(
			$infoCacheFile,
			$snapshotInfo,
			LOCK_EX
		);
		return $put !== false;
	}

	public function getUpdateLogContent() {
		$updateLogFilePath = $this->getUpdateLogFilePath();
		if ( !$updateLogFilePath ) {
			return '';
		}
		$updateLogContent = file_get_contents( $updateLogFilePath );
		return $updateLogContent;
	}

	public function getUpdateLogFilePath() {
		$updateLogFilePath = $this->settings['logsDir'] . '/updateSnaphots.log';
		return is_readable( $updateLogFilePath ) ? $updateLogFilePath  : false;
	}

	public function isUpdateLogActive() {
		$updateLogFilePath = $this->getUpdateLogFilePath();
		if ( !$updateLogFilePath ) {
			return false;
		}
		$mtime = filemtime( $updateLogFilePath );
		// If file was changed in the last 5 minutes,
		// it may still be written to by the updator
		return $mtime > strtotime( '1 minute ago' );
	}

	/**
	 * @param int $targetTime: Unix timestamp
	 * @param int $originTime: [optional] Unix timestamp (defaults to now)
	 * @return string: relative time ago, between "just now" and x hours.
	 */
	public function dumpTimeAgo( $targetTime, $originTime = null ) {
		$originTime = $originTime === null ? time() : (int)$originTime;

		// Using round() with PHP_ROUND_HALF_UP so that 61 minutes ago
		// isn't shown as "2 hours ago".

		$diff = $originTime - $targetTime;
		$text = '';
		if ( $diff < 0 ) {
			$text = 'in the future';
		} elseif ( $diff < 60 ) {
			$text = 'just now';
		} elseif ( $diff < 3600 ) {
			$i = round( $diff / 60, 0, PHP_ROUND_HALF_DOWN );
			$text = $i > 1 ? "$i minutes ago" : "1 minute ago";
		} else {
			$i = round( $diff / 3600, 0, PHP_ROUND_HALF_DOWN );
			$text = $i > 1 ? "$i hours ago" : "1 hour ago";
		}

		return $text;
	}

	public function prepareCache() {
		if ( !is_writable( $this->settings['cacheDir'] ) ) {
			return false;
		}
		$snapshotsCache = $this->settings['cacheDir'] . '/snapshots/';
		if ( !file_exists( $snapshotsCache ) && !mkdir( $snapshotsCache, 0755 ) && !is_writable( $snapshotsCache ) ) {
			return false;
		}
		return true;
	}

	public function getDownloadUrl( $repoName, $branchInfo ) {
		global $kgBaseTool;

		return rtrim( $kgBaseTool->remoteBasePath, '/' )
			. '/snapshots/'
			. $repoName
			. '/' . $branchInfo['snapshot']['path'];
	}

	public function hasValidRepoDir() {
		$repoDir = $this->getSetting( 'mediawikiCoreRepoDir' );
		return self::isRepoDirValid( $repoDir );
	}

	public static function isRepoDirValid( $repoDir ) {
		if ( $repoDir ) {
			$repoGitDir = "{$repoDir}/.git";
			if ( is_dir( $repoGitDir ) ) {
				return true;
			}
		}
		return false;
	}
}
