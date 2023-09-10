<?php
include_once("admin/class/employee.php");
include_once("admin/class/Template/class.template.build.php");

use System\App;
use System\Person\Attendance;
use Template\Body;

$_TEMPLATE = new Body("Test");
$_TEMPLATE->SetLayout(/*Sticky Title*/ true,/*Command Bar*/ true ,/*Sticky Frame*/ true);
$_TEMPLATE->FrameTitlesStack(false);
$_TEMPLATE->Title($fs()->title, null, date("Y-m-d", time()));


$dateFrom=time();
include_once "admin/class/attendance.php";
$attendance=new Attendance();
$r=$attendance->ReportToday(["company"=>$USER->company->id]);


$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Attendance report</span>");
echo $_TEMPLATE->NewFrameBodyStart();

echo "<table class=\"bom-table\">";

echo "<thead>";
echo "<tr><td></td><td></td><td>ID</td><td>Name</td><td>Check-in time</td><td align=\"right\">Time</td><td width=\"100%\"></td></tr>";
echo "</thead>";
echo "<tbody>";

$cnt=0;
if($r){
	
	while($row=$sql->fetch_assoc($r)){
		$cnt+=1;
		echo "<tr>";
		echo "<td>$cnt</td>";
		echo "<td>".(is_null($row['latestRecordOut'])?"<span style=\"font-family:icomoon4;color:#093\">&#xea1c;</span>":"<span style=\"font-family:icomoon4;color:#999\">&#xea1d;</span>")."</td>";
		echo "<td>{$row['personID']}</td>";
		echo "<td>{$row['usr_firstname']} {$row['usr_lastname']}</td>";
		echo "<td>{$row['ltr_ctime']}</td>";
		echo "<td style=\"min-width:100px;text-align:right\">".App::formatTime($row['timeAttended'])."</td>";
		echo "<td style=\"width:100%\"></td>";
		echo "</tr>";
	}
}

echo $_TEMPLATE->NewFrameBodyEnd();

echo "</tbody>";
echo "</table>";



?>