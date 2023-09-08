<?php 
include_once "../include/header.php";
$json=new CPANEL_JSON();
if(isset($_POST['s'],$_POST['p']) && $_POST['s']=="1"){
	$q		=trim($_POST['p']);
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
	
	$r=$sql->query("SELECT 
			COUNT(DISTINCT trd_id) AS zcount FROM 
			pagefile LEFT JOIN 
				(SELECT
					lng_default,lng_id,pfl_value,pfl_trd_id
				FROM
					pagefile_language JOIN languages ON lng_id=pfl_lng_id
				) AS _a ON _a.pfl_trd_id=trd_id
		WHERE
			trd_id=trd_id AND $sq

		ORDER BY 
			trd_id");
	$cpagefiles=0;
	if($r && $row=$sql->fetch_assoc($r)){
		$cpagefiles=$row['zcount'];
	}
	$json->output(true,addslashes(trim($_POST['p'])),null,array("count"=>$cpagefiles,"search"=>"1"));
}elseif(isset($_POST['p'])){
	$location=new Location();
	$parent=false;
	$_POST['p']=trim(addslashes($_POST['p']),"/\\");
	if($_POST['p']==""){
		$parent=array();
		$parent[0]=(int)0;
		$parent[1]="";
	}else{
		$r=$sql->query("SELECT trd_parent,trd_id,trd_directory FROM pagefile WHERE trd_directory='{$_POST['p']}'");
		if($r && $row=$sql->fetch_assoc($r)){
			$parent=array();
			$parent[0]=(int)$row['trd_id'];
			$parent[1]=$row['trd_directory'];
		}
	}
	
	
	if(!$parent){
		if(isset($raw) && $raw==true){
			$rawoutput['trace']="Invalid page directory";
			$rawoutput['result']=false;
		}else{
			$json->output(false,"Invalid page directory",null,"");
			exit;
		}
	}else{
		$location->location_build($parent[0],true,true,"trd_directory,trd_id,pfl_value",1);
		
		$r=$sql->query("SELECT count(trd_id) AS zcount FROM pagefile WHERE trd_parent='{$parent[0]}';");
		$cpagefiles=0;
		if($r && $row=$sql->fetch_assoc($r)){
			$cpagefiles=$row['zcount'];
		}
		
		$dir="";
		$output="<a href=\"m_pagefile/\" class=\"jQPF_emu\" data-href=\"\">Root</a>";
		foreach($location->location_trace() as $pf){
			if(trim($pf[2])==""){$pf[2]="[empty]";}
			$output.="<a class=\"jQPF_emu\" data-href=\"{$pf[0]}\" href=\"m_pagefile/?p={$pf[0]}\">{$pf[2]}</a>";
		}
		
		if(isset($raw) && $raw==true){
			$rawoutput['count']=$cpagefiles;
			$rawoutput['trace']=$output;
			$rawoutput['result']=true;
			$rawoutput['id']=$parent[0]==0?"":$parent[0];
			$rawoutput['root']=($parent[0]==0?true:false);
			$rawoutput['directory']=$parent[1];
			
		}else{
			$json->output(true,"",null,array("id"=>"{$parent[0]}","trace"=>addslashes($output),"count"=>"$cpagefiles","search"=>"0","directory"=>$parent[1],"root"=>($parent[0]==0?"1":"0")));
		}
	}
}


?>