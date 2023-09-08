<?php 
if(!isset($_POST['method'],$_POST['ln_id'],$_POST['page'])){exit;}
include_once "../include/header.php";
$_POST['ln_id']=(int)$_POST['ln_id'];

$_POST['lng_name']="";
$_POST['lng_symbol']="";
$_POST['lng_icon']="";
$_POST['lng_default']=0;
$_POST['lng_direction']=0;
$_POST['lng_css']="";

if($_POST['method']=='edit'){
	$r=$sql->query("SELECT lng_symbol,lng_name,lng_default,lng_direction,lng_icon,lng_css
					FROM languages WHERE lng_id='{$_POST['ln_id']}';");
	if($r && $row=$sql->fetch_assoc($r)){
		$_POST['lng_name']=$row['lng_name'];
		$_POST['lng_symbol']=$row['lng_symbol'];
		$_POST['lng_icon']=$row['lng_icon'];
		$_POST['lng_default']=$row['lng_default'];
		$_POST['lng_direction']=$row['lng_direction'];
		$_POST['lng_css']=$row['lng_css'];
	}else{
		echo "<h1 style=\"padding:10px;\">Unable to find selected language!<h1>";
		exit;
	}
}
?>
<div>
	<div id="__jx_title"><?php echo ($_POST['method']=='add'?'Add a new language':'Edit language');?></div>
	<div id="__jx_body">

		<form action="<?php echo $_POST['page'];?>" method="post" id="frmLanguages" style="margin:0;padding:0">
			<input type="hidden" name="__method" value="<?php echo $_POST['method'];?>" />
			<input type="hidden" name="__id" value="<?php echo $_POST['ln_id'];?>" />
			<input type="hidden" name="line" value="<?php echo (isset($_POST['line']) && $_POST['line']?"1":"0");?>" />
			<div class="cpanel_form">
				<div>
					<h1>Language name</h1>
					<div class="btn-set">
						<input type="text" name="__lng-name" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="<?php echo $_POST['lng_name'];?>" />
						<label class="btn-checkbox"><input type="checkbox" name="__lng-default" <?php echo ($_POST['lng_default']==1?"checked=\"checked\"":"");?> /><span> Default</span></label>
					</div>
				</div>
				<div>
					<h1>Language details</h1>
					<div class="btn-set">
						<span style="width:110px;">Symbol</span>
						<input type="text" name="__lng-symbol" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="<?php echo $_POST['lng_symbol'];?>" />
					</div>
				</div>
				<div>
					<div class="btn-set">
						<span style="width:110px;">Direction</span>
						<label class="btn-checkbox"><input type="radio" name="__lng-direction" <?php echo ($_POST['lng_direction']==0?"checked=\"checked\"":"");?> value="0" /><span> Left to Right</span></label>
						<label class="btn-checkbox"><input type="radio" name="__lng-direction" <?php echo ($_POST['lng_direction']==1?"checked=\"checked\"":"");?> value="1" /><span> Right to Left</span></label>
					</div>
				</div>
				<div>
					<h1>Parameters</h1>
					<div class="btn-set">
						<input type="text" name="__lng-icon" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="<?php echo $_POST['lng_icon'];?>" />
					</div>
				</div>
				<div>
					<h1>Include files in header</h1>
					<div class="btn-set">
						<span style="width:110px;">CSS</span>
						<input type="text" name="__lng-css" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="<?php echo $_POST['lng_css'];?>" />
					</div>
				</div>
				<button type="submit" style="display:none;"></button>
			</div>
		</form>
	</div>
	
	<div id="__jx_footer">
		<div class="btn-set" style="justify-content:flex-end;padding:0px;">
			<button type="button" id="jQaddlanguageform"><?php echo ($_POST['method']=='add'?'Add language':($_POST['method']=='edit'?'Edit language':''));?></button>
			<button type="button" class="jQclosepopup">Cancel</button>
		</div>
	</div>
	
	
</div>