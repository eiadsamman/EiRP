<?php
	include_once "../include/header.php";

	if(isset($_POST['fetch-child'])){
		$_POST['fetch-child']=(int)$_POST['fetch-child'];
		if($r=$sql->query("SELECT 
								trd_id,trd_directory,pfl_value ,IFNULL(_childsnumber, 0) AS _childsnumber
							FROM 
								pagefile LEFT JOIN
									(SELECT
										lng_default,lng_id,pfl_value,pfl_trd_id
									FROM
										pagefile_language JOIN languages ON lng_id=pfl_lng_id
									WHERE
										lng_default=1
									) AS _a ON _a.pfl_trd_id=trd_id
								LEFT JOIN
									(
										SELECT COUNT(trd_id) AS _childsnumber,trd_parent AS _childsparent
										FROM pagefile
										GROUP BY trd_parent
									) AS _childs ON _childs._childsparent = trd_id
							WHERE trd_parent={$_POST['fetch-child']} ORDER BY trd_zorder;")){
			while($row=$sql->fetch_assoc($r)){
				echo "<span data-expanded=\"0\" data-expandable=\"".($row['_childsnumber']>0?"1":"0")."\" 
					data-pf_id=\"{$row['trd_id']}\" data-pf_dir=\"/{$row['trd_directory']}\" 
					".($row['_childsnumber']>0?" class=\"expand\"":"")."><i>{$row['pfl_value']}</i></span>";
			}
		}
		exit;
	}

	$_POST['pf_id']	=(int)$_POST['pf_id'];
	$_POST['line']	=isset($_POST['line']) && (int)$_POST['line']==1?1:0;
	
	$pageinfo=false;
	$r=$sql->query("
		SELECT 
			_c.trd_directory,_c.trd_id ,IFNULL(_p.trd_id,0) AS parent_trd_id,_p.trd_directory AS parent_trd_directory
		FROM 
			pagefile AS _c 
				LEFT JOIN pagefile AS _p ON _p.trd_id=_c.trd_parent
		WHERE 
			_c.trd_id='{$_POST['pf_id']}';");
	if($r && $row=$sql->fetch_assoc($r)){
		$pageinfo=array();
		$pageinfo['id']=$row['trd_id'];
		$pageinfo['directory']=$row['trd_directory'];
		$pageinfo['parent_id']=$row['parent_trd_id'];
		$pageinfo['parent_directory']=$row['parent_trd_directory'];
	}
	if(!$pageinfo){?>
		<div class="cpanel_form">
			<h1 class="header">Delete pagefile</h1>
			<div>
				<h1 style="color:#f03">Required page `<?php echo $_POST['pf_id'];?>` not found</h1>
			</div>
			<div class="btn-set" style="margin:20px 0px;justify-content:center;padding:0px;">
				<input type="button" class="jQclosepopup" value="Cancel" />
			</div>
		</div><?php
		exit;
	}
?>
	
<div>
	<div id="__jx_title">Move pagefile</div>
	<div id="__jx_body">
		<form id="frmMovePageFile">
			<input type="hidden" name="pf_id" value="<?php echo $pageinfo['id'];?>" />
			<input type="hidden" name="line" value="<?php echo $_POST['line'];?>" />
			<div class="cpanel_form">
				<div>
					<h1>Source pagefile</h1>
					<div class="btn-set">
						<input type="text" readonly="readonly" style="width:60px;" value="<?php echo $pageinfo['id'];?>" />
						<input type="text" readonly="readonly" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" 
							value="/<?php echo $pageinfo['directory'];?>" />
					</div>
				</div>
				<div>
					<div class="btn-set">
						<span>Location</span><input type="text" readonly="readonly" style="width:60px;" value="<?php echo $pageinfo['parent_id'];?>" />
						<input type="text" readonly="readonly" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" 
							value="/<?php echo $pageinfo['parent_directory'];?>" />
					</div>
				</div>
				<div>
					<h1>Destination pagefile</h1>
					<div class="btn-set">
						<input id="jQnewPF_id" type="text" name="new_pf_id" readonly="readonly" style="width:60px;" value="0" />
						<input id="jQnewPF_dir" type="text" readonly="readonly" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="/" />
					</div>
					<div id="jQpagefiletree">
						<?php
							echo "<span data-ignore=\"1\" data-pf_id=\"0\" data-pf_dir=\"/\"><i>{$_SERVER['HTTP_SYSTEM_ROOT']}</i></span>";
							if($r=$sql->query("SELECT 
													trd_id,trd_directory,pfl_value ,IFNULL(_childsnumber, 0) AS _childsnumber
												FROM 
													pagefile LEFT JOIN
														(SELECT
															lng_default,lng_id,pfl_value,pfl_trd_id
														FROM
															pagefile_language JOIN languages ON lng_id=pfl_lng_id
														WHERE
															lng_default=1
														) AS _a ON _a.pfl_trd_id=trd_id
													LEFT JOIN
														(
															SELECT COUNT(trd_id) AS _childsnumber,trd_parent AS _childsparent
															FROM pagefile
															GROUP BY trd_parent
														) AS _childs ON _childs._childsparent = trd_id
												WHERE trd_parent=0 ORDER BY trd_zorder;")){
								echo "<div>";
								while($row=$sql->fetch_assoc($r)){
									echo "<span data-expanded=\"0\" data-expandable=\"".($row['_childsnumber']>0?"1":"0")."\" 
										data-pf_id=\"{$row['trd_id']}\" data-pf_dir=\"/{$row['trd_directory']}\" 
										".($row['_childsnumber']>0?" class=\"expand\"":"")."><i>{$row['pfl_value']}</i></span>";
								}
								echo "</div>";
							}
							
						?>
						<p></p>
					</div>
				</div>
				<button type="submit" style="display:none"></button>
			</div>
		</form>
	</div>
	<div id="__jx_footer">
		<div class="btn-set" style="justify-content:flex-end;padding:0px;">
			<button type="button" id="jQmoveformbutton">Move</button>
			<button type="button" class="jQclosepopup">Cancel</button>
		</div>
	</div>
</div>

		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		