<?php 
if(!isset($_POST['method'],$_POST['pr_id'],$_POST['page'])){exit;}
include_once "../include/header.php";
$_POST['pr_id']=(int)$_POST['pr_id'];

$_POST['per_title']="";
$_POST['per_description']="";

if($_POST['method']=='edit'){
	$r=$sql->query("select per_title,per_description,per_order from permissions where per_id='{$_POST['pr_id']}';");
	if($r && $row=$sql->fetch_assoc($r)){
		$_POST['per_title']=$row['per_title'];
		$_POST['per_description']=$row['per_description'];
		$_POST['per_order']=$row['per_order'];
	}else{
		echo "<h1 style=\"padding:10px;\">Unable to find selected permission!<h1>";
		exit;
	}
}
?>
<div>
	<div id="__jx_title"><?php echo ($_POST['method']=='add'?'Add a new permission':'Edit permission');?></div>
	<div id="__jx_body">
		<form action="<?php echo $_POST['page'];?>" method="post" id="frmPermission" style="margin:0;padding:0">
			<input type="hidden" name="__method" value="<?php echo $_POST['method'];?>" />
			<input type="hidden" name="__id" value="<?php echo $_POST['pr_id'];?>" />
			<input type="hidden" name="line" value="<?php echo (isset($_POST['line']) && $_POST['line']?"1":"0");?>" />
			<div class="cpanel_form">
				<div>
					<h1>Permission name</h1>
					<div class="btn-set">
						<input type="text" name="__per-title" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="<?php echo $_POST['per_title'];?>" />
					</div>
				</div>
				<div>
					<h1>Description</h1>
					<div class="btn-set">
						<input type="text" name="__per-description" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="<?php echo $_POST['per_description'];?>" />
					</div>
				</div>
				<div>
					<h1>Permission Level</h1>
					<div class="btn-set">
						<input type="text" name="__per-order" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="<?php echo $_POST['per_order'];?>" />
					</div>
				</div>
				<button type="submit" style="display:none;"></button>
			</div>
		</form>
	</div>
	<div id="__jx_footer">
		<div class="btn-set" style="justify-content:flex-end;padding:0px;">
			<button type="button" id="jQaddpermissionsbutton"><?php echo ($_POST['method']=='add'?'Add permission':($_POST['method']=='edit'?'Edit permission':''));?></button>
			<button type="button" class="jQclosepopup">Cancel</button>
		</div>
	</div>
</div>