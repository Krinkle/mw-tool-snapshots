<?php
/**
 * Web server entry point
 *
 * @author Timo Tijhof
 * @license https://krinkle.mit-license.org/
 * @package mw-tool-snapshots
 */

require_once __DIR__ . '/../common.php';

$repoInfos = array(
	'mediawiki-core' => array(
		'display-title' => 'MediaWiki core',
		'img' => '//upload.wikimedia.org/wikipedia/mediawiki/b/bc/Wiki.png',
		'site-url' => '//www.mediawiki.org/',
		'repo-browse-url' => 'https://github.com/wikimedia/mediawiki',
		'repo-branch-url' => 'https://github.com/wikimedia/mediawiki/tree/$1',
		'repo-commit-url' => 'https://github.com/wikimedia/mediawiki/commit/$1',
	),
);

$snapshotInfo = $kgTool->getInfoCache();

$kgBase->addOut( '<div class="container">' );
$pageHtml = '';

/**
 * Output (snapshot index check)
 * -------------------------------------------------
 */
if ( !$snapshotInfo ) {
	$pageHtml .= kfAlertHtml( 'danger', $I18N->msg( 'err-snapshotindex' ) );

/**
 * Output (show update log)
 * -------------------------------------------------
 */
} elseif ( $kgReq->getVal( 'action' ) === 'updatelog' ) {

	$kgBase->setHeadTitle( $I18N->msg( 'title-updatelog' ) );
	$kgBase->setLayout( 'header', array(
		'titleText' => $I18N->msg( 'title-updatelog' )
	) );

	$pageHtml .= Html::element( 'p', array( 'class' => 'lead' ), $I18N->msg( 'updatelog-intro' ) );

	if ( !$kgTool->getUpdateLogFilePath() ) {
		// Error: No update log
		$pageHtml .= kfAlertHtml( 'danger', $I18N->msg( 'err-noupdatelog' ) );
	} else {
		$notice = $kgTool->isUpdateLogActive()
			? kfAlertHtml( 'warning', $I18N->msg( 'updatelog-active' ) )
			: '';

		$pageHtml .= $notice
			. Html::element( 'pre', array(),
				$kgTool->getUpdateLogContent()
			)
			. $notice;
	}

/**
 * Output (get snapshot)
 * -------------------------------------------------
 */
} elseif (  $kgReq->wasPosted() && $kgReq->getVal( 'action' ) === 'getSnapshot' ) {

	$title = $I18N->msg( 'title-downloadpage' );
	$kgBase->setHeadTitle( $title );
	$kgBase->setLayout( 'header', array( 'titleText' => $title ) );

	$repoName = $kgReq->getVal( 'repo' );
	$branchName = $kgReq->getVal( 'branch' );
	$isAjax = $kgReq->hasKey( 'ajax' );
	$downloadUrl = null;

	if ( !isset( $repoInfos[$repoName] ) || !isset( $snapshotInfo[$repoName] ) ) {
		// Error: Invalid repo
		$pageHtml .= MwSnapshots::getPanelHtml( 'warning', $I18N->msg( 'err-invalid-repo', array(
			'variables' => array( $repoName )
		) ) );
	} else {
		$repoInfo = $repoInfos[$repoName];
		$data = $snapshotInfo[$repoName];

		// Error: Invalid branch
		if ( !isset( $data['branches'][$branchName] ) ) {
		$pageHtml .= MwSnapshots::getPanelHtml( 'warning', $I18N->msg( 'err-invalid-branch', array(
			'variables' => array( $branchName, $repoName )
		) ) );

		// Error: Snapshot unavaiable
		} elseif ( $data['branches'][$branchName]['snapshot'] == false ) {
			$pageHtml .= MwSnapshots::getPanelHtml( 'warning', $I18N->msg( 'err-nosnapshot', array(
				'variables' => array( $branchName )
			) ) );

		// Downloading snapshot
		} else {
			$branchInfo = $data['branches'][$branchName];

			$downloadUrl = $kgTool->getDownloadUrl( $repoName, $branchInfo );
			$title = $I18N->msg( 'title-downloadpage-repo', array(
				'variables' => array( $repoInfo['display-title'] )
			) );
			$kgBase->setHeadTitle( $title );
			$kgBase->setLayout( 'header', array( 'titleText' => $title ) );

			if ( $isAjax ) {
				$pageHtml .= Html::element( 'h2', array(), $title );
			}

			$pageHtml .=
				'<div class="row">'
				. '<div class="col-md-7">'
				. '<table class="table table-bordered table-condensed snapshots-download-box"><tbody>'
				. '<tr><th>'
				. htmlspecialchars( $I18N->msg( 'tablehead-repo' ) )
				. '</th><td>'
					. Html::element( 'a', array(
							'href' => $repoInfo['site-url'],
							'target' => '_blank',
						),
						$repoInfo['display-title']
					)
				. '</td></tr>'
				. '<tr><th>'
				. htmlspecialchars( $I18N->msg( 'tablehead-branch' ) )
				. '</th><td>'
					. Html::element( 'a', array(
							'href' => str_replace( '$1', $branchName, $repoInfo['repo-branch-url'] ),
							'target' => '_blank',
						),
						$branchName
					)
				. '</td></tr>'
				. '<tr><th dir="ltr" lang="en">'
				. 'HEAD'
				. '</th><td>'
					. Html::element( 'a', array(
							'href' => str_replace( '$1', $branchInfo['headSHA1'], $repoInfo['repo-commit-url'] ),
							'target' => '_blank',
							'dir' => 'ltr',
							'lang' => 'en',
						),
						$branchInfo['headSHA1']
					)
				.	'<br>'
					. htmlspecialchars( $I18N->msg( 'repo-lastmoddate-label' ) )
					. ' '
					. htmlspecialchars( gmdate( 'r', $branchInfo['headTimestamp'] ) )
				. '</td></tr>'
				. '<tr><th>'
				. htmlspecialchars( $I18N->msg( 'tablehead-filesize' ) )
				. '</th><td>'
					. kfFormatBytes( $branchInfo['snapshot']['byteSize'] )
					. ' '
					. $I18N->msg( 'parentheses', array(
						'domain' => 'general',
						'variables' => array(
							$branchInfo['snapshot']['byteSize'] . ' bytes'
						)
					))
				. '</td></tr>'
				. '<tr><th>'
				. htmlspecialchars( $I18N->msg( 'tablehead-hash' ) )
				. '</th><td dir="ltr" lang="en">'
					. 'MD5: ' . htmlspecialchars( $branchInfo['snapshot']['hashMD5'] )
					. '<br>SHA1: ' . htmlspecialchars( $branchInfo['snapshot']['hashSHA1'] )
				. '</td></tr>'
			. '</tbody></table>'
			. '</div>'
			. '<div class="col-md-5 text-center">'
			. '<div class="snapshots-download-badge">'
			. Html::rawElement( 'a', array(
					'class' => 'btn btn-primary btn-lg',
					'href' => $downloadUrl,
				), '<span class="glyphicon glyphicon-download-alt"></span> '
				. $I18N->msg( 'download-button', array( 'escape' => 'html' ) )
			);

			if ( !$isAjax ) {
				$pageHtml .= '<script>'
				. 'setTimeout(function () {'
				. 'location.href = ' . json_encode( $downloadUrl ) . ';'
				. '}, 1000);'
				. '</script>';
			}

			// End of badge and column
			$pageHtml .= '</div></div>';
		}
	}

	// End of row
	$pageHtml .= '</div>';

	if ( $isAjax ) {
		kfApiExport(
			array(
				'pageHtml' => $pageHtml,
				'downloadUrl' => $downloadUrl,
			),
			'json' );
		exit;
	}

/**
 * Output (overview)
 * -------------------------------------------------
 */
} else {

	$kgBase->setLayout( 'header', array(
		'titleText' => $I18N->msg( 'title-overview' )
	) );

	$pageHtml .= '<table class="table table-bordered snapshots-repos-box"><thead><tr><th colspan="2">'
		. htmlspecialchars( $I18N->msg( 'tablehead-repo' ) )
		. '</th><th>'
		. htmlspecialchars( $I18N->msg( 'tablehead-snapshots' ) )
		. '</th></tr></thead><tbody>';

	$updatelogLink = '';
	if ( $kgTool->getUpdateLogFilePath() ) {
		$updatelogLink = ' ' . $I18N->parensWrap(
			Html::element( 'a', array(
					'href' => $kgBase->remoteBasePath . '?action=updatelog',
				),
				$I18N->msg( 'updatelog-link' )
			)
		);
	}

	foreach ( $snapshotInfo as $repoName => $data ) {
	if ( isset( $repoInfos[$repoName] ) ) {
		$repo = $repoInfos[$repoName];
		$branchesSelect = Html::openElement( 'select', array(
			'name' => 'branch',
			'class' => 'snapshots-branches form-control',
			'data-repo-name' => $repoName,
			'id' => 'snapshots-branches-' . $repoName,
		));
		foreach ( $data['branches'] as $branch => $branchInfo ) {
			$branchesSelect .= Html::element( 'option', array(
					'value' => $branch,
					'selected' => $branch === 'master',
					'disabled' => $branchInfo['snapshot'] === false
				),
				$branch
			);
		}
		$branchesSelect .= '</select>';
		$pageHtml .=
			'<tr><td class="snapshots-repo-logo">'
				. ( isset( $repo['img'] )
					? Html::element( 'img', array(
						'src' => $repo['img'],
						'width' => '135',
						'height' => '135'
					))
					: ''
				)
			. '</td><td class="snapshots-repo-title">'
				. '<p><strong>'
				. Html::element( 'a', array(
						'href' => $repo['site-url'],
						'target' => '_blank',
					),
					$repo['display-title']
				)
				. '</strong></p>'
				. '<ul>'
					. '<li>' . Html::element( 'a', array(
							'href' => $repo['site-url'],
							'target' => '_blank',
						),
						$I18N->msg( 'repo-site-link' )
					)
					. '</li><li>' . Html::element( 'a', array(
							'href' => $repo['repo-browse-url'],
							'target' => '_blank',
						),
						$I18N->msg( 'repo-browse-link' )
					)
					. '</li>'
				. '</ul>'
			. '</td><td>'
				. Html::openElement( 'form', array(
					'action' => $kgBase->remoteBasePath,
					'method' => 'post',
					'class' => 'form-horizontal',
				))
				. Html::element( 'input', array(
					'type' => 'hidden',
					'name' => 'action',
					'value' => 'getSnapshot'
				))
				. Html::element( 'input', array(
					'type' => 'hidden',
					'name' => 'repo',
					'value' => $repoName
				))
				. '<div class="form-group">'
				. Html::element( 'label', array(
						'class' => 'col-sm-2 control-label',
						'for' => 'snapshots-branches-' . $repoName
					),
					$I18N->msg( 'repo-branches-label' )
				)
				. '<div class="col-sm-10">'
				. $branchesSelect
				. '</div>'
				. '</div>'
				. '<div class="form-group">'
				. '<div class="col-sm-offset-2 col-sm-10">'
				. Html::element( 'input', array(
					'type' => 'submit',
					'class' => 'btn btn-primary',
					'value' => $I18N->msg( 'branches-submit-button' )
				))
				. '</div>'
				. '</div>'
				. '</form>'
				. '<p class="small text-muted text-right">Last dump: '
					. Html::element( 'time', array(
							'itemprop' => 'published',
							'datetime' => gmdate( 'Y-m-d\TH:i:s\Z', $data['_updateEnd'] ),
							'title' => gmdate( 'Y-m-d H:i:s', $data['_updateEnd'] ) . ' (UTC)',
						),
						$kgTool->dumpTimeAgo( $data['_updateEnd'] )
					)
					. $updatelogLink
				. '</p>'
			. '</td>'
			. '</tr>';

	} else {
		$pageHtml .= '<!-- unknown repository: ' . htmlspecialchars( $repoName ) . ' -->';
	}
	}
	$pageHtml .= '</tbody></table>';
	$pageHtml .= '<div id="snapshots-ajax"></div>';
}

$kgBase->addOut( $pageHtml );

// Close container
$kgBase->addOut( '</div>' );

/**
 * Close up
 * -------------------------------------------------
 */
$kgBase->flushMainOutput();
