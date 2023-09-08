<pre><?php

include("admin/class/log.php");
$log= new Log();

$report=$log->fetch_log(23);
print_r($report);






?>