<?php

$debug = $app->user->info->id == 1;
$debugarr = [];


use System\Personalization\DashboardReports;
use System\Template\Gremium;


$dashboard = new DashboardReports($app);
$grem = new Gremium\Gremium(false);


$grem->header()->sticky(false)->serve("<h1><span style=\"color:var(--input_hover-color)\">Welcome </span> {$app->user->info->name}</h1>");

unset($grem);


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
	echo "<div style=\"position:fixed;top:0px;left:0px;\">" . implode("-", $debugarr) . "</div>";
}