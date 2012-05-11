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
	'mediawikiCoreRepoDir' => __DIR__ . '/remotes/mediawiki-core',

	'cacheDir' => __DIR__ . '/cache',
	'logsDir' => __DIR__ . '/logs',
));
