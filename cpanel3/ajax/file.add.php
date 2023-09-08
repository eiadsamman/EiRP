<?php 
if(!isset($_POST['pf_id'],$_POST['method'])){exit;}
include_once "../include/header.php";
$_POST['__page-header']=0;
$_POST['__page-contents']=10;

$_POST['__page-active']=1;
$_POST['__page-visible']=1;
$_POST['__page-privileges']=array();
$_POST['__page-loader']="";
$_POST['__page-forward']="";
$_POST['__page-name']=array();
$_POST['__page-css']="";
$_POST['__page-js']="";
$_POST['__page-param']="";

$r=$sql->query("SELECT per_title,per_id FROM permissions ORDER BY per_id ASC;");
if($r){
	while($row=$sql->fetch_assoc($r)){
		$_POST['__page-privileges'][$row['per_id']]=array($row['per_title'],"0");
	}
}
$arrLanguages=array();
$defaultLanguage=0;
$r=$sql->query("select lng_id,lng_symbol,lng_name,lng_default from languages order by lng_default desc,lng_name asc;");
if($r!==false){
	while($row=$sql->fetch_assoc($r)){
		$arrLanguages[$row['lng_id']]=(array($row['lng_symbol'],$row['lng_name']));
		$_POST['__page-name'][$row['lng_id']]="";
		if($row['lng_default']==1){
			$defaultLanguage=$row['lng_id'];
		}
	}
}


