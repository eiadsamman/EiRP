<?php
$output=null;
if(isset($_POST['data'])){
	$data=explode("\n",$_POST['data']);
	
	$sql->query("DELETE FROM data;");
	$q="INSERT INTO data (bom_beipn,bom_sapno,bom_sapdesc,bom_unit,bom_mattype) VALUES ";
	$smart="";
	foreach ($data as $rows){
		$row=explode("\t",$rows);
		if(sizeof($row)==5){
			$q.=$smart."(";
			$cute="";
			foreach($row as $col){
				$q.=$cute.'"'.trim($col).'"';
				$cute=",";
			}
			$q.=")";
			$smart=",";
		}
	}
	$q=$sql->query($q);
	if($q){
		$output=true;
	}else{
		$output=false;
	}
}
$r=$sql->query("SELECT bom_beipn,bom_sapno,bom_sapdesc,bom_unit,bom_mattype FROM data");
$data="";$smart="";$cute="";
while($row= $sql->fetch_assoc($r)){
	$data.=$smart."";
	$cute="";
	foreach($row as $col){
		$data.=$cute.$col;
		$cute="\t";
	}
	$smart="\n";
}

?>
<form action="<?php echo $pageinfo['directory'];?>" method="post">
<textarea style="width:100%;" rows="30" name="data">
<?php echo $data;?>
</textarea>
<br /><br />
<div style="text-align:center" class="btn-set normal"><button type="submit">Import</button><button type="reset">Reset</button></div>
<?php
if($output===true){
	echo "<span style=\"color:#0a3;\">Data imported</span>";
}elseif($output===false){
	echo "<span style=\"color:#f03;\">Failed to importe data</span>";
}
?>

</form>








