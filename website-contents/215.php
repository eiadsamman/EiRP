<?php
echo "
<div>
	<div class=\"widgetWQT\">";
		$q = $sql->query("
				SELECT 
					trd_directory,trd_id,pfl_value,trd_attrib4,trd_attrib5
				FROM 
					pagefile 
						JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1 
						JOIN 
							pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$USER->info->permissions}
						LEFT JOIN user_settings ON usrset_usr_defind_name=trd_id AND usrset_usr_id={$USER->info->id} AND usrset_name='system_count_page_visit'	
				WHERE 
					trd_visible = 1 AND trd_enable = 1
				ORDER BY (usrset_value+0) DESC,pfl_value
				LIMIT 5");
		if($q){
			while($row=$sql->fetch_assoc($q)){
				echo "<a href=\"{$row['trd_directory']}\">
					<span ".($row['trd_attrib5']!=null?"style=\"font-family:icomoon4;\"":"font-family:icomoon4;").">&#xe{$row['trd_attrib4']};</span>".$row['pfl_value']."</a>";
			}
		}

		echo "
	</div>
</div>";
?>