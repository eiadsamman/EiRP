<?php 
	include_once "../include/header.php";
	if(isset($_POST['method']) && $_POST['method']=='update.settings'){
		if($settings->settings_write($_POST)==true){
			echo "1";exit;
		}else{
			echo "0";exit;
		}
	}
	
	include_once "../include/html.header.php";
	?>
	<h1 style="font-size:1.2em">CPanel Settings</h1>
	
	<form id="frmSettings">
		<input type="hidden" name="method" value="update.settings" />
		<div class="btn-set">
			<button type="submit">Save settings</button>
			<button type="reset">Reset</button>
		</div>
		<?php 
		$settings_form=$settings->settings_form();
		foreach($c__settings as $__lvl1_k=>$__lvl1_v){
			echo "<div style=\"margin-top:20px;\"><table class=\"bom-table hover\">";
			echo "<thead><tr><td colspan=\"2\" style=\"text-transform:capitalize;border-left:solid 3px #06c;font-weight:bold\">$__lvl1_k</td></tr></thead><tbody>";
			foreach($__lvl1_v as $__lvl2_k=>$__lvl2_v){
				$s= $settings_form[$__lvl1_k][$__lvl2_k]['attrib'];
				$__lvl2_v=htmlentities($__lvl2_v);
				echo "<tr><td nowrap=\"nowrap\" style=\"width:200px;\">".$__lvl2_k."</td><td><div class=\"btn-set\">";
				if($s=="password"){
					echo "<input style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\" type=\"password\" name=\"$__lvl1_k".'['.$__lvl2_k.']'."\" value=\"".str_repeat("*",6)."\" />";
				}elseif($s=="boolean"){
					$__lvl2_v=trim(strtolower($__lvl2_v));
					$__lvl2_v=($__lvl2_v=="true" || $__lvl2_v=="1"?"true":"false");
					echo "<input style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\" type=\"text\" name=\"$__lvl1_k".'['.$__lvl2_k.']'."\" value=\"".$__lvl2_v."\" />";
				}elseif($s=="readonly"){
					echo "<input style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\" type=\"text\" name=\"$__lvl1_k".'['.$__lvl2_k.']'."\" readonly=\"readonly\" value=\"".$__lvl2_v."\" />";
				}elseif($s=="sqllib"){
					echo "<select style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\" name=\"$__lvl1_k".'['.$__lvl2_k.']'."\">";
					foreach($settings->settings_sql_lib() as $sqllib){
						echo "<option value=\"$sqllib\" ".($__lvl2_v==$sqllib?" selected=\"selected\"":"").">$sqllib</option>";
					}
					echo "</select>";
				}elseif($s=="timezone"){
					$arrOutput = array();
					foreach(DateTimeZone::listIdentifiers() as $zone){
						if(strstr($zone,"/")){
							$cont=substr($zone,0,strpos($zone,"/"));
							$count=substr($zone,strpos($zone,"/")+1);
							if(!array_key_exists($cont,$arrOutput)){$arrOutput[$cont]=array();}
							$arrOutput[$cont][]=$count;
						}
					}
					echo "<select style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\" name=\"$__lvl1_k".'['.$__lvl2_k.']'."\">";
					foreach($arrOutput as $contk=>$contv){
						echo "<optgroup label=\"$contk\">";
						foreach($contv as $count){
							$_tzt=$contk.'/'.$count;
							echo "<option value=\"".urlencode($contk.'/'.$count)."\"".($__lvl2_v==$_tzt?" selected=\"selected\"":"").">$count</option>";
						}
						echo "</optgroup>";
					}
					echo "</select>";
				}elseif($s=="VERSION"){
					echo "<span style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\">".VERSION."</span>";
					
				}elseif($s=="LICENSE"){
					echo "<span style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\">".LICENSE."</span>";
					
				}else{
					echo "<input style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\" type=\"text\" name=\"$__lvl1_k".'['.$__lvl2_k.']'."\" value=\"".$__lvl2_v."\" />";
				}
				echo "</div></td></tr>";
			}
			echo "</tbody></table></div>";
		}?>
	</form>
	<script>
	$(document).ready(function(e) {
		$("#frmSettings").on('submit',function(e){
			e.preventDefault();
			var $this=$(this),
				_serz=$this.serializeArray()
			$this.find("input,select,button").prop("disabled",true);
			popup.plain("<div style=\"margin:15px;text-align:center;\">Saving settings, please wait</div>");
			
			$.ajax({
				url:"m_settings/index.php",
				type:"POST",
				data:_serz
			}).done(function(output){
				$this.find("input,select,button").prop("disabled",false);
				popup.hide();
				if(output=="1"){
					messagesys.success("Settings saved successfully");
				}else{
					messagesys.failure("Saveing settings failed");
				}
			}).fail(function(a,b,c){
				if(b=="abort"){return false;}
				$this.find("input,select,button").prop("disabled",false);
				popup.hide();
				messagesys.failure(c);
				
			});
			return false;
		});
	});
	</script>
<?php
	include_once "../include/html.footer.php";
?>
	