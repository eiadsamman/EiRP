<?php
use System\Template\Gremium\Gremium;

if ($app->xhttp && isset($_POST['add'])) {
	$bookmark_add = $app->user->bookmark_add((int) $_POST['add']);
	if ($bookmark_add == true) {
		header("QUERY_RESULT: 1");
		echo json_encode(
			array(
				$fs((int) $_POST['add'])->id,
				$fs((int) $_POST['add'])->dir,
				$fs((int) $_POST['add'])->title,
			)
		);
	} elseif ($bookmark_add == null) {
		header("QUERY_RESULT: 2");
	} elseif ($bookmark_add == false) {
		header("QUERY_RESULT: 0");
	}
	exit;
}
if ($app->xhttp && isset($_POST['remove'])) {
	echo $app->user->bookmark_remove((int) $_POST['remove']) ? "1" : "0";
	exit;
}




$gremium = new Gremium(true);
$gremium->header(true, null, null, "<h1>Bookmarks</h1>");
$gremium->section(true);
$gremium->sectionHeader("<span class=\"flex\">Account information</span>");
$gremium->sectionArticle();


$count = 0;
$buffer = "";
foreach ($app->user->bookmark_list() as $bookmark) {
	//color:#{$bookmark['trd_attrib5']}
	//<span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px;display:inline-block;color:#555\">&#xe{$bookmark['trd_attrib4']};</span>
	$count++;
	$buffer .= "<tr>";
	$buffer .= "<td class=\"op-remove noselect\" data-id=\"{$bookmark['trd_id']}\"><span></span></td>";
	$buffer .= "<td><span>{$bookmark['pfl_value']}</span></td>";
	$buffer .= "<td width=\"100%\"><a href=\"{$bookmark['trd_directory']}/\" title=\"{$bookmark['pfl_value']}\">{$bookmark['trd_directory']}</a></td>";
	$buffer .= "</tr>";
}

if ($count > 0) {
	echo "<table class=\"bom-table hover \"><tbody>{$buffer}</tbody></table>";
} else {
	//$_TEMPLATE->NewFrameTitle("<span class=\"flex\">N</span>", false, true);
	echo('<ul>
			<li>No bookmarks found</li>
			<li>Try adding some pages to bookmarks</li>
			<li>Bookmarks can be added through `User Account` menu by clicking `Add` button</li>
			<ul>');
}

$gremium->sectionArticle();
$gremium->section();
unset($gremium);

?>
<script>
	$(document).ready(function (e) {
		var ajax = null;
		$(".op-remove").on('click', function (e) {
			let bookmarkid = $(this).attr("data-id");
			let rowowner = $(this).parent();
			rowowner.css("display", "none");

			ajax = $.ajax({
				url: '<?= $fs(263)->dir ?>',
				type: 'POST',
				data: {
					"remove": bookmarkid
				}
			}).done(function (data) {
				if (parseInt(data) != 1) {
					rowowner.css("display", "table-row");
					messagesys.failure("Removing bookmark failed");
				}
			}).fail(function (a, b, c) {
				rowowner.css("display", "table-row");
				messagesys.failure("Removing bookmark failed");
			});

		});


	});
</script>