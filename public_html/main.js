(function (K, $) {
	var $branchesSelects, $first, $ajaxTarget, $pageWrap, $loader;

	$loader = $('<div class="cssloader-circles krinkle-mwSnapshots-ajax-loader">\
		<div class="cssloader-circles-item cssloader-circles-item1"></div>\
		<div class="cssloader-circles-item cssloader-circles-item2"></div>\
		<div class="cssloader-circles-item cssloader-circles-item3"></div>\
		<div class="cssloader-circles-item cssloader-circles-item4"></div>\
		<div class="cssloader-circles-item cssloader-circles-item5"></div>\
		<div class="cssloader-circles-item cssloader-circles-item6"></div>\
		<div class="cssloader-circles-item cssloader-circles-item7"></div>\
		<div class="cssloader-circles-item cssloader-circles-item8"></div>\
		</div>'
	);

	function showAjaxInfo(repo, branch) {
		function getAjaxInfo() {
			var infoAjax;

			$ajaxTarget.empty().append($loader).fadeIn();

			infoAjax = $.ajax({
				url: K.baseTool.basePath,
				type: 'POST',
				data: {
					action: 'getSnapshot',
					repo: repo,
					branch: branch,
					ajax: ''
				},
				dataType: 'json'
			});

			// After animation and ajax is done, fill $ajaxTarget and show it.
			// Using always() since we also want to continue if ajax failed.
			$.when(
				$ajaxTarget,
				infoAjax
			).always(function ($ajaxTarget, ajaxArgs) {
				var ajaxResp, $page;

				ajaxResp = ajaxArgs[0];

				if (ajaxResp.pageHtml) {
					$page = $('<div>').append(ajaxResp.pageHtml);
					if (ajaxResp.downloadUrl) {
						$page.find('.krinkle-mwSnapshots-download-badge button')
							.click(function () {
								window.location.href = ajaxResp.downloadUrl;
							});
					}
					$ajaxTarget.empty().append($page);

				} else {
					$ajaxTarget.html('<div class="basetool-msg warning">Error retreiving data from server...</div>');
				}
				$ajaxTarget.fadeIn();
			});
		}

		$ajaxTarget.fadeOut(getAjaxInfo);
	}

	$(document).ready(function ($) {
		$ajaxTarget = $('#krinkle-mwSnapshots-ajax');
		$pageWrap = $('#page-wrap');

		$branchesSelects = $('.krinkle-mwSnapshots-branches').on('change blur', function () {
			var $el = $(this);
			showAjaxInfo($el.data('repoName'), $el.val());
		});

		$first = $branchesSelects.eq(0);
		showAjaxInfo($first.data('repoName'), $first.val());

	});

}(KRINKLE, jQuery));
