<h1>Add bulk attendance entries</h1>
<form action="" method="post">
	<textarea name="plot"></textarea>
	<button type="submit">Submit</button>
</form>
<pre>
<?php

use System\Controller\Individual\Attendance\Registration;

set_time_limit(0);


if (isset($_POST['plot'])) {
	$stime = microtime(true);
	include_once("admin/class/attendance.php");
	$att = new   Registration($app);
	$att->SetDefaultCheckInAccount($app->user->company->id);

	//DATE	TIME	SIGNER	EMPLOYEE	OPERATION

	$att->integrityCheck = false;

	$lines = explode("\n", $_POST['plot']);
	foreach ($lines as $linek => $line) {

		$col = explode("\t", $line);
		$time = false;
		$signer = (int)$col[2];
		$employee = (int)$col[3];

		$operation = trim($col[4]);
		//2021-12-31 23:59:59
		if (preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/", $col[0] . $col[1], $match)) {
			$time = mktime($match[4], $match[5], $match[6], $match[2], $match[3], $match[1]);
		}

		if ($time && $signer != 0 && $employee != 0) {
			$att->load($employee);
			if ($operation == "!") {
				$ratt = $att->CheckIn(null, $time);
				//checin
			} elseif ($operation == "&") {
				//31
				//Out to rest
				$ratt = $att->CheckIn(31, $time);
			} elseif ($operation == "&&") {
				//75
				//Back from rest
				$ratt = $att->CheckIn(75, $time);
			} elseif ($operation == "!!") {
				// checkout
				$ratt = $att->CheckOut($time);
			}
		}
	}

	echo microtime(true) - $stime;
}
?>