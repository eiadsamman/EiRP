<?php
$_TEMPLATE = new \System\Template\Body("Test");
$_TEMPLATE->SetLayout(/*Sticky Title*/false,/*Command Bar*/ false,/*Sticky Frame*/ false);
$_TEMPLATE->FrameTitlesStack(false);
$_TEMPLATE->Title("Welcome", null, null);

echo "<div class=\"homepageWidget\">";
if ($rlocal = $app->db->query(
	"SELECT trd_id 
		FROM pagefile JOIN pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$app->user->info->permissions}
		WHERE trd_parent=73 AND trd_enable=1  
		ORDER BY trd_zorder
	"
)) {
	while ($rowlocal = $rlocal->fetch_assoc()) {
		$reportpageid = $rowlocal['trd_id'];
		if (file_exists($app->root . "website-contents/{$rowlocal['trd_id']}.php")) {
			include($app->root . "website-contents/{$rowlocal['trd_id']}.php");
		}
	}
}
echo "</div>";
