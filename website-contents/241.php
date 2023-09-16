<?php

use System\Template\Side;
use \System\Finance\Invoice;
$_SIDE = new Side();

function SidePanelContent(&$app, $pageUrl, $pageTitle, $newUrl, $newTitle)
{
	$accounting = new \System\Finance\Accounting($app);
	$_syscur = $accounting->system_default_currency();
	$mysqli_result = $app->db->query("
		SELECT 
			po_id,po_title,DATE_FORMAT(po_date,'%Y-%m-%d') AS po_date,
			po_close_date,
			CONCAT(prx_value,LPAD(po_serial,prx_placeholder,'0')) AS doc_id 
		FROM 
			inv_main 
				JOIN system_prefix ON prx_id = " . Invoice::map['MAT_REQ'] . "
				JOIN inv_costcenter ON ccc_id = po_costcenter
				JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id={$app->user->info->id}
		WHERE 
			po_close_date IS NULL AND po_type = " . Invoice::map['MAT_REQ'] . " AND (po_canceled = 0 OR po_canceled = NULL) ORDER BY po_date DESC");
	if ($mysqli_result) {
		if ($mysqli_result->num_rows == 0) {
			echo "<div><span>No pending quotations</span></div>";
		}
		while ($mysqli_record = $mysqli_result->fetch_assoc()) {
			echo "<div>";
			echo is_null($mysqli_record['po_close_date']) ? "" : "<div style=\"color:#F03\">Closed&nbsp;</div>";
			echo "<span>{$mysqli_record['doc_id']} {$mysqli_record['po_title']} </span><a 
				class=\"template-IconAdd role-templatelink\" 
				  data-role_title=\"{$newTitle}\" href=\"{$newUrl}/?docid={$mysqli_record['po_id']}&token=" . md5("sysdoc_" . $mysqli_record['po_id'] . session_id()) . "\"
				></a></div>";

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
						JOIN system_prefix ON prx_id=" . Invoice::map['PUR_QUT'] . "
						LEFT JOIN (
								SELECT _from.curexg_from AS _rate_from,_to.curexg_from AS _rate_to,(_from.curexg_value / _to.curexg_value) AS _rate 
									FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to
							) AS _rates ON _rates._rate_from = po_cur_id AND _rates._rate_to = {$_syscur['id']}
				WHERE
					po_type = " . Invoice::map['PUR_QUT'] . " AND po_close_date IS NULL AND po_rel = {$mysqli_record['po_id']}
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
					echo "<div class=\"template-nestPadding\"><span>No quotations placed</span></div>";
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
							<span><span>{$elem_val['doc_id']}</span><span></span></span>
							<span><span>{$elem_val['comp_name']}</span><span>{$elem_val['po_time']}</span></span>
							<span><span>" . (number_format(
							$doc_value,
							2,
							".",
							","
						)) . $_syscur['shortname'] . "</span><span>{$elem_val['doc_usr_name']}</span></span>";
						echo "</a>";
					}
				}
			}
		}
		echo '<script type="text/javascript">
			$(function(){
				$(".role-templatelink,#jQlinkNewDoc").Template();
			});
		</script>';
	}
}

if ($h__requested_with_ajax || isset($_POST['TemplateCallback'])) {
	SidePanelContent(
		$app,
		$fs(234)->dir,
		$c__settings['site']['title'] . " - " . $fs(234)->title,
		$fs(233)->dir,
		$c__settings['site']['title'] . " - " . $fs(233)->title
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
	$fs(234)->dir,
	$c__settings['site']['title'] . " - " . $fs(234)->title,
	$fs(233)->dir,
	$c__settings['site']['title'] . " - " . $fs(233)->title
);
echo $_SIDE->BodyEnd()
?>