<?php
use System\Controller\Personalization\Bookmark;
use System\Layout\Gremium;

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
$grem->header()->prev("href=\"{$fs(27)->dir}\"")->serve("<h1>Bookmarks</h1>");

$grem->menu()->serve("<span class=\"flex\"></span><input id=\"js-input_add-list\" data-slo=\":LIST\" data-list=\"js-ref_list\" placeholder=\"Add a bookmark...\" type=\"text\" />");

$grem->article()->open();

$firstocc = false;

echo "<table class=\"hover\" id=\"js-output_tablelist\" style=\"position:relative;\"><tbody>";
foreach ($bookmark->list() as $bookmark) {
	//color:#{$bookmark['trd_attrib5']}
	//<span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px;display:inline-block;color:#555\">&#xe{$bookmark['trd_attrib4']};</span>
	if (!$firstocc) {

		$firstocc = true;
	}

	echo "<tr data-pageid=\"{$bookmark['trd_id']}\">";
	echo "<td style=\"min-width:34px;\" class=\"move-handle\">:::</td>";
	echo "<td><span>{$bookmark['pfl_value']}</span></td>";
	echo "<td width=\"100%\"><a href=\"{$bookmark['trd_directory']}/\" title=\"{$bookmark['pfl_value']}\">{$bookmark['trd_directory']}</a></td>";
	echo "<td class=\"op-remove noselect\" data-id=\"{$bookmark['trd_id']}\"><span></span></td>";
	echo "</tr>";
}

echo "</tbody></table>";


if ($firstocc) {
	$tempview = " style=\"display:none;\" ";
} else {
	$tempview = " style=\"display:block;\" ";
}
echo ('<ul id="js-output_disclaimer" ' . $tempview . '>
	<li>No bookmarks found</li>
	<li>Try adding some pages to bookmarks</li>
	<li>Bookmarks can be added through `User Account` menu by clicking `Add` button</li>
	<ul>');

$grem->getLast()->close();
$grem->terminate();



?>

<datalist id="js-ref_list" style="display: none;">
	<?php
	$ident = \System\Controller\Personalization\Identifiers::SystemFrequentVisit->value;
	$q = <<<SQL
	SELECT 
		pfl_value, trd_id
	FROM 
		pagefile 
		JOIN pagefile_language ON pfl_trd_id = trd_id AND pfl_lng_id = 1 
		JOIN 
			pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id = {$app->user->info->permissions}
				LEFT JOIN user_settings ON usrset_usr_defind_name = trd_id AND usrset_usr_id = {$app->user->info->id} 
				AND usrset_type = {$ident}
	WHERE 
		trd_enable = 1 AND trd_visible = 1
	ORDER BY
		(usrset_value + 0) DESC, pfl_value
	SQL;

	if ($r = $app->db->query($q)) {
		while ($row = $r->fetch_assoc()) {
			echo "<option data-id=\"{$row['trd_id']}\">{$row['pfl_value']}</option>";
		}
	}
	?>
</datalist>



<style>
	.ui-sortable-start {
		background-color: var(--color-soft-gray);
		position: relative;
		margin-left: -1px;
	}

	#js-output_tablelist>tbody>tr>td.move-handle {
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
		var outtable = $("#js-output_tablelist > tbody");
		var outdisc = $("#js-output_disclaimer");

		outtable.on('click', '.op-remove', function (e) {
			let bookmarkid = $(this).attr("data-id");
			let rowowner = $(this).parent();
			rowowner.css("display", "none");
			$.ajax({
				url: '<?= $fs()->dir ?>',
				type: 'POST',
				data: {
					"remove": bookmarkid
				}
			}).done(function (data) {
				if (parseInt(data) != 1) {
					rowowner.css("display", "table-row");
					messagesys.failure("Removing bookmark failed");
				} else {
					rowowner.remove();
				}
				checkListState();
			}).fail(function (a, b, c) {
				rowowner.css("display", "table-row");
				messagesys.failure("Removing bookmark failed");
			});
		});

		$("#js-input_add-list").slo({
			onselect: function (o) {
				overlay.show();
				$.ajax({
					url: '<?= $fs()->dir ?>',
					type: 'POST',
					data: {
						"add": o.key
					}
				}).done(function (data, textStatus, request) {
					let response = request.getResponseHeader('QUERY_RESULT');
					if (response == 2) {
						messagesys.success("Page already bookmarked");
					} else if (response == 0) {
						messagesys.failuer("Bookmarking page failed");
					} else {
						var json = null;
						try {
							json = JSON.parse(data);
						} catch (e) {
							messagesys.failure("Parsing server response failed");
							return false;
						}
						messagesys.success("Page bookmarked successfully");

						let newrow = $("<tr data-pageid=\"" + (json[0]) + "\" />");
						newrow.append("<td style=\"min-width:34px;\" class=\"move-handle\">:::</td>");
						newrow.append("<td><span>" + (json[2]) + "</span></td>");
						newrow.append("<td width=\"100%\"><a href=\"" + (json[1]) + "/\" title=\"" + (json[1]) + "\">" + (json[1]) + "</a></td>");
						newrow.append("<td class=\"op-remove noselect\" data-id=\"" + (json[0]) + "\"><span></span></td>");

						outtable.prepend(newrow);
						checkListState();
					}
				}).fail(function (a, b, c) {
					messagesys.failuer("Bookmarking page failed");
				}).always(function () {
					overlay.hide();
				});
			}
		});


		let checkListState = function () {
			let count = outtable.children().length;
			if (count == 0) {
				outdisc.css("display", "block");
			} else {
				outdisc.css("display", "none");
			}
		}

		$("#js-output_tablelist > tbody").sortable({
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