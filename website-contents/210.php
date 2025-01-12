<?php
use System\Finance\Invoice\enums\Purchase;
use System\Template\Gremium;
use System\Timeline\Module;
use System\Views\PanelView;

$mods = [
	Module::Company->value,
	Module::FinanceCash->value,
	Module::CRMCustomer->value,
	Module::Inventory->value,
];
$mods = join(",", $mods);

$docType = Purchase::Request->value;


if ($app->xhttp) {
	$payload = json_decode(file_get_contents('php://input'), true);
	if (isset($payload['objective']) && $payload['objective'] == 'list') {
		header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		header('Access-Control-Allow-Credentials: true');
		//header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: *");

		$current = (int) ($payload['page']);
		$current = $current < 0 ? 1 : $current;

		$filters      = [
			'company' => [
				'get' => 'doc_id',
				'sql' => 'po_id',
				'type' => 'int',
			]
		];
		$filterQuery  = "";
		$filterValues = [];

		foreach ($filters as $f => &$cond) {
			if (isset($payload[$cond['get']])) {
				if ('int' == $cond['type'] && (int) $payload[$cond['get']] != 0) {
					$filterValues[] = (int) $payload[$cond['get']];
					$filterQuery .= " AND {$cond['sql']} = ? ";
				} elseif ('string' == $cond['type'] && trim($payload[$cond['get']]) != "") {
					$filterValues[] = trim($payload[$cond['get']]);
					$filterQuery .= " AND {$cond['sql']} = ? ";
				}
			}

		}

		$count = 0;
		$r     = $app->db->execute_query(
			"SELECT COUNT(po_id) FROM inv_main WHERE po_type = $docType AND po_comp_id = {$app->user->company->id} AND 1 $filterQuery",
			$filterValues
		);

		if ($r && $row = $r->fetch_array()) {
			$count = $row[0];
		}
		$pages = ceil($count / PanelView::$itemsPerRequest);

		header("Vendor-Ouput-Count: $count");
		header("Vendor-Ouput-Pages: $pages");
		header("Vendor-Ouput-Sum: 0");
		header("Vendor-Ouput-Current: $current");

		if ($count > 0) {

			$pos = ($current - 1) * PanelView::$itemsPerRequest;

			$q = "SELECT 
					_main.po_id,
					CONCAT(prx_value,ccc_id, LPAD(po_serial, prx_placeholder,'0')) AS doc_id,
					_main.po_voided,
					_main.po_title,
					DATE_FORMAT(_main.po_date,'%Y-%m-%d') AS po_date,
					DATE_FORMAT(_main.po_date,'%H:%i') AS po_time,
					CONCAT_WS(' ',usr_firstname,usr_lastname) AS doc_usr_name,
					_main.po_close_date,
					ccc_name,ccc_id
				FROM
					inv_main AS _main
						JOIN users ON usr_id = _main.po_issuedby_id
						LEFT JOIN system_prefix ON prx_sector='Purchase' AND prx_enumid = $docType
						LEFT JOIN inv_records ON pols_po_id = _main.po_id
						JOIN inv_costcenter ON ccc_id = po_costcenter
						JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$app->user->info->id}
				WHERE
					_main.po_type = $docType AND _main.po_comp_id = {$app->user->company->id}
					AND 1 $filterQuery

				GROUP BY
					_main.po_id

				ORDER BY 
					_main.po_date DESC
				LIMIT 
					$pos, " . PanelView::$itemsPerRequest . ";
				";

			$mysqli_result = $app->db->execute_query($q, $filterValues);

			if ($mysqli_result->num_rows > 0) {
				while ($row = $mysqli_result->fetch_assoc()) {

					echo "<tr data-href=\"{$fs(240)->dir}/?id={$row['po_id']}\">";

					echo "<td class=\"col-1\">
						<div class=\"light\">{$row['doc_id']}</div>
						<div><span>{$row['po_title']}</span></div>
						<div><span>{$row['doc_usr_name']}</span></div>
						";
					echo "</td>";
					echo "<td class=\"value-comment col-2\">
						<span>{$row['po_date']} <span class=\"light\">{$row['po_time']}</span></span>
						<span>{$row['ccc_name']} <i class=\"light\"> / {$row['ccc_id']} </i></span>
					</td>";
					echo "<td class=\"blank\"></td>";

					echo "<td class=\"media-hide value-number final 0\"></td>";

					echo "</tr>";
				}
			}
		}
		exit;
	}



	$grem = new Gremium\Gremium(true);
	$grem->header()->serve("<h1><span>{$fs(210)->title}</span></h1><cite></cite><div class=\"btn-set\">" .
		"<button class=\"edge-right standard plus\" data-href=\"{$fs(230)->dir}\" data-target=\"{$fs(230)->dir}\"><span class=\"small-media-hide\"> Add</span></button></div>");
	$legend = $grem->menu()->open();
	echo <<<HTML
		<button id="searchButton" class="edge-left edge-right search" data-href="{$fs(269)->dir}" data-target="{$fs(269)->dir}"><span class="small-media-hide"> Search</span></button>
		<input type="button" id="cancelSearchButton" style="display: none;font-family: glyphs" class="edge-right error" data-href="{$fs()->dir}" href="{$fs()->dir}" value="&#xe901;" />

		<span class="flex" style="justify-content: flex-end"><span class="small-media-hide" id="navEntries">0 records</span></span>
		<input type="button" class="pagination prev edge-left" id="navPrev" disabled value="&#xe91a;" />
		<input type="text" id="js-input_page-current" placeholder="#" data-slo=":NUMBER" style="width:80px;text-align:center" data-rangestart="1" value="0" data-rangeend="100" />
		<input type="button" class="pagination next" id="navNext" disabled value="&#xe91d;" />
		<input type="button" class="edge-right " id="navPages" style="min-width:50px;text-align:center" value="0" />
	HTML;
	$legend->close();
	$grem->article()->open();
	$dummyrows = "";
	for ($i = 0; $i < 3; $i++) {
		$dummyrows .= "<tr><td class=\"placeholder\" colspan=\"6\"></td></tr>";
	}
	echo <<<HTML
		<table class="dynamic hover strip">
			<thead class="table-head" style="top: calc(163px - var(--gremium-header-toggle));background-color: #fff;z-index: 1;">
			<tr>
				<td>ID</td>
				<td>Material Requests</td>
				<td class="blank" style="width: 100%"></td>
				<td></td>
				</tr>
			</tr>
			</thead>
			<tbody id="navOutput">
				{$dummyrows}
			</tbody>
		</table>
	HTML;
	$grem->getLast()->close();
	$grem->terminate();

}