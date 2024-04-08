<?php
if(isset($_POST['suspend'])){
	$id=(int)$_POST['id'];
	$app->db->autocommit(false);
	$r=true;
	
	$r&=$app->db->query("UPDATE labour SET lbr_resigndate=NOW() WHERE lbr_id={$id}");
	
	if($r){
		$app->db->commit();
		echo "1";
	}else{
		$app->db->rollback();
		echo "0";
	}
	exit;
}
$perpage=50;
if(isset($_POST['fetch']) && isset($_POST['pos'])){
	

	$_POST['pos']=(int)$_POST['pos'];
	$build=isset($_POST['build']) && $_POST['build']=='1'?true:false;
	
	$month=false;
	if(isset($_POST['fetch']) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['fetch'],$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			$month=mktime(0,0,0,$match[2],$match[3],$match[1]);
		}
	}
	if(!$month){die("");}
	
	$pagefile_display=$fs(108)->dir;
	
	$arrMonthDetails=array(
		"total"=>0,
		"working"=>0,
		"holiday"=>0,
	);

	for($i=1;$i<=(date("j",$month) > date("j")?date("j"):date("j",$month));$i++){
		$arrMonthDetails['total']++;
		$ttt=mktime(0,0,0,date("m",$month),$i,date("Y",$month));
		if(date("w",$ttt)==5){
			$arrMonthDetails['holiday']++;
		}else{
			$arrMonthDetails['working']++;
		}
	}
	$arrdays=array();
	for($i=1;$i<=date("t",$month);$i++){
		$ttt=mktime(0,0,0,date("m",$month),$i,date("Y",$month));
		$arrdays[$i]=array(false,date("w",$ttt),date("D",$ttt));
	}
	
	
	if($build){
		echo "<thead><tr><td>ID</td><td></td><td>Name</td>";
		foreach($arrdays as $k=>$v){
			echo "<td".($v[1]==5?" class=\"__holiday_major\"":"").">$k</td>";
		}
		echo "</tr>";
		echo "<tr><td colspan=\"3\"></td>";
		foreach($arrdays as $k=>$v){
			echo "<td".($v[1]==5?" class=\"__holiday_major\"":"").">{$v[2]}</td>";
		}
		echo "</tr>";
		echo "</thead><tbody>";
	}

	if($r=$app->db->query("
		SELECT 
			lbr_id,CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS empname
		FROM 
			labour JOIN users ON usr_id=lbr_id
		WHERE 
			lbr_resigndate IS NULL
		LIMIT ".($perpage*$_POST['pos']).",$perpage
		")){
		while($row=$r->fetch_assoc()){
			echo "<tr>";
			echo "<td>".$row['lbr_id']."</td><td></td><td>".$row['empname']."</td>";
			
			
			foreach($arrdays as $k=>$v){
				$arrdays[$k][0]=false;
			}
			$ratt=$app->db->query("
					SELECT 
						UNIX_TIMESTAMP(ltr_ctime) AS ltr_ctime
					FROM
						labour_track
					WHERE 
						ltr_usr_id={$row['lbr_id']} AND 
						ltr_type=1 AND 
						ltr_ctime>='".date("Y-m-1 00:00:00",$month)."' AND 
						ltr_ctime<='".date("Y-m-t 24:00:00",$month)."'");
			
			if($ratt){
				while($rowatt=$ratt->fetch_assoc()){
					$tempd=date("j",$rowatt['ltr_ctime']);
					if(isset($arrdays[$tempd][0]) && $arrdays[$tempd][0]==false){
						$arrdays[$tempd][0]=date("H:i",$rowatt['ltr_ctime']);
					}
				}
			}
			foreach($arrdays as $v){
				echo "<td".($v[1]==5?" class=\"__holiday_major\"":"").">{$v[0]}</td>";
			}
					
			
			
			echo "</tr>";
		}
	}
	if($build){echo "</tbody>";}
	exit;
}

if(isset($_POST['detailed-report']) && isset($_POST['usr_id'])){
	$month=false;
	if(preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['detailed-report'],$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			$month=mktime(0,0,0,$match[2],$match[3],$match[1]);
		}
	}
	if(!$month){die("");}
	include("admin/class/attendance-list.php");
	$attendance=new AttendanceList();
	$attendance->getAttendaceList((int)$_POST['usr_id'],date("Y-m-d",$month),true);
	echo $attendance->PrintTable(true);
	exit;
}



