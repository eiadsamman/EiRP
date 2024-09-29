<?php
use System\Finance\Invoice\enums\Purchase;
use System\Views\PanelView;


if ($app->xhttp) {
	$payload = json_decode(file_get_contents('php://input'), true);
	if (!empty($payload['method']) && $payload['method'] == "fetch") {
		$rm_type = Purchase::Request->value;


		$current = (int) ($payload['page']);
		$current = $current < 0 ? 1 : $current;

		$count = 0;
		$q     = "SELECT COUNT(po_id) FROM inv_main WHERE po_type = $rm_type AND po_comp_id = {$app->user->company->id}";

		$r = $app->db->query($q);
		if ($r && $row = $r->fetch_array()) {
			$count = $row[0];
		}
		$pages = ceil($count / PanelView::$itemsPerRequest);

		header("res_count: {$count}");
		header("res_pages: {$pages}");
		header("res_current: {$current}");




		$pos = ($current - 1) * PanelView::$itemsPerRequest;

		$q = "SELECT 
				_main.po_id,
				CONCAT(prx_value,LPAD(po_serial,prx_placeholder,'0')) AS doc_id,
				_main.po_canceled,
				_main.po_title,
				DATE_FORMAT(_main.po_date,'%Y-%m-%d') AS po_date,
				DATE_FORMAT(_main.po_date,'%H:%i') AS po_time,
				CONCAT_WS(' ',usr_firstname,usr_lastname) AS doc_usr_name,
				COUNT(pols_id) AS matcount,
				_sub._subcount AS qutcount,
				_main.po_close_date,
				ccc_name,ccc_id
			FROM
				inv_main AS _main
					JOIN users ON usr_id = _main.po_usr_id
					JOIN system_prefix ON prx_id = $rm_type
					LEFT JOIN inv_records ON pols_po_id = _main.po_id
					LEFT JOIN (SELECT po_rel, COUNT(po_id) AS _subcount FROM inv_main WHERE po_type = 2 GROUP BY po_rel) AS _sub ON _sub.po_rel = _main.po_id
					JOIN inv_costcenter ON ccc_id = po_costcenter
					JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id={$app->user->info->id}
			WHERE
				_main.po_type = $rm_type AND _main.po_comp_id={$app->user->company->id}
			GROUP BY
				_main.po_id
			ORDER BY _main.po_date DESC
			";

		$mysqli_result = $app->db->query($q);
		if ($mysqli_result->num_rows > 0) {
			while ($row = $mysqli_result->fetch_assoc()) {
				$costcenter = $row['ccc_name'];

				$closed = (is_null($row['po_close_date']) ? "Open" : "Closed");
				echo <<<HTML
					<a class="panel-item " href="{$fs(240)->dir}/?id={$row['po_id']}" data-listitem_id="{$row['po_id']}" data-href="{$fs(240)->dir}">
						<div>
							<span style="flex: 1">
								<div><h1>{$row['po_title']}</h1><cite> </cite><cite>{$row['doc_id']}</cite></div>
								<div><h1>({$row['matcount']} items)</h1><cite> </cite><cite>({$row['qutcount']} Quotations)</cite></div>
								<div><h1>{$row['po_date']}</h1><cite>{$closed}</cite><cite>{$row['doc_usr_name']}</cite></div>
							</span>
						</div>
					</a>
				HTML;
			}
		}

		exit;
	}
}