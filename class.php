<?php
/**
 * class.php: Helper functions
 * Created on May 7, 2012
 *
 * @package ts-krinkle-mwSnapshots
 * @author Timo Tijhof <krinklemail@gmail.com>, 2012
 * @license CC-BY-SA 3.0 Unported: creativecommons.org/licenses/by/3.0/
 */

class KrToolBaseClass {

	protected $settings = array();

	protected $settingsKeys = array();

	public function setSettings( $settings ) {
		foreach ( $this->settingsKeys as $key ) {
			if ( !array_key_exists( $key, $settings ) ) {
				throw new InvalidArgumentException( "Settings must have key $key." );
			}
		}
		$this->settings = $settings;
	}

	public function getSetting( $key ) {
		return isset( $this->settings[$key] ) ? $this->settings[$key] : null;
	}
}

class KrMwSnapshots extends KrToolBaseClass {

	protected $settingsKeys = array(
		'mediawikiCoreRepoDir',
		'cacheDir',
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
