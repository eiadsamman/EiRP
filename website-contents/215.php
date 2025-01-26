<?php
$rp = 1;
$r  = $app->db->query(
	"SELECT 
		trd_directory,pfl_value,trd_attrib4,trd_attrib5
	FROM 
		pagefile 
			JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id = 1 
			JOIN 
				pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$app->user->info->permissions}
			LEFT JOIN user_settings ON usrset_usr_defind_name = trd_id AND usrset_usr_id = {$app->user->info->id} AND usrset_type = " . \System\Controller\Personalization\Identifiers::SystemFrequentVisit->value . "	AND 1
	WHERE 
		trd_visible = 1 AND trd_enable = 1
	ORDER BY (usrset_value + 0) DESC, pfl_value
	LIMIT 4"
);
if ($r && $r->num_rows > 0) {
	$rr = $r;
	echo "<div class=\"links rowclicks\"><span>Most frequent</span><div>";
	while ($row = $r->fetch_assoc()) {
		echo "<a href=\"{$row['trd_directory']}\">";
		echo "<span style=\"color:var(--root-font-color);background-color:var(--static-bgcolor);\">&#xe{$row['trd_attrib4']};</span>";//color:var(--root-font-color);background-image: linear-gradient(to top, var(--root-background-color), var(--root-background-color));
		echo "<div>{$row['pfl_value']}</div>";
		echo "</a>";
	}
	echo "</div>";
	echo "</div>";
}