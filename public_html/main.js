(function (K, $) {
	var $ajaxTarget, $branchesSelects, $first, $loader, $pageWrap,
		prevRepoBranch, infoAjax,
		log = window.console && console.log && console.log.bind && console.log.bind(console) || function () {};

	$loader = $('<div class="snapshots-ajax-loader"></div>');

	function showAjaxInfo(repo, branch) {

		function getAjaxInfo() {
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
				infoAjax,
				$ajaxTarget
			)
			.done(function (argsAjax) {
				var ajaxResp, $page;

				ajaxResp = argsAjax[0];

				if (ajaxResp.pageHtml) {
					$ajaxTarget.empty().append(ajaxResp.pageHtml);

				} else {
					$ajaxTarget.html('<div class="alert alert-danger">Unable to retreive data...</div>');
				}
				$ajaxTarget.fadeIn();
			})
			.fail(function () {
				$ajaxTarget.fadeOut();
				log('getAjaxInfo failed', arguments);
			});
		}

		if (prevRepoBranch !== (repo + branch)) {
			prevRepoBranch = repo + branch;

			if (infoAjax && infoAjax.state() === 'pending') {
				infoAjax.abort();
			}

			hashSet(repo, branch);
			$ajaxTarget.fadeOut(getAjaxInfo);
		}
	}

	function strExplode(str, delimiter, limit) {
		var parts = str.split(delimiter),
			ret = parts.splice(0, limit - 1),
			tmp = parts.join(delimiter);
		ret.push(tmp);
		return ret;
	}

	/** return Object|Boolean */
	function hashGet() {
		var parts;
		if (location.hash.substr(0, 3) === '#!/' && location.hash.length > 3) {
			parts = strExplode(location.hash.substr(3), '/', 2);
			if (parts.length === 2) {
				return {
					repo: parts[0],
					branch: parts[1]
				};
			}
		}
		return false;
	}

	function hashSet(repo, branch) {
		window.location.hash = '#!/' + repo + '/' + branch;
	}

	$(document).ready(function ($) {
		var hash;
		$ajaxTarget = $('#snapshots-ajax');
		$pageWrap = $('#page-wrap');

		$branchesSelects = $('.snapshots-branches').on('change blur', function () {
			var $el = $(this);
			showAjaxInfo($el.data('repoName'), $el.val());
		});
		$first = $branchesSelects.eq(0);

		hash = hashGet();
		if (!hash) {
			if ($first.length) {
				showAjaxInfo($first.data('repoName'), $first.val());
			}
		} else {
			$branchesSelects
				.filter(function () {
					return $(this).data('repoName') === hash.repo;
				})
				.val(hash.branch);

			showAjaxInfo(hash.repo, hash.branch);
		}

	});

}(KRINKLE, jQuery));
