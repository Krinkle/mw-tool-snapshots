<?php

$kgTool->setSettings(array(
	// This must be a sandbox clone of MediaWiki, do not use your main
	// "dev-wiki" clone because it will wipe out anything that doesn't
	// belong in a fresh checkout.
	'mediawikiCoreRepoDir' => realpath( __DIR__ . '/../mediawiki-core' ),

	'cacheDir' => __DIR__ . '/cache',
	'logsDir' => __DIR__ . '/logs',
));
