<?php
if(!isset($_POST['esstore']) || !isset($_POST['esdate']) ){
	exit;
}
function getbeidet($id){
	global $sql;
	$output=array();
	if($r=$sql->query("SELECT bom_sno,bom_beipn,bom_sapno,bom_sapdesc,bom_unit,bom_mattype FROM data WHERE bom_id='".((int)$id)."';")){
		if($row=$sql->fetch_assoc($r)){
			$output=$row;
		}
	}
	return $output;
}
$storage_location=isset($_POST['esstore'])?$_POST['esstore']:"";
$issue_date=isset($_POST['esdate'])?$_POST['esdate']:date("d.m.Y");
?>
<style>
.special{
	background-color:#fff;
}
.special > tbody > tr >td{
	white-space:nowrap;font-size:1em;
}
</style>
<table class="bom-table special">
<thead>
<tr>
	<td>MATNR</td>
	<td>WERKS</td>
	<td>STLAN</td>
	<td>STLAL</td>
	<td>DATUV</td>
	<td>ZTEXT</td>
	<td>BMENG</td>
	<td>STLST</td>
	<td>IDNRK</td>
	<td>MENGE</td>
	<td>MEINS</td>
	<td>POSTP</td>
	<td>POSNR</td>
	<td>SANFE</td>
	<td>SANKA</td>
	<td>AUSKZ</td>
	<td>POTX1</td>
	<td>LGORT</td>
	<td>EBORT</td>
	<td>UPMNG</td>
</tr>
</thead>
<tbody><?php

foreach($_POST['esbom'][0] as $lvl0k=>$lvl0v){
	if($lvl0v[1]!="" && (int)$lvl0v[1]!=0){
		
		$header=true;
		$comass=getbeidet($lvl0v[1]);
		if(sizeof($comass)==0){continue;}
		$majcount=0;
		foreach($_POST['esbom'][$lvl0k] as $lvlxk=>$lvlxv){
			if($lvlxv[1]!="" && (int)$lvlxv[1]!=0){
				$ref=$_POST['esref'][$lvl0k][$lvlxk];
				$ref=explode(";",$ref);
				$majcount++;
				$bomass=getbeidet($lvlxv[1]);
				if(sizeof($bomass)==0){continue;}
				$cnt=$_POST['esqty'][$lvl0k][$lvlxk];
				if((int)$cnt==(float)$cnt){
					$manor=true;
					for($i=1;$i<=(int)$cnt;$i++){
						echo "<tr>";
						echo "<td>".$comass['bom_sapno']."</td>";
						echo "<td>".($header?"EE10":"")."</td>";
						echo "<td>".($header?"1":"")."</td>";
						echo "<td>1</td>";
						echo "<td>".$issue_date."</td>";
						echo "<td>".($header?$comass['bom_beipn']:"")."</td>";
						echo "<td>".($header?"1":"")."</td>";
						echo "<td>".($header?"1":"")."</td>";
						echo "<td>".$bomass['bom_sapno']."</td>";
		
						echo "<td>".($manor?$cnt:"")."</td>";
						echo "<td>".($manor?$bomass['bom_unit']:"")."</td>";
						echo "<td>".($manor?"L":"")."</td>";
						
						echo "<td>".str_pad($majcount,3,"0",STR_PAD_LEFT)."0</td>";
						
						echo "<td>".($manor?"X":"")."</td>";
						echo "<td>".($manor?"X":"")."</td>";
						echo "<td>".($manor?"X":"")."</td>";
						
						echo "<td>".($manor?$bomass['bom_beipn']:"")."</td>";
						
						echo "<td>".($manor?$storage_location:"")."</td>";
						echo "<td>".(isset($ref[$i-1])?$ref[$i-1]:"")."</td>";
						echo "<td>1</td>";
						
						
						echo "</tr>";
						$header=false;
						$manor=false;
					}
				}else{
					$manor=true;
					echo "<tr>";
					echo "<td>".$comass['bom_sapno']."</td>";
					echo "<td>".($header?"EE10":"")."</td>";
					echo "<td>".($header?"1":"")."</td>";
					echo "<td>1</td>";
					echo "<td>".$issue_date."</td>";
					echo "<td>".($header?$comass['bom_beipn']:"")."</td>";
					echo "<td>".($header?"1":"")."</td>";
					echo "<td>".($header?"1":"")."</td>";
					echo "<td>".$bomass['bom_sapno']."</td>";
	
					echo "<td>".($manor?$cnt:"")."</td>";
					echo "<td>".($manor?$bomass['bom_unit']:"")."</td>";
					echo "<td>".($manor?"L":"")."</td>";
					
					echo "<td>".str_pad($majcount,3,"0",STR_PAD_LEFT)."0</td>";
					
					echo "<td>".($manor?"X":"")."</td>";
					echo "<td>".($manor?"X":"")."</td>";
					echo "<td>".($manor?"X":"")."</td>";
					
					echo "<td>".($manor?$bomass['bom_beipn']:"")."</td>";
					
					echo "<td>".($manor?$storage_location:"")."</td>";
					
					echo "<td>{$ref[0]}</td>";
					echo "<td>1</td>";
						
					echo "</tr>";
					$header=false;
					$manor=false;
				}
				
				
				
				
			}
		}
	}
}


?>
</tbody></table>