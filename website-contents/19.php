<?php

use System\Template\Gremium;

$grem = new Gremium\Gremium(false);
$grem->header()->sticky(false)->serve("<h1><span id=\"xxx\" style=\"\">Welcome </span> {$app->user->info->name}</h1>");
$grem->article()->open();
echo "<div class=\"homepageWidget\">";
if (
	$rlocal = $app->db->query("SELECT trd_id 
		FROM pagefile JOIN pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$app->user->info->permissions}
		WHERE trd_parent = 73 AND trd_enable = 1  ORDER BY trd_zorder")
) {
	while ($rowlocal = $rlocal->fetch_assoc()) {
		$reportpageid = $rowlocal['trd_id'];
		if (file_exists($app->root . "website-contents/{$rowlocal['trd_id']}.php")) {
			include($app->root . "website-contents/{$rowlocal['trd_id']}.php");
		}
	}
}
echo "</div>";
$grem->getLast()->close();