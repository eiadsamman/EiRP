<?php
use System\Personalization\Bookmark;
use System\Template\Gremium;

$bookmark = new Bookmark($app);


if ($app->xhttp) {
	if (isset($_POST['order'])) {

		$bookmark->update($_POST['order']);
		exit;
	}

	if (isset($_POST['add'])) {
		$bookmark_add = $bookmark->register((int) $_POST['add']);
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
	if (isset($_POST['remove'])) {
		echo $bookmark->remove((int) $_POST['remove']) ? "1" : "0";
		exit;
	}

	exit;
}



$grem = new Gremium\Gremium(true);
$grem->header()->prev($fs(27)->dir)->serve("<h1>Bookmarks</h1>");
$grem->article()->open();

$firstocc = false;
foreach ($bookmark->list() as $bookmark) {
	//color:#{$bookmark['trd_attrib5']}
	//<span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px;display:inline-block;color:#555\">&#xe{$bookmark['trd_attrib4']};</span>
	if (!$firstocc) {
		echo "<table class=\"bom-table hover\" id=\"bookmarks-table\" style=\"position:relative;\"><tbody>";
		$firstocc = true;
	}

	echo "<tr data-pageid=\"{$bookmark['trd_id']}\">";
	echo "<td style=\"min-width:34px;\" class=\"move-handle\">:::</td>";
	echo "<td><span>{$bookmark['pfl_value']}</span></td>";
	echo "<td width=\"100%\"><a href=\"{$bookmark['trd_directory']}/\" title=\"{$bookmark['pfl_value']}\">{$bookmark['trd_directory']}</a></td>";
	echo "<td class=\"op-remove noselect\" data-id=\"{$bookmark['trd_id']}\"><span></span></td>";
	echo "</tr>";
}


if ($firstocc) {
	echo "</tbody></table>";
} else {
	//$_TEMPLATE->NewFrameTitle("<span class=\"flex\">N</span>", false, true);
	echo ('<ul>
			<li>No bookmarks found</li>
			<li>Try adding some pages to bookmarks</li>
			<li>Bookmarks can be added through `User Account` menu by clicking `Add` button</li>
			<ul>');
}

$grem->getLast()->close();
unset($grem);

?>
<style>
	.ui-sortable-start {
		background-color: var(--color-soft-gray);
		position: relative;
		margin-left: -1px;
	}

	#bookmarks-table>tbody>tr>td.move-handle {
		cursor: move;
		-webkit-touch-callout: none;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}
</style>
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


		$("#bookmarks-table > tbody").sortable({
			handle: '.move-handle',
			helper: 'clone',
			tolerance: 'pointer',
			axis: 'y',
			items: 'tr',
			opacity: 0.8,
			forceHelperSize: true,
			forcePlaceholderSize: true,
			start: function (event, ui) {
				ui.item.addClass("ui-sortable-start");
			},
			stop: function (event, ui) {
				ui.item.removeClass("ui-sortable-start");
			},
			update: function (event, ui) {
				ui.item.removeClass("ui-sortable-start");
				var neworder = [];
				$(this).find("tr").each(function (index, element) {
					neworder.push($(this).attr("data-pageid"));
				});
				$.ajax({
					url: "<?= $fs()->dir; ?>",
					data: { "order": neworder },
					type: "POST"
				}).done((o) => {
				}).fail(() => { });
			}
		});
	});
</script>