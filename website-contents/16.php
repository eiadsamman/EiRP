Connect to VPN, ping 10.0.1.1<br />

<?php
//eiad/maysutdr34@10.0.1.1:1527/PRD
$arrtrans=array(
	"WRKST"=>"bom_beipn",
	"MATNR"=>"bom_sapno",
	"MAKTX"=>"bom_sapdesc",
	"MEINS"=>"bom_unit",
	"MTART"=>"bom_mattype",
);

$conn = oci_connect('eiad','maysutdr34','10.0.1.1:1527/PRD','UTF8');
var_dump($conn);
if (!$conn) {
    $e = oci_error();
	
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
	
	exit;
}

exit;
$stid = oci_parse($conn, 'SELECT * FROM system.LUMINA');//WHERE rownum <= 40
oci_execute($stid);


$sql->query("DELETE FROM data;");

$q="";
$q.="INSERT INTO data (";
$smart="";
foreach($arrtrans as $k=>$v){
	$q.= $smart.$v;
	$smart=",";
}
$q.=") VALUES ";
$smart="";
$cute="";
$cnt=0;
$buffer=100;
$sq="";
$total=0;
while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
	$cnt++;
	$total++;
	$sq.=$smart."(";
	$cute="";
	foreach($arrtrans as $trnasv=>$trnask){
		$v=$row[$trnasv];
		if($trnask=="bom_sapno"){
			$v=ltrim($v,"0");
		}
		$sq.=$cute.($v !== null ? "'".htmlentities($v, ENT_QUOTES)."'" : "NULL");
		$cute=",";
	}
	$sq.=")";
	$smart=",";
	if($cnt>$buffer){
		if($sql->query( $q.$sq )){echo "MySQL Query Executed<br />";}
		$sq="";
		$cute="";
		$smart="";
		$cnt=0;
	}
}
if($cnt!=0){
	if($sql->query( $q.$sq )){echo "MySQL Query Executed<br />";}
}
echo "<b>Total rows imported: $total</b>";
?>