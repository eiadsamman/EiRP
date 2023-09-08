<?php
if($_SERVER['REQUEST_METHOD']!="POST"){exit;}
include_once "admin/class/system.php";

$output="";

if(isset($_POST['posubmit'])){
	
	$dateFrom=false;
	$dateTo=false;
	
	$onlyselection=isset($_POST['onlyselection'])?1:0;
	$displaysuspended=isset($_POST['displaysuspended'])?1:0;
	
	if(isset($_POST['dateFrom'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['dateFrom'][1],$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			$dateFrom=mktime(0,0,0,$match[2],$match[3],$match[1]);
		}
	}
	if(isset($_POST['dateTo'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['dateTo'][1],$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			$dateTo=mktime(23,59,59,$match[2],$match[3],$match[1]);
		}
	}
	

	if(!$dateFrom || !$dateTo){
		header("HTTP_X_RESPONSE: INERR");
		$output.= "Select a valid date range";
		exit;
	}
	
	
	$dateFromCompare = new DateTime(date("Y-m-d", $dateFrom));
	$date__ToCompare = new DateTime(date("Y-m-d", $dateTo));
	$dateInterval = $dateFromCompare->diff($date__ToCompare);
	if($dateInterval->days > 31){
		$output.= "Date range is too big, maximum date range is 31 days";
		exit;
	}
	if($dateFromCompare > $date__ToCompare){
		$output.= "Selected start date must be smaller than the end date";
		exit;
	}
	
	
	
	$parameters=array(
		"company"=>$USER->company->id,
		"paymethod"=>isset($_POST['paymethod'][1]) && (int)$_POST['paymethod'][1]!=0?(int)$_POST['paymethod'][1]:null,
		"section"=>isset($_POST['section'][1]) && (int)$_POST['section'][1]!=0?(int)$_POST['section'][1]:null,
		"job"=>isset($_POST['section'][1]) && (int)$_POST['job'][1]!=0?(int)$_POST['job'][1]:null,
		//"shift"=>isset($_POST['shift'][1]) && (int)$_POST['shift'][1]!=0?(int)$_POST['shift'][1]:null,
		//"workingtime"=>isset($_POST['workingtime'][1]) && (int)$_POST['workingtime'][1]!=0?(int)$_POST['workingtime'][1]:null,
		//"residence"=>null,
		//"transportation"=>null,
		"onlyselection"=>$onlyselection,
		"onlyselection_usr_id"=>$USER->info->id,
		"displaysuspended"=>$displaysuspended,
		"limit_date"=>date("Y-m-d")
	);
	
	
	
	include_once "admin/class/attendance.php";
	$attendance=new Attendance($sql);
	$r=$attendance->ReportSummary(date("Y-m-d H:i:s", $dateFrom),date("Y-m-d H:i:s", $dateTo),null,$parameters);
	
	$arrDisplay=array();
	if($r){
		$cnt=1;
		
		while($row=$sql->fetch_assoc($r)){
			if(!isset($arrDisplay[$row['personID']])){
				$arrDisplay[$row['personID']] = array();
				$arrDisplay[$row['personID']]['info']=array();
				$arrDisplay[$row['personID']]['info']['id']=$row['personID'];
				$arrDisplay[$row['personID']]['info']['name']=$row['usr_firstname']." ".$row['usr_lastname'];
				$arrDisplay[$row['personID']]['info']['totalAttendedTime']=0;
				$arrDisplay[$row['personID']]['info']['timeGroup']=$row['lbr_mth_name'];
				$arrDisplay[$row['personID']]['days']=array();
			}
			$arrDisplay[$row['personID']]['days'][$row['att_date']] = $row['timeAttended'];
			$arrDisplay[$row['personID']]['info']['totalAttendedTime']+= $row['timeAttended'];
			
			$cnt++;
		}
		
		$dayPlot = $dateFrom;
		while($dayPlot < $dateTo){
			$daysList[]=date("Y-m-d", $dayPlot);
			$dayPlot = strtotime(date("Y-m-d", $dayPlot). ' + 1 days');
		}
	
		$output.="ID\tName\tTotal";
		foreach($daysList as $dayId=>$day){
			$output.="\t".$day;
		}
		$output.="\n";
		foreach($arrDisplay as $empID=>$empData){
			$output.=$empID."\t".$empData['info']['name']."\t";
			$output.=number_format($empData['info']['totalAttendedTime']/3600,2,".",",");
			
			foreach($daysList as $dayId=>$day){
				if(isset($empData['days'][$day])){
					$output.="\t".System::formatTime($empData['days'][$day]);
				}else{
					$output.="\t0";
				}
			}
			$output.= "\n";
		}
	}
	
	header("Accept-Ranges: bytes");
	header("Content-Transfer-Encoding: binary");
	header('Content-Type: text/csv');
	header('Cache-Control: max-age=1');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: cache, must-revalidate');
	header('Pragma: public');
	header('Content-Length: '.strlen($output));
	header('Content-Disposition: attachment;filename="Attendance Rerport, '.$c__settings['site']['title'].", ".gmdate("ymd").'.txt"');

	flush();
	echo $output;
	
}
?>