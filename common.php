<?php

// BaseTool & Localization
require_once __DIR__ . '/lib/basetool/InitTool.php';
require_once KR_TSINT_START_INC;

// Class for this tool
require_once __DIR__ . '/class.php';
$kgTool = new KrSnapshots();

// Local settings
require_once __DIR__ . '/local.php';

$I18N = new TsIntuition( 'mwsnapshots' );

$toolConfig = array(
	'displayTitle' => $I18N->msg( 'title-overview' ),
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.1.2',
	'styles' => array(
		'main.css',
	),
	'scripts' => array(
		'main.js',
	),
	'I18N' => $I18N,
);

$kgBaseTool = BaseTool::newFromArray( $toolConfig );
$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'mw-tool-snapshots', __DIR__ );
