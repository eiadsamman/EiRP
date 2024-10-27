<?php
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
			'search' => [
				'get' => 'company',
				'sql' => 'comp_id',
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
			"SELECT COUNT(comp_id) FROM companies WHERE 1 $filterQuery",
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
			$q   = "SELECT
				comp_id, comp_name, _cashValue, _tltot, _tlread, (_tltot - _tlread) AS _tlnew,tl_timestamp, 
				DATE_FORMAT(mm.tl_timestamp, '%b %D, %Y') AS _datestamp, DATE_FORMAT(mm.tl_timestamp, '%H:%i ') AS _timestamp,
				mm.usr_firstname, mm.usr_lastname
			FROM 
				companies
					LEFT JOIN (
						SELECT acm_party, SUM( acm_realvalue * IF( acm_type = 1 , 1 , -1) * curexg_value ) AS _cashValue
						FROM acc_main JOIN currency_exchange ON acm_realcurrency = curexg_from 
						GROUP BY acm_party
					) AS _cash
					ON _cash.acm_party = comp_id

					LEFT JOIN 
					(
						SELECT 
							tl_owner, COUNT(tl_id) AS _tltot, COUNT(tlrk_usr_id) AS _tlread
						FROM timeline 
							LEFT JOIN timeline_track ON tl_id = tlrk_tl_id AND tlrk_usr_id = {$app->user->info->id}
						WHERE tl_module IN ($mods) 
						GROUP BY tl_owner
					) AS sub_timeline ON sub_timeline.tl_owner = comp_id

					LEFT JOIN (
						WITH s1 AS (
							SELECT m.tl_issuer, m.tl_owner,m.tl_timestamp, ROW_NUMBER() OVER(PARTITION BY tl_owner ORDER BY tl_timestamp DESC ) AS rn
							FROM timeline AS m
						)
						SELECT tl_owner, tl_timestamp , usr_firstname, usr_lastname
						FROM s1 
							LEFT JOIN users ON usr_id = tl_issuer
						WHERE rn =1
					) AS mm ON mm.tl_owner = comp_id

			WHERE 1 $filterQuery
			ORDER BY tl_timestamp DESC ,comp_id
			LIMIT $pos, " . PanelView::$itemsPerRequest . ";";

			$mysqli_result = $app->db->execute_query($q, $filterValues);

			if ($mysqli_result->num_rows > 0) {
				while ($row = $mysqli_result->fetch_assoc()) {
					$row['_tlnew'] = $row['_tlnew'] ?? 0;
					$row['_tltot'] = $row['_tltot'] ?? 0;
					echo "<tr data-href=\"{$fs(267)->dir}/?id={$row['comp_id']}\">";
					echo "<td class=\"col-1\">
						<div class=\"light\">{$row['comp_id']}</div>
						<div><span>{$row['comp_name']}</span>" . ($row['_tlnew'] > 0 ? "<span class=\"smallbadge\">{$row['_tlnew']}</span>" : "") . "</div>
						<div class=\"in-value value-number " . ($row['_cashValue'] <= 0 ? " negative" : "positive") . "\">" . number_format(abs($row['_cashValue'] ?? 0), 2) . "</div>
						";
					echo "</td>";
					echo "<td class=\"value-comment col-2\">
						<span>" . ($row['usr_firstname'] ? "{$row['usr_firstname']} {$row['usr_lastname']}" : "-") . "</span>
						<span>{$row['_datestamp']} <span class=\"light\">{$row['_timestamp']}</span></span>
						<span>{$row['_tlnew']} <i class=\"light\"> / {$row['_tltot']} feedbacks</i></span>
					</td>";
					echo "<td class=\"blank\"></td>";

					echo "<td class=\"media-hide value-number final " . (($row['_cashValue'] ?? 0) < 0 ? "negative" : "positive") . "\">" . number_format(abs($row['_cashValue'] ?? 0), 2) . "</td>";

					echo "</tr>";
				}
			}
		}
		exit;
	}



	$grem = new Gremium\Gremium(true);
	$grem->header()->serve("<h1><span>{$fs(173)->title}</span></h1><cite></cite><div class=\"btn-set\">" .
		"<button class=\"edge-right standard plus\" data-href=\"{$fs(270)->dir}\" data-target=\"{$fs(270)->dir}\"><span class=\"small-media-hide\"> Add</span></button></div>");
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
				<td>Activities</td>
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