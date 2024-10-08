<?php
use System\Individual\Attendance\Registration;
use System\Template\Gremium;

$grem = new Gremium\Gremium();
$attendance = new Registration($app);
$grem->header()->serve("<h1>{$fs()->title}</h1><ul><li>" . date("Y-m-d", time()) . "</li></ul>");
$grem->title()->serve("<span class=\"flex\">Attendance report</span>");
$grem->article()->open();
echo "<table id=\"att_list\">";
echo "<thead>";
echo "<tr><td></td><td></td><td>ID</td><td>Name</td><td>Check-in time</td><td align=\"right\">Time</td><td width=\"100%\"></td></tr>";
echo "</thead>";
echo "<tbody>";
$r = $attendance->ReportToday(["company" => $app->user->company->id]);
if ($r && $r->num_rows > 0) {
	$cnt = 0;
	while ($row = $r->fetch_assoc()) {
		$cnt += 1;
		echo "<tr>";
		echo "<td>$cnt</td>";
		echo "<td>" . (is_null($row['latestRecordOut']) ? "<span style=\"font-family:icomoon4;color:#093\">&#xea1c;</span>" : "<span style=\"font-family:icomoon4;color:#999\">&#xea1d;</span>") . "</td>";
		echo "<td>{$row['personID']}</td>";
		echo "<td>{$row['usr_firstname']} {$row['usr_lastname']}</td>";
		echo "<td>{$row['ltr_ctime']}</td>";
		echo "<td style=\"min-width:100px;text-align:right\">" . $app->formatTime($row['timeAttended']) . "</td>";
		echo "<td style=\"width:100%\"></td>";
		echo "</tr>";
	}
}
$grem->getLast()->close();
$grem->terminate();
echo "</tbody>";
echo "</table>";


?>

<style>
	@media only screen and (max-width: 624px) {
		#att_list>thead>tr>td:nth-child(5),
		#att_list>tbody>tr>td:nth-child(5) {
			display: none;

		}

	}
</style>