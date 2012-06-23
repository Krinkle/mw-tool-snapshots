<?php

$kgTool->setSettings(array(
	// This must be a sandbox clone of MediaWiki, do not use your main
	// "dev-wiki" clone because it will wipe out anything that doesn't
	// belong in a fresh checkout.
	// Also make sure you don't use this MediaWiki clone for other
	// scripts (e.g. a code searcher or analyzer) because it will be
	// swapping HEAD and active branch back and forth between very
	// old versions of MediaWiki, new versions and even potentially
	// unstable deveopment branches.
	'mediawikiCoreRepoDir' => '/mnt/user-store/krinkle/mwSnapshots/remotes/mediawiki-core',

	// This (among other temporary files) where the tarballs will be stored
	'cacheDir' => '/mnt/user-store/krinkle/mwSnapshots/cache',

	// Log files will be written here. Only the log of the last run is kept.
	'logsDir' => __DIR__ . '/logs',
));
