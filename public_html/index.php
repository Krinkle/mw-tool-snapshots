<?php
/**
 * index.php: Web front-end
 * Created on May 6, 2012
 *
 * @package ts-krinkle-mwSnapshots
 * @author Timo Tijhof <krinklemail@gmail.com>, 2012
 * @license CC-BY-SA 3.0 Unported: creativecommons.org/licenses/by/3.0/
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
	$kgBaseTool->addOut( $I18N->msg( 'title-overview' ), 'h2' );
	$pageHtml = kfMsgBlock( $I18N->msg( 'err-snapshotindex' ), 'warning error' );

/**
 * Output (show update log)
 * -------------------------------------------------
 */
} elseif ( $kgReq->getVal( 'action' ) === 'updatelog' ) {

	$kgBaseTool->addOut( $I18N->msg( 'title-updatelog' ), 'h2' );
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

	$repoName = $kgReq->getVal( 'repoName' );
	$branch = $kgReq->getVal( 'branch' );

	if ( !isset( $repoInfos[$repoName] ) || !isset( $snapshotInfo[$repoName] ) ) {
		// Error: Invalid repo
		$kgBaseTool->addOut( $I18N->msg( 'title-error' ), 'h2' );
		$pageHtml .= kfMsgBlock( $I18N->msg( 'err-invalid-repo', array(
				'variables' => array( $repoName )
		) ), 'warning' );
	} else {
		$repoInfo = $repoInfos[$repoName];
		$data = $snapshotInfo[$repoName];
		if ( !isset( $data['branches'][$branch] ) ) {
			// Error: Invalid branch
			$kgBaseTool->addOut( $I18N->msg( 'title-error' ), 'h2' );
			$pageHtml .= kfMsgBlock( $I18N->msg( 'err-invalid-branch', array(
				'variables' => array( $branch, $repoName )
			) ), 'warning' );
		} elseif ( $data['branches'][$branch]['snapshot'] == false ) {
			// Error: Snapshot unavaiable
			$kgBaseTool->addOut( $I18N->msg( 'title-error' ), 'h2' );
			$pageHtml .= kfMsgBlock( $I18N->msg( 'err-nosnapshot', array(
				'variables' => array( $branch )
			) ), 'warning' );
		} else {
			$branchInfo = $data['branches'][$branch];
			// Downoading snapshot
			$kgBaseTool->addOut( $I18N->msg( 'title-downloading', array(
				'variables' => array( $branchInfo['snapshot']['path'] )
			) ), 'h2' );
			$downloadUrl = $kgTool->getDownloadUrl( $repoName, $branchInfo );
			$pageHtml .= '<p>'
				. Html::element( 'strong', array(), $I18N->msg( 'downloading-intro' ) )
				. ' '
				. Html::element( 'a', array( 'href' => $downloadUrl ), $I18N->msg( 'downloading-directlink' ) )
				. '</p>'
				. '<table class="wikitable krinkle-mwSnapshots-download"><tbody>'
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
								'href' => str_replace( '$1', $branch, $repoInfo['repo-branch-url'] ),
								'target' => '_blank',
							),
							$branch
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
					.	 '<br>'
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
				. '<script>'
				. 'setTimeout(function () {'
				. 'var downloadUrl = ' . json_encode( $downloadUrl ) . ';'
				. 'window.location.href = downloadUrl;'
				. '}, 1000);'
				. '</script>';
		}
	}


/**
 * Output (overview)
 * -------------------------------------------------
 */
} else {
	$kgBaseTool->addOut( $I18N->msg( 'title-overview' ), 'h2' );

	$formBase = Html::openElement( 'form', array(
		'action' => $kgBaseTool->remoteBasePath,
		'method' => 'post',
	));

	$pageHtml .= '<table class="wikitable krinkle-mwSnapshots-repos"><thead><tr><th colspan="2">'
		. htmlspecialchars( $I18N->msg( 'tablehead-repo' ) )
		. '</th><th>'
		. htmlspecialchars( $I18N->msg( 'tablehead-snapshots' ) )
		. '</th></tr></thead><tbody>';

	$updatelogLink = '';
	if ( $kgTool->getUpdateLogFilePath() ) {
		$updatelogLink = ' ' . Html::element( 'a', array(
			'href' => $kgBaseTool->remoteBasePath . '?action=updatelog',
		), $I18N->parentheses( 'updatelog-link' ) );
	}

	foreach ( $snapshotInfo as $repoName => $data ) {
	if ( isset( $repoInfos[$repoName] ) ) {
		$repo = $repoInfos[$repoName];
		$branchesSelect = Html::openElement( 'select', array(
			'name' => 'branch',
			'id' => 'branches-' . $repoName,
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
			'<tr><td class="krinkle-mwSnapshots-repo-logo">'
				. ( isset( $repo['img'] )
					? Html::element( 'img', array(
							'src' => $repo['img'],
							'width' => '135'
						)
					)
					: ''
				)
			. '</td><td class="krinkle-mwSnapshots-repo-title">'
				. '<p><strong>'
				. Html::element( 'a', array(
						'href' => $repo['site-url'],
							'target' => '_blank',
					), $repo['display-title']
				)
				. '</strong></p>'
				. '<ul>'
					. '<li>' . Html::element( 'a', array(
							'href' => $repo['site-url'],
							'target' => '_blank',
						), $I18N->msg( 'repo-site-link' )
					)
					. '</li><li>' . Html::element( 'a', array(
							'href' => $repo['repo-browse-url'],
							'target' => '_blank',
						), $I18N->msg( 'repo-browse-link' )
					)
					. '</li>'
				. '</ul>'
			. '</td><td class="krinkle-mwSnapshots-repo-select">'
				. $formBase
				. Html::element( 'input', array(
					'type' => 'hidden',
					'name' => 'action',
					'value' => 'getSnapshot'
				))
				. Html::element( 'input', array(
					'type' => 'hidden',
					'name' => 'repoName',
					'value' => $repoName
				))
				. Html::element( 'label', array(
						'for' => 'branches-' . $repoName
					),
					$I18N->msg( 'repo-branches-label' )
				)
				. '&nbsp;'
				. $branchesSelect
				. '<div class="krinkle-mwSnapshots-repo-select-submit">'
				. Html::element( 'input', array(
					'type' => 'submit',
					'nof' => true,
					'value' => $I18N->msg( 'branches-submit-button' )
				))
				. '</div>'
				. '</form>'
				. '<p class="krinkle-mwSnapshots-repo-dumpdate">Last dump: '
					. Html::element( 'time', array(
						'pubdate' => true,
						'datetime' => gmdate( 'Y-m-d\TH:i:s\Z', $data['_updateEnd'] ),
						'title' => gmdate( 'Y-m-d\TH:i:s\Z', $data['_updateEnd'] ),
					), $kgTool->dumpTimeAgo( $data['_updateEnd'] ) )
					. $updatelogLink
				. '</p>'
			. '</td>'
			. '</tr>';

	} else {
		$pageHtml .= '<!-- unknown repository: ' . htmlspecialchars( $repoName ) . ' -->';
	}
	}
	$pageHtml .= '</tbody></table>';
}

$kgBaseTool->addOut( $pageHtml );

/**
 * Close up
 * -------------------------------------------------
 */
$kgBaseTool->flushMainOutput();
