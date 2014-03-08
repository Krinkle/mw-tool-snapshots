<?php

// BaseTool & Localization
require_once( __DIR__ . '/lib/basetool/InitTool.php' );
require_once( KR_TSINT_START_INC );

// Class for this tool
require_once( __DIR__ . '/class.php' );
$kgTool = new KrSnapshots();

$I18N = new TsIntuition( 'Mwsnapshots' );

$toolConfig = array(
	'displayTitle' => 'Snapshots',
	'krinklePrefix' => false,
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.1.2',
	'styles' => array(
		'main.css',
	),
	'scripts' => array(
		'main.js',
	),
);

// Local settings
require_once( __DIR__ . '/local.php' );

$kgBaseTool = BaseTool::newFromArray( $toolConfig );
$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'mw-tool-snapshots', __DIR__ );
