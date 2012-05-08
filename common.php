<?php

// BaseTool & Localization
require_once( __DIR__ . '/../ts-krinkle-basetool/InitTool.php' );
require_once( KR_TSINT_START_INC );

// Class for this tool
require_once( __DIR__ . '/class.php' );
$kgTool = new KrMwSnapshots();

$I18N = new TsIntuition( 'Mwsnapshots' );

$toolConfig = array(
	'displayTitle'     => 'mwSnapshots',
	'remoteBasePath'   => $kgConf->getRemoteBase() . '/mwSnapshots/',
	'revisionId'       => '0.0.2',
	'revisionDate'     => '2012-05-08',
	'I18N'             => $I18N,
	'styles'           => array(
		'main.css',
	),
);

// Local settings
require_once( __DIR__ . '/local.php' );

$kgBaseTool = BaseTool::newFromArray( $toolConfig );
$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'ts-krinkle-mwSnapshots', __DIR__ );
