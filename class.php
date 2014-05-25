<?php
/**
 * Helper functions
 *
 * @author Timo Tijhof, 2012-2014
 * @license http://krinkle.mit-license.org/
 * @package mw-tool-snapshots
 */

class KrSnapshots extends KrToolBaseClass {

	protected $settings = array(
		'buildsPath' => 'builds',
	);

	protected $settingsKeys = array(
		'buildsPath',
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
		// If file was written to recently, the updator may still be running
		return $mtime > strtotime( '30 seconds ago' );
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
			. '/' . $this->settings['buildsPath']
			. '/' . $repoName
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

	public static function getPanelHtml( $type, $content ) {
		global $I18N;
		return Html::rawElement(
			'div',
			array(
				'class' => 'panel panel-' . $type,
			),
			'<div class="panel-heading">'
			. Html::element( 'h3', array( 'class' => 'panel-title' ), $I18N->msg( 'title-error' ) )
			. '</div>'
			. '<div class="panel-body">' . $content . '</div>'
		);
	}
}
