<?php

if(isset($_POST['method']) && $_POST['method']=="addrate"){
	if($r=$app->db->query("INSERT INTO labour_rating 
		(lbrrtg_lbr_id,lbrrtg_editor_id,lbrrtg_type,lbrrtg_value,lbrrtg_prt_id) VALUES 
		(".((int)$_POST['emp']).",{$app->user->info->id},1,".((int)$_POST['dir']=="1"?"1":"-1").",{$app->user->account->id});"))
	{
		//Get employee rating
		$rating=0;
		if($r2=$app->db->query("
			SELECT (4*((1* COALESCE(SUM(lbrrtg_value),0) / (COALESCE(COUNT(lbrrtg_value),0) + 1)+1)/2)+1) AS rating
			FROM	labour_rating
			WHERE lbrrtg_lbr_id=".((int)$_POST['emp'])." AND lbrrtg_type=1
		")){
			if($row2=$r2->fetch_assoc()){
				$rating=$row2['rating'];
			}
		}
		echo number_format($rating,2);
	}else{
		echo "false";
	}
	exit;
}
if(isset($_POST['serial'])){
	$serial=addslashes($_POST['serial']);
	if($r=$app->db->query("
		SELECT
			usr_id AS empid,
			CONCAT_WS(' ',COALESCE(_up.usr_firstname,''),IF(NULLIF(_up.usr_lastname, '') IS NULL, NULL, _up.usr_lastname)) AS empname,
			lbr_serial,usr_images_list,lbr_resigndate,
			(4*((1* COALESCE(SUM(lbrrtg_value),0) / (COALESCE(COUNT(lbrrtg_value),0) + 1)+1)/2)+1) AS rating
		FROM
			labour 
				JOIN 
				(SELECT usr_images_list,usr_id,usr_firstname,usr_lastname FROM users) AS _up ON _up.usr_id=lbr_id
				LEFT JOIN labour_rating ON lbrrtg_lbr_id=lbr_id AND lbrrtg_type=1
		WHERE 
			(lbr_serial='{$_POST['serial']}' OR lbr_id=".((int)$_POST['serial'])." ) ".($app->user->info->id!=1?" AND usr_id!=1 ":"").";")){
		if($row=$r->fetch_assoc()){
			if(!is_null($row['empid'])){
				$rating=$row['rating'];
				echo "<tr><th id=\"css_empname\" colspan=\"2\">{$row['empname']}</th></tr>
					<tr><th align=\"center\">
						<div id=\"jQrating-btn\">
							<div id=\"jQrating-btn-up\" data-ratdir=\"1\" data-empid=\"{$row['empid']}\"></div>
							<div id=\"jQrating-btn-value\">".number_format($rating,2)."</div>
							<div id=\"jQrating-btn-down\" data-ratdir=\"-1\" data-empid=\"{$row['empid']}\"></div>
						</div>
					</th><th width=\"50%\" align=\"center\"><img src=\"users-photos/".($row['usr_images_list']=="0"?"0":"{$row['empid']}").".jpg\" style=\"width:100%;max-width:320px;min-width:200px\" /></th></tr>";
			}else{
				echo "<tr><th id=\"css_empname\">Employee not found</th></tr>";
			}
		}else{
				echo "<tr><th id=\"css_empname\">Employee not found</th></tr>";
		}
	}
	exit;
}
?>
<style>
#jQrate{
	text-align:center;
}
#jQrate > h1{
	font-size:2em;
	font-weight:normal;
}
#jQrating-btn{
	text-align:center;
	white-space:normal;
	margin:15px 0px;
	display:inline-block;
	max-width:200px;
	width:100%;
	position:relative;
	color:#fff;
	-webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}
#jQrating-btn > #jQrating-btn-up{
	display:inline-block;
	width:100%;
	height:110px;
	border:solid 2px #555;
	background-color:#fff;
	border-radius:100px 100px 0px 0px;
	position:relative;
	z-index:1;
	color:#06c;
	text-align:center;
}
#jQrating-btn > #jQrating-btn-up:after{
	display:inline-block;
	font-family:"icomoon";
	content:"\e61a";
	font-size:6em;
	position:absolute;
	font-weight:normal;
	left:0px;
	right:0px;
	bottom:50%;
	height:1px;
}
#jQrating-btn > #jQrating-btn-up:hover{
	border-color:#06c;
	z-index:2;
	color:#06c;
}


#jQrating-btn > #jQrating-btn-value{
	display:inline-block;
	width:100%;
	height:50px;
	border:solid 2px #555;
	background-color:#fff;
	position:relative;
	margin-top:-2px;
	z-index:1;
	color:#f02;
	font-size:2.2em;
	line-height:1.5em;
	color:#444;
	vertical-align:middle;
}

#jQrating-btn > #jQrating-btn-down{
	display:inline-block;
	width:100%;
	height:110px;
	border:solid 2px #555;
	background-color:#fff;
	border-radius:0px 0px 100px 100px;
	position:relative;
	margin-top:-2px;
	z-index:1;
	color:#f02;
}
#jQrating-btn > #jQrating-btn-down:after{
	display:inline-block;
	font-family:"icomoon";
	content:"\e619";
	font-size:6em;
	position:absolute;
	font-weight:normal;
	left:0px;
	right:0px;
	bottom:50%;
	height:1px;
}
#jQrating-btn > #jQrating-btn-down:hover{
	border-color:#f02;
	z-index:2;
	color:#f02;
}

#jQrating-btn.disabled > #jQrating-btn-down,#jQrating-btn.disabled > #jQrating-btn-up{
	background-color:#ddd;
	color:#888;
	border-color:#888;
	pointer-events:none;
}
#css_empname{
	color:#06c;font-size:1.3em;
	line-height:1.3em;
}
</style>
<div class="btn-set">
	<span>Employee serial</span><input type="text" id="serial" style="width:150px" value="" />
</div>
<table style="margin-top:15px;">
<tbody id="jQrate">
</tbody>
</table>
<script>
$(document).ready(function(e) {
	var $ajax=null;
	var fetch=function(){
		var $this=$("#serial");
		$this.attr("disabled","disabled");
		$.ajax({
			data:{'serial':$this.val()},
			url:"<?php echo $fs()->dir;?>",
			type:"POST"
		}).done(function(data){
			$("#jQrate").html(data);
		}).always(function(){
			$this.removeAttr("disabled").focus().select();
		}).fail(function(a,b,c){
			messagessys.failure(b+" - "+c);
		});
	}
	$("#serial").on('keydown',function(e){
		var keycode = (e.keyCode ? e.keyCode : e.which);
		if(keycode==13){
			fetch();
		}
	});
	$("#jQrate").on('click',"#jQrating-btn-up,#jQrating-btn-down",function(){
		var $this=$(this),
			dir=$this.attr("data-ratdir"),
			emp=$this.attr("data-empid");
		
		$this.parent().addClass("disabled");
		
		if($ajax!=null){$ajax.abort();}
		$ajax=$.ajax({
			url:'',
			type:"POST",
			data:{'method':'addrate','dir':dir,'emp':emp}
		}).done(function(data){
			if(data=="false"){
				messagesys.failure("Failed to add emplyee rate");
				$this.parent().removeClass("disabled");

			}else{
				$("#jQrating-btn-value").html(data);
				$this.parent().removeClass("disabled");
			}
		});
	});
	$("#serial").select().focus();
});










</script>