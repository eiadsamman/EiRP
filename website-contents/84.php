<?php
$data = json_decode(file_get_contents('php://input'), true);
if ($data) {
	$fp = fopen("mqtt.txt", 'a');
	$dt = new DateTime();
	fwrite($fp, $dt->format("Y-m-d H:i") . "\n" . var_export($data, true) . "\n");
	fclose($fp);
}
?>