?>

<div class="btn-set" style="margin-bottom:5px;">
	<span style="min-width:160px;">Report for</span>
	<input id="jQmonth" value="" data-slodefaultid="" data-slo="MONTH" type="text" />
</div>
<style>
.__holiday_major{
	background-color:#eee;
}
</style>
<table class="bom-table hover" style="margin-bottom:5px;" id="jQoutput">
</table>

<script>
$(document).ready(function(e) {
	var $ajaxload=null;
	var _regdate=null;
	var _pos=0,_loading=false;
	var fetch=function(value,append,build){
		_regdate=value;
		if($ajaxload!=null){$ajaxload.abort();}
		if(append==undefined){append=false;}
		if(build==undefined){build=false;}
		_loading=true;
		messagesys.success("Loading...");
		$ajaxload=$.ajax({
			type:"POST",
			url:"<?php echo $fs()->dir;?>",
			data:{'fetch':value,'pos':_pos,'build':(build?'1':'0')}
		}).done(function(data){
			if(data==""){
				return false;
			}else{
				if(append){
					$("#jQoutput").append(data);
				}else{
					$("#jQoutput").html(data);
				}
			}
		}).always(function(){
			_loading=false;
		});
	}
	$("#jQoutput").on('click','.jQsuspend',function(){
		var id=$(this).attr('data-id');
		var $this=$(this);
		$.ajax({
			url:'<?php echo $fs()->dir;?>',
			type:'POST',
			data:{'suspend':'','id':id}
		}).done(function(data){console.log(data);
			if(data=='1'){
				$this.closest("tr").remove();
				messagesys.success("Employee suspended successfully");
			}else{
				messagesys.failure("Failed to suspend employee");
			}
		});
	});
	$("#jQmonth").slo({
		onselect:function(value){
			_pos=0;
			fetch(value.key,false,true);
			value.object.blur();
		},ondeselect:function(){
			_regdate=null;
			$("#jQoutput").html("");
		}
	});
	
	$("#jQoutput").on('click',".jQdr",function(){
		if(_regdate==null){return false;}
		var $this=$(this);
		popup.content("Loading").show();
		var $ajax=$.ajax({
			type:"POST",
			url:"<?php echo $fs()->dir;?>",
			data:{'detailed-report':_regdate,'usr_id':$this.attr("data-usrid")}
		}).done(function(data){
			if(data==""){
				popup.content("No entries found!").show();
			}else{
				popup.content(data).show();
			}
		});
	});
	
	
	$(window).scroll(function() {
		if($(window).scrollTop() == $(document).height() - $(window).height()) {
			if(_loading==true){return false;}
			if(_regdate==null){return false;}
			_pos++;
			fetch(_regdate,true);
			_loading=true;
		}
	});
	
	var $div=$("<div />");
	$div.attr("id","jQpopupAttDetails");
	$(document).on('mouseover','.css_attendanceBlocks > div',function(){
		var $this=$(this);
		var idint=$(this).attr("data-clsid");
		var idcld=$(this).attr("data-clscloseid");
		if(idint!=0){
			$("div[data-clsid="+idint+"]").css({'background-color':'rgba('+$this.attr('data-clscolor')+',0.8)'});
			$div.html(
				"<div><h1>"+$this.attr("data-clsprt")+"</h1></div>"
				/*+"<div><span>OC:</span>"+idint+"-"+idcld+"</div>"*/
				+"<div><span>Start time:</span>"+$this.attr("data-clsstr")+"</div>"
				+"<div><span>Finish time:</span>"+$this.attr("data-clsfin")+"</div>"
				+"<div><span>Total time:</span>"+$this.attr("data-actual")+" - "+$this.attr("data-clstot")+"</div>"
			);
			$("div[data-clsid="+idint+"]").first().append($div);

			if($div.offset().left<100){
				$div.css({'left':'0px','border-radius':'8px 8px 8px 0px','display':'block'});
			}else{
				$div.css({'right':'100%','border-radius':'8px 8px 0px 8px','display':'block'});
			}
		}
	}).on('mouseout','.css_attendanceBlocks > div',function(){
		var $this=$(this);
		var idint=$(this).attr("data-clsid");
		if(idint!=0){
			$div.hide();
			$("div[data-clsid="+idint+"]").css({'background-color':'rgba('+$this.attr('data-clscolor')+',1)'});
		}
	});
});
</script>