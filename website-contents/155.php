<?php
$r=$sql->query("SELECT UNIX_TIMESTAMP(NOW());");
echo $sql->error();
$row=$sql->fetch_row($r);
var_dump($row);
echo time();

$date=time();

$sql->query("TRUNCATE test");

$r=$sql->query("INSERT INTO test (t_date) VALUES (FROM_UNIXTIME($date));");

echo $sql->error();



$r=$sql->query("SELECT * FROM test");
while($row=$sql->fetch_assoc($r)){
	var_dump($row);
}

































?>