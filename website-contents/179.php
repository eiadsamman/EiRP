<?php
use System\SmartListObject;
use System\Template\Gremium\Gremium;

/*
 * TODO
 * 1. Date range and pagination in broweser history
 * 2. 
 * 
 */
if (!$app->user->account->role->view) {
	$gremium = new Gremium();
	$gremium->header(true, $gremium->exclamation, null, "<h1>Access restricted!</h1>");
	$gremium->section();
	$gremium->sectionHeader("<span class=\"flex\">Loading journal for `{$app->user->account->name}` account failed:</span>");
	$gremium->sectionArticle('<ul style="margin:0">
		<li>Session has expired, loing in again to your account</li>
		<li>Database query failed, contact system administrator</li>
		<li>Viewing account detials is restricted by management, try selecting another account</li>
		<li>Permission denied or not enough privileges to proceed with this document</li>
		<ul>');
	$gremium->section();
	unset($gremium);
	exit;
}







$SmartListObject = new SmartListObject($app);


$gremium = new Gremium(false);

$gremium->header(true, null, $fs()->dir, "<h1>{$fs()->title}</h1><cite>{$app->user->company->name}: {$app->user->account->name}</cite>");

$gremium->menu();

echo "<span>Date</span>";
echo "<input type=\"text\" id=\"js-input_date-start\" style=\"width:120px\" data-slo=\":DATE\" data-rangestart=\"1970-01-01\" value=\"\"  />";
echo "<input type=\"text\" id=\"js-input_date-end\" style=\"width:120px\" data-slo=\":DATE\" data-rangestart=\"1970-01-01\" value=\"" . date("Y-m-d") . "\"  />";
echo "<button>Update</button>";
echo "<button>Export</button>";
echo "<span class=\"gap\"></span>";

$gremium->menu();


$gremium->section();
$gremium->sectionHeader();
echo "<span class=\"flex\">Account journal records</span>";
echo "<button><</button>";
echo "<input type=\"text\" id=\"js-input_page-current\" data-slo=\":NUMBER\" style=\"width:80px;text-align:center\" data-rangestart=\"1\" value=\"1\" data-rangeend=\"100\" />";
echo "<span id=\"js-input_page-total\" style=\"\"> / 90</span>";
echo "<button>></button>";
$gremium->sectionHeader();


$gremium->sectionArticle();
for ($I = 0; $I <= 100; $I++)
	echo "$I<br>";
$gremium->sectionArticle();



$gremium->section();
unset($gremium);

?>


<script type="text/javascript">
	$(document).ready(function (e) {
		$("#js-input_date-start").slo();
		$("#js-input_page-current").slo();
	});
</script>