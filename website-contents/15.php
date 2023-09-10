<?php 

/*
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');
*/

require('admin/class/barcode/barcode.php');

if(isset($_GET['c'],$_GET['f'],$_GET['t']) && trim($_GET['c'])!=''){
	header('Content-Type: image/png');

	$scale = (int)$_GET['f'];
	$scale = $scale > 10 ? 10 : ($scale < 1 ? 1 : $scale);
	
	$height = (int)$_GET['t'];
	$height = $height > 100 ? 100 : ($height < 1 ? 1 : $height);
	

	\Barcode::code39($_GET['c'],$height,$scale);
}
exit;
?>
