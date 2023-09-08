<?php
include_once("admin/class/system.php");

use System\System;

if ($h__requested_with_ajax && isset($_POST['add'])) {
	$bookmark_add = System::bookmarkAdd((int)$_POST['add']);
	if ($bookmark_add == true) {
		header("QUERY_RESULT: 1");
		$bookmark_page = $tables->pagefile_info((int)$_POST['add']);
		echo json_encode(array($bookmark_page['id'], $bookmark_page['directory'], $bookmark_page['title']));
	} elseif ($bookmark_add == null) {
		header("QUERY_RESULT: 2");
	} elseif ($bookmark_add == false) {
		header("QUERY_RESULT: 0");
	}
	exit;
}
if ($h__requested_with_ajax && isset($_POST['remove'])) {
	echo System::bookmarkRemove((int)$_POST['remove']) ? "1" : "0";
	exit;
}


require_once("admin/class/Template/class.template.build.php");

use Template\TemplateBuild;

$_TEMPLATE 	= new TemplateBuild();
$_TEMPLATE->SetWidth("800px");
$_TEMPLATE->Title("<a class=\"backward\" href=\"{$fs->find(27)->dir}\"></a>Bookmarks", null, null);


$count = 0;
$buffer = "";
foreach (System::bookmarksList() as $bookmark) {
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
	echo $_TEMPLATE->NewFrameBodyStart();
	echo "<table class=\"bom-table hover\"><tbody>{$buffer}</tbody></table>";
	echo $_TEMPLATE->NewFrameBodyEnd();
} else {
	//$_TEMPLATE->NewFrameTitle("<span class=\"flex\">N</span>", false, true);
	$_TEMPLATE->NewFrameBody('<ul>
			<li>No bookmarks found</li>
			<li>Try adding some pages to bookmarks</li>
			<li>Bookmarks can be added through `User Account` menu by clicking `Add` button</li>
			<ul>');
}
?>
<script>
	$(document).ready(function(e) {
		var ajax = null;
		$(".op-remove").on('click', function(e) {
			let bookmarkid = $(this).attr("data-id");
			let rowowner = $(this).parent();
			rowowner.css("display", "none");

			ajax = $.ajax({
				url: '<?= $fs->find(263)->dir ?>',
				type: 'POST',
				data: {
					"remove": bookmarkid
				}
			}).done(function(data) {
				if (parseInt(data) != 1) {
					rowowner.css("display", "table-row");
					messagesys.failure("Removing bookmark failed");
				}
			}).fail(function(a, b, c) {
				rowowner.css("display", "table-row");
				messagesys.failure("Removing bookmark failed");
			});

		});


	});
</script>