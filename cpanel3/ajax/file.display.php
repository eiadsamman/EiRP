<?php 
include_once "../include/header.php";
$bool=true;

if(isset($_POST['s'],$_POST['p']) && $_POST['s']=="1"){
	$q		=trim($_POST['p']);
	$q		=str_replace(array("(",")","[","]","+","?",".","*","\\","/"),"",$q);
	$q		=$sql->escape($q);
	
	$colArr	="trd_directory,pfl_value,trd_id";
	$sq 	=' ';
	$i		=0;
	$sJS	="";
	$cols	=explode(",",$colArr);
	$q		=explode(" ",$q);
	for($i=0;$i<sizeof($q);$i++){
		$sq.="(";
		for($j=0;$j<sizeof($cols);$j++){
			$sq.=" {$cols[$j]} RLIKE '.*".replaceARABIC($q[$i]).".*' ";
			if($j!=sizeof($cols)-1)
				$sq.=' or ';
		}
		$sq.=")";
		if($i!=sizeof($q)-1)
			$sq.=' AND ';
	}
	$r=$sql->query("
		SELECT 
			trd_id,trd_directory,trd_visible,trd_enable,trd_parent,trd_header ,pfl_value
		FROM 
			pagefile LEFT JOIN 
				(SELECT
					lng_default,lng_id,pfl_value,pfl_trd_id
				FROM
					pagefile_language JOIN languages ON lng_id=pfl_lng_id
				) AS _a ON _a.pfl_trd_id=trd_id
		WHERE
			trd_id=trd_id AND $sq
		GROUP BY
			trd_id
		ORDER BY 
			trd_id;");
}elseif(isset($_POST['p'])){
	$parent=array(0,"");
	$parent=false;
	$_POST['p']=trim(addslashes($_POST['p']),"/\\");
	if($_POST['p']==""){
		$parent=array();
		$parent[0]=(int)0;
		$parent[1]="";
	}else{
		$r=$sql->query("SELECT trd_parent,trd_id,trd_directory FROM pagefile WHERE ".(trim($_POST['p'])==""?"trd_parent=0":"trd_directory='{$_POST['p']}'")."");
		if($r && $row=$sql->fetch_assoc($r)){
			$parent=array();
			$parent[0]=(int)$row['trd_id'];
			$parent[1]=$row['trd_directory'];
		}
	}
	
	if(!$parent){
		$bool=false;
		echo "<td colspan=\"11\"><span style=\"color:#999;cursor:default\">Invalid page directory</span></td>";
	}else{
		$r=$sql->query("SELECT trd_id,trd_directory,trd_visible,trd_enable,trd_parent,trd_header ,pfl_value
			FROM 
				pagefile LEFT JOIN 
					(SELECT
						lng_default,lng_id,pfl_value,pfl_trd_id
					FROM
						pagefile_language JOIN languages ON lng_id=pfl_lng_id
					WHERE
						lng_default=1
					) AS _a ON _a.pfl_trd_id=trd_id
			WHERE 
				trd_parent={$parent[0]} 
			GROUP BY
				trd_id
			ORDER BY trd_zorder;");
			echo $sql->error();
	}
	
}else{
	exit;
}

if($r && $bool){
	if($sql->num_rows($r)>0)
	while($row=$sql->fetch_assoc($r)){
		echo "<tr data-pf_id=\"{$row['trd_id']}\">";
		echo "<td style=\"min-width:34px;\" class=\"orderHandle\">:::</td>";
		echo "<th style=\"min-width:60px\">{$row['trd_id']}</th>";
		echo "<td class=\"rwicon p\" data-pf_visible>".($row['trd_visible']==1?"&#xe62e;":"&#xe631;")."</td>";
		echo "<td class=\"rwicon y\" data-pf_access>".($row['trd_enable']==1?"&#xe622;":"&#xe621;")."</td>";
		echo "<td class=\"rwicon g\" data-pf_edit>&#xe602;</td>";
		echo "<td class=\"rwicon\"   data-pf_move>&#xe64c;</td>";
		echo "<td class=\"rwicon r\" data-pf_delete>&#xe638;</td>";
		echo "<td width=\"50%\"><a href=\"m_pagefile/?p={$row['trd_directory']}\" data-href=\"{$row['trd_directory']}\" class=\"jQPF_emu\" style=\"float:left;\">{$row['trd_directory']}</a></td>";
		echo "<td width=\"50%\">{$row['pfl_value']}</td>";
		echo "</tr>";
	}
	else{
		echo "<tr>";
		echo "<td colspan=\"11\"><span style=\"color:#999;cursor:default\">This directory is empty</span></td>";
		echo "</tr>";
	}
}
?>