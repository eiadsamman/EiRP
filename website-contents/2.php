<form action="display-bom" method="post" target="_blank" id="esform">
	<h2 class="bom-idnt">Finished Goods</h2>
	<input name="esfin" type="text" data-slo="A003" style="width:100%" id="esdin" placeholder="Finished Goods BEI" />
	<input name="esdate" type="text" class="text" placeholder="Date (d.m.Y)" style="width:100%;margin-top:5px;" value="<?php echo date("d.m.Y"); ?>" />
	<input name="esstore" type="text" class="text" placeholder="Storage Location (#)" style="width:100%;margin-top:5px;" value="2001" />

	<h2 class="bom-idnt">Level 0: Complete Assembly</h2>
	<table data-counter="0" data-lvl="0">
		<thead>
			<tr>
				<td>Level</td>
				<td width="100%">BEI Material</td>
				<td>Quantity</td>
				<td class="op-add noselect"><span></span></td>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
	<div id="boms"></div>

	<div style="text-align:center;">
		<button type="button" class="bomsubbtn">Submit</button>
	</div>
	<hr />
	<div style="margin-top:10px;">
		Notes:
		<ul>
			<li>Date of uploading DOM into the server must be in this format `d.m.Y`, Ex: `27.02.2015`</li>

			<li>Choose LVL0 materials in the logical order from bottom to top</li>
			<li>If you gave an element a floated number, it will be displayed as a single row</li>
			<li>In BOM material reference, you might enter the material reference based on its Quantity and sepereted by `;`<br />
				Ex: If you entered the quantity as `3` you have to enter each one of these `3` elements reference in the field seperated by `;` like: `ref1;ref2;ref3`</li>
			<li>
				<?php
				echo "<pre style=\"margin:0;\">";
				echo "<b>Code\tCount\tMaterial Type</b>\n";
				$array_sap_bei_materials = array(
					"ERSA" => array("Spare Parts - New", 0),
					"ZUNF" => array("Finished Product", 0),
					"FHMI" => array("Production Resourse/Tool", 0),
					"ZHLB" => array("Semi-finished Product", 0),
					"HERS" => array("Manufacturer Part", 0),
					"HIBE" => array("Operating supplies Manufacturing", 0),
					"ROH" => array("Raw materials", 0),
					"ZUNB" => array("Non-valuated materials", 0),
					"ZFOC" => array("Metra Free Goods", 0),
					"ZMAN" => array("Manufacturing consumables", 0),
					"ZSCR" => array("Scrap Inventory, semi-finished, valuated", 0),
					"ZUNS" => array("Scrap Materials, non-valuated", 0),
				);
				$r = $app->db->query("SELECT count(bom_id) AS count, bom_mattype FROM data GROUP BY bom_mattype");
				while ($row = $r->fetch_assoc()) {
					if (isset($array_sap_bei_materials[$row['bom_mattype']])) {
						$array_sap_bei_materials[$row['bom_mattype']][1] = $row['count'];
					}
				}
				foreach ($array_sap_bei_materials as $k => $v) {
					echo "$k\t{$v[1]}\t{$v[0]}\n";
				}
				echo "</pre>";
				?></li>
		</ul>
	</div>
</form>

