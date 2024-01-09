<?php

use System\Personalization\DashboardReports;
use System\Template\Gremium;

$dashboard = new DashboardReports($app);
$grem      = new Gremium\Gremium(false);


$grem->header()->sticky(false)->serve("<h1><span style=\"color:var(--input_hover-border)\">Welcome </span> {$app->user->info->name}</h1>");

unset($grem);

$atleastone = false;
echo "<div class=\"dashboard\">";
foreach ($dashboard->list(true) as $dashboard) {
	$atleastone   = true;
	$reportpageid = $dashboard['trd_id'];
	if (file_exists($app->root . "website-contents/{$dashboard['trd_id']}.php")) {
		include($app->root . "website-contents/{$dashboard['trd_id']}.php");
	}
}
echo "</div>";
if(!$atleastone){
echo <<<HTML
<ul>
<li>Dashboard is empty, goto `<a href="{$fs(17)->dir}">Settings</a>` and select desired reports</li>
</ul>
HTML;
}
