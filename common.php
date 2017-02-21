<?php

require_once __DIR__ . '/vendor/autoload.php';

// Class for this tool
require_once __DIR__ . '/class.php';
$kgTool = new MwSnapshots();

// Local settings
require_once __DIR__ . '/config.php';

$I18N = new TsIntuition( 'mwsnapshots' );

$kgBase = BaseTool::newFromArray( array(
	'displayTitle' => $I18N->msg( 'title-overview' ),
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '1.0.0',
	'styles' => array(
		'main.css',
	),
	'scripts' => array(
		'main.js',
	),
	'I18N' => $I18N,
) );
$kgBase->setSourceInfoGithub( 'Krinkle', 'mw-tool-snapshots', __DIR__ );
