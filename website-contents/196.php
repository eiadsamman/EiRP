<?php

//$app->db->query("TRUNCATE `mat_materials`");
//$app->db->query("ALTER TABLE `mat_materials` AUTO_INCREMENT=1001;");
include_once("admin/class/materials.php");
$material = new Materials();


$output = array();
$output_cnt = 0;
if (isset($_POST['bulkinput'])) {
	$input = $_POST['bulkinput'];
	$input_row = explode("\r\n", $_POST['bulkinput']);

	foreach ($input_row as $input_line) {
		$output_cnt++;
		$input_col = explode("\t", $input_line);
		if (sizeof($input_col) == 8) {


			$ins_res = $material->Create(array(
				"part_number" => $input_col[1],
				"vendor_code" => $input_col[6],
				"ean_code" => $input_col[2],
				"unit" => $input_col[3],
				"desc" => $input_col[7],
				"date" => $input_col[4],
				"type" => $input_col[0],
				"vendor_id" => $input_col[5],
				"thershold" => 0,
			));

			if ($ins_res) {
				$output[$output_cnt] = $ins_res;
			} else {
				$output[$output_cnt] = "Failed" . $app->db->error;
			}
		} else {
			$output[$output_cnt] = "Columns count aren't valid";
		}
	}
}

?>
<form action="" method="POST">
	<table>
		<tbody>
			<tr class="special">
				<td colspan="8">Bulk materials insertion</td>
			</tr>
			<tr>
				<td colspan="8">
					<h2>Insertion map</h2>
				</td>
			</tr>
			<tr>
				<th>Materail Type ID</th>
				<th>Part Number</th>
				<th>EAN Code</th>
				<th>UNIT</th>
				<th>Date</th>
				<th>Vendor ID</th>
				<th>Vendor Serial</th>
				<th width="100%">Description</th>
			</tr>
			<tr>
				<td colspan="8">
					<div class="btn-set">
						<textarea name="bulkinput" style="width:100%;height: 400px;"><?php echo isset($_POST['bulkinput']) ? $_POST['bulkinput'] : ""; ?></textarea>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="8">
					<div class="btn-set">
						<button type="submit">Submit</button>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<br />

	<table>
		<tbody>
			<tr class="special">
				<td>Output result</td>
			</tr>
			<tr>
				<td>
					<?php
					if ($output_cnt > 0) {
						foreach ($output as $line) {
							echo $line . "<br />";
						}
					}
					?>
				</td>
			</tr>
		</tbody>
	</table>


</form>