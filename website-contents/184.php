<?php
	//Bulk attendance insersion
	exit;


	$result=false;
	$cont=true;
	$terminal=array();
	$terminal[]="!Terminal";
	if(isset($_POST['id'],$_POST['from'],$_POST['hour'])){
		
		$hrs=((float)$_POST['hour'])*60;
		$time_from=false;
		$time__end=false;
		
		if(preg_match("/^([0-9]{4})-([0-9]{0,2})-([0-9]{0,2}) ([0-9]{0,2}):([0-9]{0,2})$/",$_POST['from'],$match)){
			$time_from=mktime($match[4],$match[5],0,$match[2],$match[3],$match[1]);
		}
		
		$time__end=strtotime(" +$hrs Minute",$time_from);
		
		
		if(!$time_from){
			$terminal[]="Selected date/time isn't valid";
			$cont=false;
		}
		if($hrs<7){
			$terminal[]="Hours must be more than 7";
			$cont=false;	
		}
		
		if($cont){
			$terminal[]="\t#Executing command";
			$emps=explode("\n",$_POST['id']);
			foreach($emps as $id){
				$tid=(int)$id;
				if($tid>0){
					$q="
						INSERT INTO labour_track (ltr_id, ltr_ctime,ltr_shift_id,ltr_usr_id,ltr_signer,ltr_type,ltr_prt_id,ltr_comments) 
						VALUES (NULL, '".date("Y-m-d H:i:s",$time_from)."', NULL, $tid, 1, 1, 1, NULL),(NULL, '".date("Y-m-d H:i:s",$time__end)."', NULL, $tid, 1, 3, 1, NULL);";
					$r=$sql->query($q);
					$terminal[]="\t".$tid."\t".($r?"OK":"FAIL");
				}
			}
			
		}
	}
?>
<form action="<?php echo $pageinfo['directory']; ?>" method="POST">
	<div class="btn-set">
		<span>Date/Time</span>
		<span>From</span>
		<input placeholder="yyyy-mm-dd hh:mm" type="text" name="from" value="<?php echo isset($_POST['from'])?$_POST['from']:"";?>">
		<span>Hours</span><input type="text" placeholder="hours" name="hour" value="<?php echo isset($_POST['hour'])?$_POST['hour']:"";?>">
		
	</div><br />
	<div class="btn-set">
	</div>
	<div class="btn-set">
		<textarea placeholder="Employees list" style="width:100%;height:200px;" name="id"><?php echo isset($_POST['id'])?$_POST['id']:"";?></textarea>
	</div>
	<div class="btn-set">
		<button>Submit</button>
	</div>
	<br /><br />
	<div class="btn-set">
		<textarea style="width:100%;height:400px;" readonly=""><?php
		echo implode("\n",$terminal);
		?></textarea>
	</div>
</form>
