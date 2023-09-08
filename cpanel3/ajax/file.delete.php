<?php 
	include_once "../include/header.php";
	$_POST['pf_id']	=(int)$_POST['pf_id'];
	$_POST['line']	=isset($_POST['line']) && (int)$_POST['line']==1?1:0;
	
	$pageinfo=false;
	$r=$sql->query("SELECT trd_directory,trd_id FROM pagefile WHERE trd_id='{$_POST['pf_id']}';");
	if($r && $row=$sql->fetch_assoc($r)){
		$pageinfo=array();
		$pageinfo['id']=$row['trd_id'];
		$pageinfo['directory']=$row['trd_directory'];
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
		</div>
	<?php exit;}
?>
<div>
	<div id="__jx_title">Delete pagefile confirmation</div>
	<div id="__jx_body">
		<form id="frmDeletePageFile">
			<input type="hidden" name="pf_id" value="<?php echo $pageinfo['id'];?>" />
			<input type="hidden" name="line" value="<?php echo $_POST['line'];?>" />
			<div class="cpanel_form">
				<div>
				
					<h1>Are you sure you want to delete pagefile:</h1>
					<div class="btn-set">
						<input type="text" readonly="readonly" style="width:60px;" value="<?php echo $pageinfo['id'];?>" />
						<input type="text" readonly="readonly" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" 
							value="/<?php echo $pageinfo['directory'];?>" />
						<label class="btn-checkbox"><input type="checkbox" name="file_delete_method" /><span> Cascade delete</span></label>
					</div>
					
					<div style="font-size:0.8em;color:#777;margin:10px;">In cascade delete mode all sub-directories will be removed, otherwise all sub-directories will be moved one level up
					</div>
				</div>
				<button type="submit" style="display:none;"></button>
			</div>
		</form>
	</div>
	<div id="__jx_footer">
		<div class="btn-set" style="justify-content:flex-end;padding:0px;">
			<button type="button" id="jQdeleteformbutton">Delete</button>
			<button type="button" class="jQclosepopup">Cancel</button>
		</div>
	</div>
</div>