<script>
	$(function() {
		$(".bomsubbtn").on('click', function() {
			$("#esform").submit();
		});

		function recount_bylvl(lvl) {
			var _cnt = 0;
			$("table[data-lvl=" + lvl + "] > tbody > tr").each(function() {
				_cnt++;
				$(this).find("td:first-child").html(_cnt);
			});
		}

		function recount_bytable(table) {
			var _cnt = 0;
			$(table).find("tbody > tr").each(function() {
				_cnt++;
				$(this).find("td:first-child").html(_cnt);
			});
		}

		function recount_titles() {
			var _cnt = 0,
				_ray = 0;
			$("table[data-lvl=0] > tbody > tr").each(function() {
				_cnt++;
				_ray = $(this).attr("data-layer");
				$("div[data-lvl=" + _ray + "] > h2 > span").html(_cnt);
			});
		}

		function createLVLX_table(lvl, description, more) {
			if ($("div[data-lvl=" + lvl + "]").length > 0) {
				var $obj = $("div[data-lvl=" + lvl + "]");
				$obj.find("> h2").html("Level <span>" + lvl + "</span>: " + description + "");
				$obj.find("> span").html(more);
				$obj.find("> div.jQoverlay").remove();
				recount_titles();
			} else {
				var output = "";
				output += '<div data-lvl=' + lvl + ' style="position:relative"><h2 class="bom-idnt">Level <span>' + lvl + '</span>: ' + description + '</h2>';
				output += '<span>' + more + '</span>';
				output += '<table class="bom-els" data-counter="0" data-lvl="' + lvl + '">';
				output += '<thead><tr><td>Order</td><td width="100%">BEI Material</td><td>Quantity</td><td>Reference</td><td class="op-add noselect"><span></span></td></tr></thead>';
				output += '<tbody></tbody>';
				output += '</table></div>';
				$output = $(output);
				$("#boms").append($output);
				createLVLX_elem(lvl);
				recount_titles();
			}
		}

		function createLVLX_elem(lvl) {
			var $table = $("table[data-lvl=" + lvl + "]");
			var _lvl = $table.attr('data-lvl');
			var _cnt = parseInt($table.attr('data-counter'));
			var output = "";
			_cnt++;
			if (_lvl != "0") {
				output += "<tr data-layer=\"" + _cnt + "\">";
				output += "<td>" + _cnt + "</td>";
				output += "<td>";
				output += "<input name=\"esbom[" + lvl + "][" + _cnt + "]\" type=\"text\" data-slo=\"A001\" ";
				output += "style=\"width:100%\" value=\"\" />";
				output += "</td>";
				output += "<td><input name=\"esqty[" + lvl + "][" + _cnt + "]\" autocomplete=\"off\" type=\"text\" class=\"text\" style=\"width:60px;text-align:center\" value=\"0\" /></td>";
				output += "<td><input name=\"esref[" + lvl + "][" + _cnt + "]\" autocomplete=\"off\" type=\"text\" class=\"text\" style=\"width:200px;text-align:left\" value=\"\" /></td>";
				output += "<td class=\"op-remove noselect\"><span></span></td>";
				output += "</tr>";
				$output = $(output);
				$table.find("tbody").append($output);
				$output.find("[data-slo]").slo({
					"limit": 7
				});
			}
			recount_bytable($table);
			$table.attr('data-counter', _cnt);
		}

		function hideTable(lvl) {
			var $obj = $("div[data-lvl=" + lvl + "]");
			var $ovl = $("<div />");
			$ovl.css({
				'position': 'absolute',
				'top': '0px',
				'left': '0px',
				'right': '0px',
				'bottom': '0px',
				'background-color': 'rgba(255,255,255,0.7)',
			}).addClass("jQoverlay").html();
			$obj.append($ovl);
		}

		function createLVL0() {
			var $table = $("table[data-lvl=0]");
			var _lvl = $table.attr('data-lvl');
			var _cnt = parseInt($table.attr('data-counter'));
			var output = "";
			_cnt++;
			if (_lvl == "0") {
				output += "<tr data-layer=\"" + _cnt + "\">";
				output += "<td>" + _cnt + "</td>";
				output += "<td>";
				output += "<input name=\"esbom[0][" + _cnt + "]\" type=\"text\" data-slo=\"A002\" ";
				output += "style=\"width:100%\" value=\"\" />";
				output += "</td>";
				output += "<td>";
				output += "<input type=\"text\" class=\"text\" style=\"width:60px;text-align:center\" value=\"0\" autocomplete=\"off\" name=\"esqty[0][" + _cnt + "]\" />";
				output += "</td>";
				output += "<td class=\"op-remove noselect\"><span></span></td>";
				output += "</tr>";
				$output = $(output);
				$table.find("tbody").append($output);
				$output.find("[data-slo]").slo({
					"onselect": function(value) {
						createLVLX_table(_cnt, value.value, value.text);
					},
					"ondeselect": function() {
						hideTable(_cnt);
					},
					"limit": 7
				});
			}
			recount_bytable($table);
			$table.attr('data-counter', _cnt);
		}
		$(document).on('click', "[data-lvl=0] .op-add", function() {
			createLVL0();
		});
		$(document).on('click', "[data-lvl=0] .op-remove", function() {
			var $row = $(this).closest("tr"),
				_lay = $row.attr("data-layer"),
				$table = $row.closest("table");
			$("div[data-lvl=" + _lay + "]").remove();
			$row.remove();
			recount_bytable($table);
			recount_titles();
		});
		$(document).on('click', '.bom-els .op-add', function() {
			var lvl = $(this).closest("table").attr('data-lvl');
			createLVLX_elem(lvl);
		});

		$(document).on('click', '.bom-els .op-remove', function() {
			var $row = $(this).closest("tr"),
				_lay = $row.attr("data-layer"),
				$table = $row.closest("table");
			$row.remove();
			recount_bytable($table);
		});

		for (i = 0; i < 4; i++) {
			createLVL0();
		}

		$("#esdin").slo({
			"limit": 7
		});

	});
</script>