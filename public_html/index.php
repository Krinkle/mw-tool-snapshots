<?php
/**
 * Web server entry point
 *
 * @package mw-tool-snapshots
 * @license http://krinkle.mit-license.org/
 * @author Timo Tijhof, 2012-2014
 */

/**
 * Configuration
 * -------------------------------------------------
 */
require_once( __DIR__ . '/../common.php' );

$kgBaseTool->doHtmlHead();
$kgBaseTool->doStartBodyWrapper();

$repoInfos = array(
	'mediawiki-core' => array(
		'display-title' => 'MediaWiki core',
		'img' => '//upload.wikimedia.org/wikipedia/mediawiki/b/bc/Wiki.png',
		'site-url' => '//www.mediawiki.org/',
		'repo-browse-url' => 'https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/core.git;a=tree',
		'repo-branch-url' => 'https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/core.git;a=shortlog;h=refs/heads/$1',
		'repo-commit-url' => 'https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/core.git;a=commit;h=$1',
	),
);

$snapshotInfo = $kgTool->getInfoCache();

$pageHtml = '';

/**
 * Output (snapshot index check)
 * -------------------------------------------------
 */
if ( !$snapshotInfo ) {
	$pageHtml .= Html::element( 'h2', array(), $I18N->msg( 'title-overview' ) );
	$pageHtml .= kfMsgBlock( $I18N->msg( 'err-snapshotindex' ), 'warning error' );

/**
 * Output (show update log)
 * -------------------------------------------------
 */
} elseif ( $kgReq->getVal( 'action' ) === 'updatelog' ) {

	$pageHtml .= Html::element( 'h2', array(), $I18N->msg( 'title-updatelog' ) );
	$pageHtml .= Html::element( 'p', array(), $I18N->msg( 'updatelog-intro' ) );

	if ( !$kgTool->getUpdateLogFilePath() ) {
		// Error: No update log
		$pageHtml .= kfMsgBlock( $I18N->msg( 'err-noupdatelog' ), 'warning' );
	} else {
		$notice = $kgTool->isUpdateLogActive()
			? kfMsgBlock( $I18N->msg( 'updatelog-active' ), 'warning' )
			: '';

		$pageHtml .= '<hr/>'
			. $notice
			. Html::element( 'pre', array(),
				$kgTool->getUpdateLogContent()
			)
			. $notice;
	}

/**
 * Output (get snapshot)
 * -------------------------------------------------
 */
} elseif ( $kgReq->wasPosted() && $kgReq->getVal( 'action' ) === 'getSnapshot' ) {

	$repoName = $kgReq->getVal( 'repo' );
	$branchName = $kgReq->getVal( 'branch' );
	$isAjax = $kgReq->getBool( 'ajax' );
	$downloadUrl = null;

	if ( !isset( $repoInfos[$repoName] ) || !isset( $snapshotInfo[$repoName] ) ) {
		// Error: Invalid repo
		$pageHtml .= Html::element( 'h2', array(), $I18N->msg( 'title-error' ) );
		$pageHtml .= kfMsgBlock( $I18N->msg( 'err-invalid-repo', array(
				'variables' => array( $repoName )
		) ), 'warning' );
	} else {
		$repoInfo = $repoInfos[$repoName];
		$data = $snapshotInfo[$repoName];
		if ( !isset( $data['branches'][$branchName] ) ) {
			// Error: Invalid branch
			$pageHtml .= Html::element( 'h2', array(), $I18N->msg( 'title-error' ) );
			$pageHtml .= kfMsgBlock( $I18N->msg( 'err-invalid-branch', array(
				'variables' => array( $branchName, $repoName )
			) ), 'warning' );
		} elseif ( $data['branches'][$branchName]['snapshot'] == false ) {
			// Error: Snapshot unavaiable
			$pageHtml .= Html::element( 'h2', array(), $I18N->msg( 'title-error' ) );
			$pageHtml .= kfMsgBlock( $I18N->msg( 'err-nosnapshot', array(
				'variables' => array( $branchName )
			) ), 'warning' );
		} else {
			$branchInfo = $data['branches'][$branchName];
			// Downoading snapshot

			$downloadUrl = $kgTool->getDownloadUrl( $repoName, $branchInfo );

			if ( !$isAjax ) {
				$pageHtml .= Html::element( 'h2', array(),
					$I18N->msg( 'title-downloadpage', array(
						'variables' => array( $branchInfo['snapshot']['path'] )
					) )
				)
				. '<p>'
				. Html::element( 'a', array( 'href' => $downloadUrl ), $I18N->msg( 'downloadpage-directlink' ) )
				. '</p>';
			} else {
				$pageHtml .= Html::element( 'h2', array(),
					$I18N->msg( 'title-download', array(
						'variables' => array( $branchInfo['snapshot']['path'] )
					) )
				);
			}

			$pageHtml .=
				'<table class="wikitable krinkle-snapshots-download-box"><tbody>'
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
			. '</tbody></table>';

			if ( $isAjax ) {
				$pageHtml = '<div class="krinkle-snapshots-download-badge">'
				. '<button>&darr;<br/>' . $I18N->msg( 'download-button', array(
					'variables' => array( $branchName ),
					'escape' => 'html'
				) )
				. '</button><br/>'
				. $I18N->parensWrap(
					Html::element( 'a', array(
							'href'=> $downloadUrl,
						),
						$I18N->msg( 'download-directlink' )
					)
				)
				. '</div>'
				. $pageHtml;
			} else {
				$pageHtml .= '<script>'
				. 'setTimeout(function () {'
				. 'var downloadUrl = ' . json_encode( $downloadUrl ) . ';'
				. 'window.location.href = downloadUrl;'
				. '}, 1000);'
				. '</script>';
			}

		}
	}

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
	$pageHtml .= Html::element( 'h2', array(), $I18N->msg( 'title-overview' ) );

	$pageHtml .= '<table class="wikitable krinkle-snapshots-repos-box"><thead><tr><th colspan="2">'
		. htmlspecialchars( $I18N->msg( 'tablehead-repo' ) )
		. '</th><th>'
		. htmlspecialchars( $I18N->msg( 'tablehead-snapshots' ) )
		. '</th></tr></thead><tbody>';

	$updatelogLink = '';
	if ( $kgTool->getUpdateLogFilePath() ) {
		$updatelogLink = ' ' . $I18N->parensWrap(
			Html::element( 'a', array(
					'href' => $kgBaseTool->remoteBasePath . '?action=updatelog',
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
			'class' => 'krinkle-snapshots-branches',
			'data-repo-name' => $repoName,
			'id' => 'krinkle-snapshots-branches-' . $repoName,
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
			'<tr><td class="krinkle-snapshots-repo-logo">'
				. ( isset( $repo['img'] )
					? Html::element( 'img', array(
						'src' => $repo['img'],
						'width' => '135',
						'height' => '135'
					))
					: ''
				)
			. '</td><td class="krinkle-snapshots-repo-title">'
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
					'action' => $kgBaseTool->remoteBasePath,
					'method' => 'post',
					'class' => 'krinkle-snapshots-repo-select',
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
				. Html::element( 'label', array(
						'for' => 'krinkle-snapshots-branches-' . $repoName
					),
					$I18N->msg( 'repo-branches-label' )
				)
				. '&nbsp;'
				. $branchesSelect
				. '<div class="krinkle-snapshots-repo-select-submit">'
				. Html::element( 'input', array(
					'type' => 'submit',
					'nof' => true,
					'value' => $I18N->msg( 'branches-submit-button' )
				))
				. '</div>'
				. '</form>'
				. '<p class="krinkle-snapshots-repo-dumpdate">Last dump: '
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
	$pageHtml .= '<div id="krinkle-snapshots-ajax"></div>';
}

$kgBaseTool->addOut( $pageHtml );

/**
 * Close up
 * -------------------------------------------------
 */
$kgBaseTool->flushMainOutput();
