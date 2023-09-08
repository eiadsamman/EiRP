<?php 
include_once "../include/header.php";

if($r=$sql->query("SELECT per_id,per_title,per_description,per_order FROM permissions ORDER BY per_order DESC;")){
	if($sql->num_rows($r)>0){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr data-pr_id=\"{$row['per_id']}\">";
			echo "<td></td>";
			echo "<th>{$row['per_id']}</th>";
			echo "<td class=\"rwicon g\" data-pf_edit>&#xe602;</td>";
			echo "<td class=\"rwicon r\" data-pf_delete>&#xe638;</td>";
			echo "<td>{$row['per_title']}</td>";
			echo "<td>{$row['per_description']}</td>";
			echo "<td>{$row['per_order']}</td>";
			echo "</tr>";
		}
	}else{
		echo "<tr>";
		echo "<td colspan=\"4\"><span style=\"color:#999;cursor:default\">Permissions table is empty</span></td>";
		echo "</tr>";
	}
}
?>