if(sizeof($arrLanguages)==0){echo "No languages added to CPanel yet";exit;}
if($_POST['method']=='edit'){
	$r=$sql->query("SELECT trd_id,trd_directory,trd_visible,trd_param,trd_enable,trd_header,trd_loader,trd_forward,trd_css,trd_js FROM pagefile WHERE trd_id=".((int)$_POST['pf_id']).";");
	if($r){
		if($row=$sql->fetch_assoc($r)){
			$_POST['__page-directory']=$row['trd_directory']."/";
			$_POST['__page-active']=$row['trd_enable'];
			$_POST['__page-visible']=$row['trd_visible'];
			
			$headercode=(string)$row['trd_header'];
			
			$_POST['__page-header']=isset($headercode[1])?$headercode[1]:$_POST['__page-header'];
			$_POST['__page-contents']=isset($headercode[0])?(int)$headercode[0]."0":$_POST['__page-contents'];
			
			$_POST['__page-loader']=$row['trd_loader'];
			$_POST['__page-forward']=is_null($row['trd_forward'])?"":$row['trd_forward'];
			$_POST['__page-css']=$row['trd_css'];
			$_POST['__page-js']=$row['trd_js'];
			$_POST['__page-param']=$row['trd_param'];
			
			$rper=$sql->query("
						SELECT per_id,pfp_value 
						FROM pagefile_permissions 
						JOIN pagefile ON trd_id=pfp_trd_id 
						JOIN permissions ON per_id=pfp_per_id WHERE pfp_trd_id={$row['trd_id']}");
			if($rper){
				while($rowper=$sql->fetch_assoc($rper)){
					if(isset($_POST['__page-privileges'][$rowper['per_id']])){
						$_POST['__page-privileges'][$rowper['per_id']][1]=$rowper['pfp_value'];
					}
				}
			}
		}
	}
	$r=$sql->query("SELECT pfl_trd_id,pfl_lng_id,pfl_value FROM pagefile_language WHERE pfl_trd_id=".((int)$_POST['pf_id']).";");
	if($r){
		while($row=$sql->fetch_assoc($r)){
			if(!isset($_POST['__page-name'])){$_POST['__page-name']=array();}
			$_POST['__page-name'][$row['pfl_lng_id']]=$row['pfl_value'];
		}
	}
	
}elseif($_POST['method']=='add'){
	$r=$sql->query("SELECT trd_directory FROM pagefile WHERE trd_id='{$_POST['pf_id']}';");
	if($r!==false){
		if($row=$sql->fetch_assoc($r)){
			$_POST['__page-directory']=$row['trd_directory']."/";
		}
	}
}
?>

<div>
	<div id="__jx_title"><?php echo ($_POST['method']=='add'?'Add a new page':($_POST['method']=='edit'?"Edit an existing page":''));?></div>
	<div id="__jx_body">
		
		<form action="<?php echo $_POST['page'];?>" method="post" id="frmMain" style="margin:0;padding:0">
		<input type="hidden" name="__method" value="<?php echo $_POST['method'];?>" />
		<input type="hidden" name="__parent" value="<?php echo $_POST['pf_id'];?>" />
		<input type="hidden" name="line" value="<?php echo (isset($_POST['line']) && $_POST['line']?"1":"0");?>" />
		<div class="cpanel_form">
			<div>
				<h1>Page name</h1>
				<?php
				if(!isset($_POST['__page-name']) || sizeof($_POST['__page-name'])==0){
					if($defaultLanguage!=0){
						foreach($arrLanguages as $langK=>$langV){
							echo "<div class=\"btn-set\" style=\"margin:1px;\">
								<span style=\"min-width:80px;\">{$langV[1]}</span>
								<input type=\"text\" style=\"-webkit-box-flex:1;-moz-box-flex:1;-webkit-flex:1;-ms-flex:1;flex:1;\" name=\"__page-name[$langK]\" /></div>";
						}
					}
				}else{
					foreach($_POST['__page-name'] as $langK=>$langV){
						/*Drop on unknown lang*/
						if(array_key_exists($langK,$arrLanguages)){
							echo "<div class=\"btn-set\" style=\"margin:1px;\">
								<span style=\"min-width:80px;\">{$arrLanguages[$langK][1]}</span>
								<input type=\"text\" style=\"-webkit-box-flex:1;-moz-box-flex:1;-webkit-flex:1;-ms-flex:1;flex:1;\" name=\"__page-name[$langK]\" value=\"$langV\" /></div>";
						}
					}
				}
				?>
			</div>
			<div>
				<h1>Page directory</h1>
				<div class="btn-set">
					<input type="text" name="__page-directory" style="-webkit-box-flex:1;-moz-box-flex:1;-webkit-flex:1;-ms-flex:1;flex:1;" value="<?php echo !array_key_exists('__page-directory',$_POST)?"":$_POST['__page-directory'];?>" />
					<label class="btn-checkbox"><input type="checkbox" name="__page-active" <?php echo ($_POST['__page-active']==1?"checked=\"checked\"":"");?> /><span> Active</span></label>
					<label class="btn-checkbox"><input type="checkbox" name="__page-visible" <?php echo ($_POST['__page-visible']==1?"checked=\"checked\"":"");?> /><span> Visible</span></label>
				</div>
			</div>
			
			
			<?php 
				$arr_htmlheaders=array(
					"HTML Headers"=>array(
						0=>array("Incude","s"),
						1=>array("Exclude","s"),
						
						"title"=>false,
					),
				);
				$arr_bodycontents=array(
					"Content"=>array(
						10=>array("Default file","s"),
						20=>array("Content editor","s"),
						"title"=>false,
					),
					"Customized"=>array(
						30=>array("Custom file","i","__page-loader","File name inside `website-contents` directory"),
						"title"=>false,
					),
					"Redirect"=>array(
						40=>array("Forward","i","__page-forward","Page ID"),
						"title"=>false,
					)
				);
			?>
			<div>
				<h1>HTML Headers</h1>
				<div>
					<?php 
						foreach($arr_htmlheaders as $k=>$v){
							echo "<div class=\"btn-set\" style=\"margin:2px 0px;\">";
							if(isset($v['title']) && $v['title']==true){echo "<span style=\"min-width:164px;\">$k</span>";}
							foreach($v as $tk=>$tv){
								if($tk!=="title"){
								echo "<label class=\"btn-checkbox\">
									<input type=\"radio\" name=\"__page-header\" 
										".(isset($_POST['__page-header']) && $_POST['__page-header']==$tk?"checked=\"checked\"":"")." value=\"$tk\" />
									<span style=\"min-width:130px;\"> {$tv[0]}</span></label>";
									if($tv[1]=="i"){
										echo "<input type=\"text\" placeholder=\"{$tv[3]}\" style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;\" name=\"{$tv[2]}\" 
											value=\"{$_POST[$tv[2]]}\" />";
									}
								}
							}
							echo "</div>";
						}
					?>
					<span style="font-size:0.8em;color:#777;margin:10px;">Include or exlcude `/admin/forms/header.php:/admin/forms/footer.php` files</span>
				</div>
			</div>
			<div>
				<h1>Contents</h1>
				<div>
					<?php 
						foreach($arr_bodycontents as $k=>$v){
							echo "<div class=\"btn-set\" style=\"margin:2px 0px;\">";
							if(isset($v['title']) && $v['title']==true){echo "<span style=\"min-width:164px;\">$k</span>";}
							foreach($v as $tk=>$tv){
								if($tk!=="title"){
								echo "<label class=\"btn-checkbox\">
									<input type=\"radio\" name=\"__page-contents\" 
										".(isset($_POST['__page-contents']) && $_POST['__page-contents']==$tk?"checked=\"checked\"":"")." value=\"$tk\" />
									<span style=\"min-width:130px;\"> {$tv[0]}</span></label>";
									if($tv[1]=="i"){
										echo "<input type=\"text\" placeholder=\"{$tv[3]}\" style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;\" name=\"{$tv[2]}\" 
											value=\"{$_POST[$tv[2]]}\" />";
									}
								}
							}
							echo "</div>";
						}
					?>
					<span style="font-size:0.8em;color:#777;margin:10px;">Default files location `/website-contents/[fileid].php</span>
				</div>
			</div>
			<div>
				<h1>Custome included files in header</h1>
				<div>
					<div class="btn-set" style="margin:2px 0px;">
						<span style="min-width:164px;">CSS</span><input type="text" value="<?php echo $_POST['__page-css'];?>" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" placeholder="File path [:]" name="__page-css" />
					</div>
					<div class="btn-set">
						<span style="min-width:164px;">Javascript</span><input type="text" value="<?php echo $_POST['__page-js'];?>" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" placeholder="File path [:]" name="__page-js" />
					</div>
					
				</div>
			</div>
			<div>
				<h1>Settings</h1>
				<div>
					<div class="btn-set" style="margin:2px 0px;">
						<span style="min-width:164px;">Parameters</span><input type="text" value="<?php echo $_POST['__page-param'];?>" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" placeholder="Parameters [:]" name="__page-param" />
					</div>
					
				</div>
			</div>
			
			<div>
				<h1>Permission</h1>
				<div>
					
					<table class="bom-table hover">
					<thead>
						<tr>
							<td class="p rwicon" data-colid="1" data-state="0" style="width: 20px">&#xe62e;</td>
							<td class="b rwicon" data-colid="2" data-state="0" style="width: 20px">&#xe634;</td>
							<td class="g rwicon" data-colid="3" data-state="0" style="width: 20px">&#xe602;</td>
							<td class="r rwicon" data-colid="4" data-state="0" style="width: 20px">&#xe638;</td>
							<td>Permission name</td>
						</tr>
					</thead>
					<tbody>
					<?php 

						if(isset($_POST['__page-privileges']) && is_array($_POST['__page-privileges'])){
							foreach($_POST['__page-privileges'] as $pK=>$pV){
								// Translate premission tags
								$temp=str_pad(decbin($pV[1]),4,"0",STR_PAD_LEFT);
								
								echo "<tr class=\"per_chk\">";
								echo "<td><label><input data-colid=\"1\" name=\"__page-privileges[{$pK}][r]\" ".((int)$temp[0]==1?"checked=\"checked\"":"")." type=\"checkbox\" /><span></span></label></td>";
								echo "<td><label><input data-colid=\"2\" name=\"__page-privileges[{$pK}][a]\" ".((int)$temp[1]==1?"checked=\"checked\"":"")." type=\"checkbox\" /><span></span></label></td>";
								echo "<td><label><input data-colid=\"3\" name=\"__page-privileges[{$pK}][e]\" ".((int)$temp[2]==1?"checked=\"checked\"":"")." type=\"checkbox\" /><span></span></label></td>";
								echo "<td><label><input data-colid=\"4\" name=\"__page-privileges[{$pK}][d]\" ".((int)$temp[3]==1?"checked=\"checked\"":"")." type=\"checkbox\" /><span></span></label></td>";
								echo "<th>{$pV[0]}</th>";
								echo "</tr>";
							}
						}else{
							echo "Permissions list is empty, edit it <a href=\"m_permissions/\" target=\"_blank\">here</a>";
						}
					?>
					</tbody>
					</table>
				</div>
			</div>
			
			<button type="submit" style="display:none;"></button>
			
		</div>
		</form>
	</div>
	<div id="__jx_footer">
		<div class="btn-set" style="justify-content:flex-end;padding:0px;">
			<button type="button" id="jQformsubmit"><?php echo ($_POST['method']=='add'?'Add pagefile':($_POST['method']=='edit'?'Edit pagefile':''));?></button>
			<button type="button" class="jQclosepopup">Cancel</button>
		</div>
	</div>
	
</div>
