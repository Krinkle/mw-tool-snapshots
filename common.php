<?php

// BaseTool & Localization
require_once( __DIR__ . '/lib/basetool/InitTool.php' );
require_once( KR_TSINT_START_INC );

// Class for this tool
require_once( __DIR__ . '/class.php' );
$kgTool = new KrMwSnapshots();

$I18N = new TsIntuition( 'Mwsnapshots' );

$toolConfig = array(
	'displayTitle' => 'mwSnapshots',
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
$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'ts-krinkle-mwSnapshots', __DIR__ );
