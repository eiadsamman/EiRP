<?php
use System\Personalization\DashboardReports;
use System\Template\Gremium;
$debug     = $app->user->info->id == 1100;
$debug     = false;
$debugarr  = [];
$dashboard = new DashboardReports($app);
$grem      = new Gremium\Gremium(false);
$grem->header()->sticky(false)->serve("<h1><span style=\"color:var(--input-hover_border-color)\">Welcome </span> {$app->user->info->fullName()}</h1>");
$grem->terminate();
echo "<div class=\"dashboard\">";
foreach ($dashboard->overview(true) as $item) {
	if ($debug)
		$debugarr[] = $item['trd_id'];

	if (file_exists($app->root . "website-contents/{$item['trd_id']}.php")) {
		include($app->root . "website-contents/{$item['trd_id']}.php");
	}
}
echo "</div>";
$atleastone = false;
echo "<div class=\"dashboard\">";
foreach ($dashboard->list(true) as $item) {
	$atleastone = true;
	if ($debug)
		$debugarr[] = $item['trd_id'];
	if (file_exists($app->root . "website-contents/{$item['trd_id']}.php")) {
		include($app->root . "website-contents/{$item['trd_id']}.php");
	}
}
echo "</div>";
if (!$atleastone) {
	echo <<<HTML
<ul>
	<li>Dashboard is empty, goto `<a href="{$fs(17)->dir}">Settings</a>` and select desired reports</li>
</ul>
HTML;
}


if ($debug) {
	echo "<div style=\"position:fixed;bottom:0px;left:0px;z-index:9999\">" . implode(", ", $debugarr) . "</div>";
}
?>
<script type="text/javascript">
	const loadDashboard = function (container, uri) {
		fetch(uri, {
			method: 'POST',
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			referrerPolicy: "no-referrer",
			headers: {
				"X-Requested-With": "fetch",
			},
		}).then(response => {
			if (response.ok)
				return response.text();
			return Promise.reject(response);
		}).then(body => {
			container.innerHTML = body;
		}).catch(response => {
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		let mainpageDashboards = document.querySelectorAll(".dashboard > div > div");
		mainpageDashboards.forEach(element => {
			let uri = element.dataset.uri;
			if (uri != undefined) {
				loadDashboard(element, uri);
			}
		});
	}, false);
</script>