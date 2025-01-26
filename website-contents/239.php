<?php

use \System\Controller\Finance\Invoice;
use System\Template\Side;

$_SIDE = new Side();

function SidePanelContent(&$app, $pageUrl, $pageTitle)
{
	$accounting = new \System\Controller\Finance\Accounting($app);
	$_syscur = $accounting->system_default_currency();

	echo "<div><span>Purchase Orders</span></div>";

	$r = $app->db->query("
				SELECT 
					po_id,po_title,po_total,po_vat_rate,po_additional_amount,po_discount,cur_shortname,
					comp_name,
					CONCAT(prx_value,LPAD(po_serial,prx_placeholder,'0')) AS doc_id,
					DATE_FORMAT(po_date,'%Y-%m-%d') AS po_date,
					DATE_FORMAT(po_date,'%H:%i') AS po_time,
					CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS doc_usr_name,
					DATE_FORMAT(po_date,'%W, %M %d, %Y') AS groupDate,
					_rates._rate AS _exchangeRate
				FROM
					inv_main
						JOIN users ON usr_id = po_usr_id
						JOIN currencies ON cur_id = po_cur_id
						JOIN companies ON comp_id = po_comp_id
						LEFT JOIN (
								SELECT _from.curexg_from AS _rate_from,_to.curexg_from AS _rate_to,(_from.curexg_value / _to.curexg_value) AS _rate 
									FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to
							) AS _rates ON _rates._rate_from = po_cur_id AND _rates._rate_to = {$_syscur['id']}
				WHERE
					po_type = " . Invoice::map['PUR_ORD'] . " AND po_close_date IS NULL 
				GROUP BY
					po_id
				ORDER BY po_date DESC, po_rel, po_id DESC 
				");
	if ($r) {
		$arroutput = array();
		while ($row = $r->fetch_assoc()) {
			if (!isset($arroutput[$row['groupDate']])) {
				$arroutput[$row['groupDate']] = array();
			}
			$arroutput[$row['groupDate']][] = $row;
		}
		if (sizeof($arroutput) == 0) {
			echo "<div class=\"template-nestPadding\"><span>No purchase orders active</span></div>";
		}
		foreach ($arroutput as $k => $v) {
			if ($k != null)
				echo "<div class=\"template-nestPadding\"><span>{$k}</span></div>";
			foreach ($v as $elem_key => $elem_val) {
				$doc_value = $elem_val['po_total'] - ($elem_val['po_total'] * $elem_val['po_discount'] / 100) + $elem_val['po_additional_amount'];
				$doc_value += ($doc_value * $elem_val['po_vat_rate'] / 100);
				$doc_value *= $elem_val['_exchangeRate'];

				//<span class=\"fade\"><span>Material requested {$elem_val['matcount']}</span></span>
				echo "<a class=\"role-templatelink\" data-role_title=\"{$pageTitle}\" href=\"{$pageUrl}/?docid={$elem_val['po_id']}&token=" . md5("sysdoc_" . $elem_val['po_id'] . session_id()) . "\">
							<span><span>{$elem_val['po_title']}</span><span>{$elem_val['doc_id']}</span></span>
							<span><span>{$elem_val['comp_name']}</span><span>{$elem_val['po_time']}</span></span>
							<span><span>" . (number_format($doc_value, 2, ".", ",")) . $_syscur['shortname'] . "</span><span>{$elem_val['doc_usr_name']}</span></span>
							<span><span>Items Received 0%</span><span></span></span>
							<span><span>Billing Status 0.00EGP</span><span></span></span>
							
						</a>";
			}
		}
	}

	echo '<script type="text/javascript">
			$(function(){
				$(".role-templatelink,#jQlinkNewDoc").Template();
			});
		</script>';
}

if ($h__requested_with_ajax || isset($_POST['TemplateCallback'])) {
	SidePanelContent(
		$app,
		$fs(237)->dir,
		$c__settings['site']['title'] . " - " . $fs(237)->title
	);
	exit;
}
?>


<?= $_SIDE->HeaderStart() ?>
<div class="btn-set">
	<input type="text" class="flex" placeholder="Search" name="">
</div>
<?= $_SIDE->HeaderEnd() ?>

<?php
echo $_SIDE->BodyStart();
SidePanelContent(
	$app,
	$fs(237)->dir,
	$c__settings['site']['title'] . " - " . $fs(237)->title
);
echo $_SIDE->BodyEnd()
?>