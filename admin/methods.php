<?php
	function replaceAlef($str){
		$str=str_replace("أ","ا",$str);
		$str=str_replace("إ","ا",$str);
		$str=str_replace("آ","ا",$str);
		$str=str_replace("ا","[أإاآ]+",$str);
		return $str;
	}
	function replaceHa2($str){
		$str=str_replace("ة","ه",$str);
		$str=str_replace("ه","[ةه]+",$str);
		return $str;
	}
	function replaceYa2($str){
		$str=str_replace("ي","ى",$str);
		$str=str_replace("ى","[يى]+",$str);
		return $str;
	}	
	function replaceARABIC($str){
		$str=replaceAlef($str);
		$str=replaceHa2($str);
		$str=replaceYa2($str);
		return $str;
	}
	
	function printViewer($id){
		global $app;
		echo "<div id=\"cssViewer\">";
		if($r=$app->db->query("
			SELECT 
				trd_id,trd_directory,pfl_value,trd_attrib4,trd_attrib5
			FROM 
				pagefile 
					JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1
					JOIN pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$app->user->info->permissions}
			WHERE 
				trd_parent=$id AND trd_visible=1 AND trd_enable=1 
			ORDER BY 
				trd_zorder
			")){
			while($row=$r->fetch_assoc()){
				echo "<a href=\"{$row['trd_directory']}\"><span style=\"color:#".($row['trd_attrib5']==null?"333":$row['trd_attrib5'])."\">".($row['trd_attrib4']!=null?"&#xe{$row['trd_attrib4']};":"&nbsp;")."</span><h1>".$row['pfl_value']."</h1></a>";
			}
		}
		echo "</div>";
	}
?>