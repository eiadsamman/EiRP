<?php

use System\Finance\Accounting;
use System\Finance\DocumentException;
use System\Finance\DocumentMaterialListException;
use System\Finance\Invoice;

//purchase/rm/new

$accounting = new Accounting($app);
$_syscur    = $accounting->system_default_currency();
$invoice    = new Invoice($app);


//$doc_id = $invoice->DocumentURI();
if (false) {
	/* try {

				 $doc_mr = $invoice->GetMaterialRequestDoc($doc_id);


				 $_TEMPLATE->Title("Material Request Record", null, $app->translatePrefix(Invoice::map['MAT_REQ'], $doc_mr['po_serial']));

				 echo $_TEMPLATE->CommandBarStart();
				 echo "<div class=\"btn-set\">";
				 echo "<a style=\"color:#333;\" href=\"" . $fs(240)->dir . "/\" class=\"bnt-back\"></a>";

				 echo "<span>" . $app->translatePrefix(Invoice::map['MAT_REQ'], $doc_mr['po_serial']) . "</span>";

				 echo "<span class=\"gap\"></span>";
				 if (is_null($doc_mr['po_close_date'])) {
					 echo "<button>Cancel Request</button>";
					 echo "<a class=\"clr-green\" href=\"" . $fs(233)->dir . "/?docid={$doc_mr['po_id']}&token=" . md5("sysdoc_" . $doc_id . session_id()) . "\">New Quotation</a>";
				 } else {
					 echo "<span>Request Closed</span>";
				 }
				 echo "</div>";
				 echo $_TEMPLATE->CommandBarEnd();



				 $_TEMPLATE->NewFrameTitle("<span class=\"flex\">Request Information</span>");
				 $_TEMPLATE->NewFrameBody('
					 <div class="template-gridLayout">
						 <div><span>Cost Center</span><div>' . $doc_mr['ccc_name'] . '</div></div>
						 <div><span>Number</span><div>' . $app->translatePrefix(Invoice::map['MAT_REQ'], $doc_mr['po_serial']) . '</div></div>
						 <div><span>Title</span><div>' . $doc_mr['po_title'] . '</div></div>
					 </div>
					 <div class="template-gridLayout">
						 <div><span>Creation Date</span><div>' . $doc_mr['po_date'] . '</div></div>
						 <div></div>
						 <div></div>
					 </div>
					 <div class="template-gridLayout">
						 <div>
							 <span>Placed By</span>
							 <div>' . $app->translatePrefix(11, $doc_mr['po_att_id']) . ' - ' . $doc_mr['po_att_name'] . '</div>
						 </div>
					 </div>
					 <div class="template-gridLayout">
						 <div><span>Remarks</span><div>' . nl2br($doc_mr['po_remarks']) . '</div></div>
					 </div>
					 ');

				 $mysqli_result = $invoice->DocGetMaterialList($doc_id);
				 $_TEMPLATE->NewFrameTitle("<span class=\"flex\">Requested Materials</span>");
				 echo $_TEMPLATE->NewFrameBodyStart();
				 echo '<table>
					 <thead><tr><td width="100%">Material</td><td align="right" colspan="2">Quantity</td></tr></thead>
					 <tbody id="jQmaterialList" style="border:solid 1px #E6E6EB;">';
				 if ($mysqli_result) {
					 $bomlegend = null;
					 while ($row = $mysqli_result->fetch_assoc()) {
						 if ($bomlegend != $row['pols_bom_part']) {
							 $bomlegend = $row['pols_bom_part'];
							 echo "<tr>";
							 echo "<td colspan=\"3\" style=\"color:var(--color-link);border-left:solid 2px var(--color-link)\">{$row['_mat_bom']}</td>";
							 echo "</tr>";
						 }
						 echo "<tr>";
						 echo "
								 <td title=\"{$row['cat_alias']} {$row['mat_name']}\" style=\"padding-left:20px;\">
									 <div class=\"template-cascadeOverflow\"><div>{$row['mat_long_id']}</div></div>
									 <div class=\"template-cascadeOverflow\"><div>{$row['cat_alias']}, {$row['mat_name']}</div></td>";
						 echo "<td align=\"right\">" . number_format($row['pols_issued_qty'], $row['unt_decim'], ".", ",") . "</td>";
						 echo "<td>{$row['unt_name']}</td>";
						 echo "</tr>";
					 }
				 }
				 echo '</tbody></table>';
				 echo $_TEMPLATE->NewFrameBodyEnd();

				 $_TEMPLATE->NewFrameTitle("<span class=\"flex\">Quotation Placed</span>");
				 echo $_TEMPLATE->NewFrameBodyStart();

				 echo "<table>";
				 echo "<tr><td>ID</td><td>Issued By</td><td>Date</td><td width=\"100%\"></td></tr>";
				 echo "</thead>";

				 $rowsploted = 0;
				 $pq_type    = Invoice::map['PUR_QUT'];
				 $rm_type    = Invoice::map['MAT_REQ'];
				 $r          = $app->db->query("
						 SELECT 
							 po_id,po_rel,po_serial,
							 DATE_FORMAT(po_date,'%Y-%m-%d %H:%i') AS po_date,
							 CONCAT_WS(' ',usr_firstname,usr_lastname) AS doc_usr_name,
							 po_close_date
						 FROM
							 inv_main 
								 JOIN users ON usr_id = po_usr_id
						 WHERE
							 po_rel = $doc_id AND po_type = $pq_type
						 GROUP BY
							 po_id
						 ORDER BY po_date DESC
						 ");
				 if ($r) {
					 while ($row = $r->fetch_assoc()) {
						 $rowsploted++;
						 echo "<tr>";

						 echo "<td><a href=\"" . $fs(234)->dir . "/?docid={$row['po_id']}&token=" . md5("sysdoc_" . $row['po_id'] . session_id()) . "\">";
						 echo $app->translatePrefix($pq_type, $row['po_serial']);
						 echo "</a></td>";

						 echo "<td>{$row['doc_usr_name']}</td>";
						 echo "<td>{$row['po_date']}</td>";
						 echo "<td></td>";
						 echo "</tr>";
					 }
				 }

				 echo "</table>";

				 echo $_TEMPLATE->NewFrameBodyEnd();
			 } catch (DocumentException $e) {
				 $_TEMPLATE->Title("&nbsp;Not Found!", null, "", "mark-error");
				 $_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading material request order failed, one or more of the following might be the cause:</span>");
				 $_TEMPLATE->NewFrameBody('<ul>
					 <li>Material Request document number is invalid</li>
					 <li>Session has expired</li>
					 <li>Database query failed, contact system administrator</li>
					 <li>Permission denied or not enough privileges to proceed with this document</li>
					 <ul>');
			 } catch (DocumentMaterialListException $e) {
				 $_TEMPLATE->Title("&nbsp;Materials list error!", null, "", "mark-error");
				 $_TEMPLATE->NewFrameBody('<ul>
					 <li>Plotting materials list failed</li>
					 <ul>');
			 } catch (Exception $e) {
			 } finally {
			 } */
} else {
}
