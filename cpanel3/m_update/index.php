<?php 
	include_once "../include/header.php";
	
	$lastestbuild = $sql->query("SELECT vernumber,verdate from updateserver ORDER BY vernumber DESC limit 1");
	
	$latestversion = false;
	if($lastestbuild && $lastestbuildr=$sql->fetch_assoc($lastestbuild)){
		$latestversion = (int)$lastestbuildr['vernumber'];
	}

	$settingsfile=@realpath("{$_SERVER['FILE_SYSTEM_ROOT']}cpanel3.versions.xml");
	
	$verions = array();
	$dom=new DOMDocument('1.0');
	if(@$dom->load($settingsfile)){
		$xmlparser=$dom->documentElement;
		if($xmlparser->hasChildNodes()){
			foreach($xmlparser->childNodes as $versions){
				if($xmlparser->nodeType==XML_ELEMENT_NODE && $versions->nodeName=="versions"){
					
					foreach($versions->childNodes as $ver){
						$vno = false;
						if($ver->nodeType == XML_ELEMENT_NODE  && $ver->nodeName=="version"){
							
							$temp = (int) $ver->getAttribute("number");
							if ($temp !== 0 && $temp > $latestversion){
								$vno = $temp;
								$verions[$vno]=array(
									"date"=>$ver->getAttribute("date"),
									"version"=>$vno,
									"review"=>"",
									"status"=>"pending",
									"commands"=>array(),
									"file"=>array(),
								);
							}
						}
						
						if($vno){
							foreach($ver->childNodes as $comm){
								if($comm->nodeType == XML_ELEMENT_NODE && $comm->nodeName == 'sql'){
									$verions[$vno]['commands'][]=$comm->nodeValue;
									//Here goes the execution commands
									//$sql->query($comm->nodeValue);
								}elseif($comm->nodeType == XML_ELEMENT_NODE && $comm->nodeName == 'file'){
									$verions[$vno]['file'][]=$comm->nodeValue;
								}elseif($comm->nodeType == XML_ELEMENT_NODE && $comm->nodeName == 'review'){
									$verions[$vno]['review']=$comm->nodeValue;
								}
							}
						}
						
					}
					//$versions->nodeValue
				}
			}
		}
	}
	ksort($verions);
	
	
	
	if(isset($_GET['do'],$_GET['psid']) && $_GET['do']=="update" && $_GET['psid']==md5(session_id())){
		
		$sql->autocommit(false);
		$updateresult=true;
		foreach($verions as $vernum=>$dataset){
			
			foreach($dataset['commands'] as $k=>$query){
				$updateresult &= $sql->query($query);
				if(!$updateresult){
					$sql->rollback();
					break;
				}
			}
			if($updateresult){
				$updateresult &= $sql->query("INSERT INTO updateserver (vernumber,verdate) VALUES ($vernum,'{$dataset['date']}');");
			}
			if($updateresult){
				$sql->commit();
				$verions[$vernum]['status']="updated";
			}else{
				$sql->rollback();
				$verions[$vernum]['status']="failed";
				break;
			}
		}

	}
	
	function convert_version_number(int $vernum):string{
		return (int)($vernum/(10**6)).".".(int)(($vernum%10**6)/10**3).".".($vernum%10**3);
	}
	
	include_once "../include/html.header.php";
	?>
	<h1 style="font-size:1.2em">CPanel Updater</h1>
	<div class="btn-set">
		<span>Current build</span>
		<input style="width:120px" value="<?php echo $latestversion?convert_version_number($latestversion):"0";?>" readonly />
		<span>Latest build</span>
		
		<?php 
		$lat = array_key_last($verions);
		if($lat){?>
			<input style="width:120px" value="<?php echo convert_version_number($lat);?>" readonly />
		<?php }else{?>
			<input style="width:120px" value="<?php echo $latestversion?convert_version_number($latestversion):"0";?>" readonly />
		<?php }?>
		
		<?php if(sizeof($verions)>0){?>
		<a href="m_update/?do=update&psid=<?php echo md5(session_id());?>">Upgrade</a>
		<?php }?>
	</div>
	<?php 
		foreach($verions as $vernum=>$dataset){
			echo "<div style=\"margin-top:20px;\"><table class=\"bom-table hover\">";
			echo "<thead><tr><td colspan=\"2\" style=\"text-transform:capitalize;border-left:solid 3px #06c;font-weight:bold\">Version ".convert_version_number($vernum)." [".$dataset['date']."]</td></tr></thead>";
			
			echo "<tbody><tr><td>";
			if($dataset['status']=="updated"){
				echo "<div style=\"color:#0a6\">Version updated successfully</div>";
			}elseif($dataset['status']=="failed"){
				echo "<div style=\"color:#b33\">Version update failed</div>";
			}
			
			echo "<b>".$dataset['review']."</b><br />";
			if(sizeof($dataset['commands'])>0)
				echo " - SQL updates count: (".sizeof($dataset['commands']).") queries<br/>";
			if(sizeof($dataset['file'])>0)
				echo " - Affected files count: (".sizeof($dataset['file']).") files<br/>";
			
			echo "</td></tr></tbody>";
			echo "</table></div>";
		}

	?>
	
<?php
	include_once "../include/html.footer.php";
?>
	