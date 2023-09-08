<?php 
include_once "../include/header.php";

if($r=$sql->query("SELECT lng_id,lng_name,lng_symbol,lng_default,lng_direction,lng_icon,lng_css FROM languages ORDER BY lng_id ASC;")){
	if($sql->num_rows($r)>0){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr data-ln_id=\"{$row['lng_id']}\">";
			echo "<td></td>";
			echo "<th>{$row['lng_id']}</th>";
			echo "<td class=\"rwicon g\" data-ln_edit>&#xe602;</td>";
			echo "<td class=\"rwicon r\" data-ln_delete>&#xe638;</td>";
			echo "<td class=\"rwicon y\" data-ln_check>".($row['lng_default']==1?"&#xe64a;":"&#xe64b;")."</td>";
			echo "<td>{$row['lng_name']}</td>";
			echo "<td>{$row['lng_symbol']}</td>";
			echo "<td>".($row['lng_direction']=='0'?"ltr":"rtl")."</td>";
			echo "</tr>";
		}
	}else{
		echo "<tr>";
		echo "<td colspan=\"5\"><span style=\"color:#999;float:left;padding:2px;cursor:default\">Languages table is empty</span></td>";
		echo "</tr>";
	}
}
?>