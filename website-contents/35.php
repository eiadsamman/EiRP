<?php
echo `whoami`."<br />";
echo exec('whoami /priv')."<hr />";
echo "<b>Terminal output:</b>";
echo "<pre style=\"background-color:#000;padding:5px 10px;color:#0f0\">";
if(system("D:\wamp\www\sqlsysback.bat  2>&1",$return)){
}else{
	echo "Failed to execute bat file";
}
echo "</pre>";
?>