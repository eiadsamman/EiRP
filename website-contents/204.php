<?php
include_once("admin/class/materials.php");
$material = new Materials();



$wo_id = false;
$fillarr = array("info" => array(), "list" => array());
if (isset($_POST['wo_id']) || isset($_GET['wo_id'])) {
	$wo_id = (isset($_POST['wo_id'])  ?  (int)$_POST['wo_id']  : (isset($_GET['wo_id']) ? (int)$_GET['wo_id'] : false));

	$r = $app->db->query("SELECT 
			wo_id,wo_title,wo_date,wo_due_date,wo_close_date,wo_remarks,
			_c_prt._wo_site,
			CONCAT_WS(' ',COALESCE(_a_usr.usr_firstname,''),IF(NULLIF(_a_usr.usr_lastname, '') IS NULL, NULL, _a_usr.usr_lastname)) AS wo_creator_name,
			_a_usr.usr_id AS wo_creator_id,
			
			CONCAT_WS(' ',COALESCE(_b_usr.usr_firstname,''),IF(NULLIF(_b_usr.usr_lastname, '') IS NULL, NULL, _b_usr.usr_lastname)) AS wo_manager_name,
			_b_usr.usr_id AS wo_manager_id
		FROM 
			mat_wo 
				JOIN users AS _a_usr ON _a_usr.usr_id = wo_creator_id
				LEFT JOIN users AS _b_usr ON _a_usr.usr_id = wo_manager
				JOIN 
				(SELECT CONCAT (\"[\", cur_shortname , \"] \" , comp_name, \": \", prt_name) AS _wo_site,prt_id 
					FROM `acc_accounts`  
						JOIN currencies ON cur_id=prt_currency
						JOIN companies ON prt_company_id=comp_id
				) AS _c_prt ON _c_prt.prt_id = wo_site
		WHERE
			wo_id = $wo_id
			");

	if ($r) {
		if ($row = $r->fetch_assoc()) {
			$fillarr['info'] = $row;
			/*Gather material information*/
			$fillarr['list'] = $material->WOMaterials($row['wo_id']);
		} else {
			exit;
		}
	} else {
		exit;
	}
} else {
	exit;
}

$fillarr['info']['wo_due_date'] = is_null($fillarr['info']['wo_due_date']) ? "-" : $fillarr['info']['wo_due_date'];
$fillarr['info']['wo_close_date'] = is_null($fillarr['info']['wo_close_date']) ? "<i>[Still open]</i>" : $fillarr['info']['wo_due_date'];
$fillarr['info']['wo_creator_name'] = is_null($fillarr['info']['wo_creator_id']) ? "-" : $fillarr['info']['wo_creator_id'] . ": " . $fillarr['info']['wo_creator_name'];
$fillarr['info']['wo_manager_name'] = is_null($fillarr['info']['wo_manager_id']) ? "-" : $fillarr['info']['wo_manager_id'] . ": " . $fillarr['info']['wo_manager_name'];
$fillarr['info']['wo_remarks'] = is_null($fillarr['info']['wo_remarks']) || trim($fillarr['info']['wo_remarks']) == "" ? "-" : $fillarr['info']['wo_remarks'];

?>
<table>
	<tbody>
		<tr class="special">
			<td colspan="2">Work Order Status</td>
		</tr>
		<tr>
			<th>ID</th>
			<td width="100%"><?php echo $fillarr['info']['wi_id']; ?></td>
		</tr>
		<tr>
			<th>Creation Date</th>
			<td><?php echo $fillarr['info']['wo_date']; ?></td>
		</tr>
		<tr>
			<th>Due Date</th>
			<td><?php echo $fillarr['info']['wo_due_date']; ?></td>
		</tr>
		<tr>
			<th>Creator</th>
			<td><?php echo $fillarr['info']['wo_creator_name']; ?></td>
		</tr>
		<tr>
			<th>Production Plant</th>
			<td><?php echo $fillarr['info']['_wo_site']; ?></td>
		</tr>
		<tr>
			<th>Manager</th>
			<td><?php echo $fillarr['info']['wo_manager_name']; ?></td>
		</tr>

		<tr>
			<th>Closeing Date</th>
			<td><?php echo $fillarr['info']['wo_close_date']; ?></td>
		</tr>

		<tr>
			<th>Remarks</th>
			<td><?php echo $fillarr['info']['wo_remarks']; ?></td>
		</tr>

	</tbody>
</table>
<style type="text/css">
	.CSSTablePO>tbody>tr>td:nth-child(1n+2) {
		text-align: right;
	}

	.CSSTablePO>tbody>tr>td:nth-child(1n+7) {
		text-align: left;
	}
</style>
<table class="CSSTablePO" style="margin-top: 15px;">
	<thead>
		<tr class="special">
			<td colspan="13">Work Order Materials Description</td>
		</tr>
		<tr>
			<td>#</td>
			<td>Quantity</td>
			<td>Received</td>
			<td>Produced</td>
			<td>Scraped</td>
			<td>Delivered</td>
			<td>Unit</td>
			<td>Material Code</td>
			<td>Part Number</td>
			<td width="100%">Description</td>
			<td>Type</td>
		</tr>
	</thead>
	<tbody>
		<?php
		$cnt = 0;
		foreach ($fillarr['list'] as $si_k => $si_v) {
			$cnt++;
			echo "<tr>";
			echo "<td>$cnt</td>";
			echo "<td>" . number_format($si_v['wol_qty'], $si_v['unt_decim'], ".", ",") . "</td>";
			echo "<td>0</td>";
			echo "<td>0</td>";
			echo "<td>0</td>";
			echo "<td>0</td>";
			echo "<td>{$si_v['unt_name']}</td>";
			echo "<td>{$si_v['mat_long_id']}</td>";
			echo "<td>{$si_v['mat_pn']}</td>";
			echo "<td>{$si_v['mat_description']}</td>";
			echo "<td>{$si_v['mattyp_name']}</td>";
			echo "</tr>";
		}
		?>
	</tbody>
</table>