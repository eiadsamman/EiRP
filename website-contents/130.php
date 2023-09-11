<?php

$abs = false;
if (isset($_GET['id'])) {
	$_GET['id'] = (int)$_GET['id'];
	$r = $app->db->query("
	SELECT
		lbr_abs_id,lbr_abs_lbr_id,lbr_abs_usr_id,UNIX_TIMESTAMP(lbr_abs_issue_date) AS lbr_abs_issue_date,lbr_abs_days,lbr_abs_comments,lbr_abs_type,
		(SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) FROM users WHERE usr_id=lbr_abs_usr_id) AS signername,
		CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,''))  AS empname,
		DATE_ADD(lbr_abs_start_date, INTERVAL lbr_abs_days DAY) AS returndate,
		usr_id,abs_typ_name,UNIX_TIMESTAMP(lbr_abs_start_date) AS lbr_abs_start_date
	FROM
		labour_absence_request
			LEFT JOIN 
				users ON usr_id=lbr_abs_lbr_id
			LEFT JOIN
				absence_types ON abs_typ_id=lbr_abs_type
	WHERE
		lbr_abs_id={$_GET['id']}
	");
	if ($r && $row = $r->fetch_assoc()) {
		$abs = $row;
	}
}
if ($abs) {
?>
	<style>
		.absence-print-form {
			border-collapse: collapse;
			width: 100%;
			padding: 0;
		}

		.absence-print-form>thead>tr>td {
			border: solid 1px #bbb;
			padding: 10px;
		}

		.absence-print-form>tbody>tr>td {
			border: solid 1px #bbb;
			padding: 0;
		}

		.detailed_row {
			display: -webkit-box;
			display: -moz-box;
			display: -ms-flexbox;
			display: -webkit-flex;
			display: flex;

		}

		.detailed_row>div {
			-webkit-box-flex: 1;
			-moz-box-flex: 1;
			-webkit-flex: 1;
			-ms-flex: 1;
			flex: 1;
		}

		.detailed_row>div.gap {
			max-width: 6px;
			border-left: solid 1px #ccc;
			margin-left: 5px;
		}

		.detailed_col {
			display: -webkit-box;
			display: -moz-box;
			display: -ms-flexbox;
			display: -webkit-flex;
			display: flex;
			padding: 5px;
			position: relative;
		}

		.detailed_col>div {
			text-align: center;
			white-space: nowrap;
			position: absolute;
			left: 0px;
			right: 0px;
		}

		.detailed_col>span {
			color: #777;
			white-space: nowrap;
			padding: 0px 5px;
		}

		.detailed_col>b {
			padding: 0px 10px;
		}

		.detailed_col>span.stretch {
			-webkit-box-flex: 1;
			-moz-box-flex: 1;
			-webkit-flex: 1;
			-ms-flex: 1;
			flex: 1;

		}
	</style>
	<table class="absence-print-form">
		<thead>
			<tr>
				<td align="center">طلب إجازة<br />Absence Request</td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<div class="detailed_row">
						<div class="detailed_col">
							<span>Employee Name</span><span class="stretch"></span>
							<div><?php echo $abs['empname']; ?></div><span>اسم الموظف</span>
						</div>
						<div class="gap"></div>
						<div class="detailed_col" style="min-width:33%;max-width:33%">
							<span>No.</span><span class="stretch"></span>
							<div><?php echo $abs['usr_id']; ?></div><span>رقم الموظف</span>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="detailed_row">
						<div class="detailed_col">
							<span>Issue date</span><span class="stretch"></span>
							<div style="min-width:130px;"><?php echo date("Y-m-d H:i", $abs['lbr_abs_issue_date']); ?></div><span>تاريخ التحرير</span>
						</div>
						<div class="gap"></div>
						<div class="detailed_col" style="min-width:33%;max-width:33%">
							<span>ID#</span><span class="stretch"></span>
							<div>AR<?php echo $abs['lbr_abs_id']; ?></div><span>رقم الطلب</span>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="detailed_row">
						<div class="detailed_col">
							<span>First day of absence</span><span class="stretch"></span>
							<div><?php echo date("Y-m-d", $abs['lbr_abs_start_date']); ?></div><span>تاريخ بداية الإجازة</span>
						</div>
						<div class="gap"></div>
						<div class="detailed_col" style="min-width:33%;max-width:33%">
							<span>Reason</span><span class="stretch"></span>
							<div><?php echo $abs['abs_typ_name']; ?></div><span>سبب الإجازة</span>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="detailed_row">
						<div class="detailed_col">
							<span>Return to work date</span><span class="stretch"></span>
							<div><?php echo $abs['returndate']; ?></div><span>تاريخ العودة للعمل</span>
						</div>
						<div class="gap"></div>
						<div class="detailed_col" style="min-width:33%;max-width:33%">
							<span>Days</span><span class="stretch"></span>
							<div style="min-width:130px;"><?php echo $abs['lbr_abs_days']; ?></div><span>أيام الإجازة</span>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td style="border:none;height:5px;"></td>
			</tr>

			<tr>
				<td>
					<div class="detailed_row">
						<div class="detailed_col">
							<span>Comments</span><span class="stretch"></span>
							<div><?php echo $abs['lbr_abs_comments']; ?></div>
						</div>

					</div>
				</td>
			</tr>

			<tr>
				<td style="border:none;height:5px;"></td>
			</tr>
			<tr>
				<td>
					<div class="detailed_row" style="height:120px;">
						<div class="detailed_col">
							<span>&nbsp;</span>
							<div>مقدم الطلب<br /><?php echo $abs['empname']; ?></div>
						</div>
						<div class="gap"></div>
						<div class="detailed_col" style="min-width:33%;max-width:33%">
							<span>&nbsp;</span>
							<div>محرر الطلب<br /><?php echo $abs['signername']; ?></div>
						</div>
						<div class="gap"></div>
						<div class="detailed_col" style="min-width:33%;max-width:33%">
							<span>&nbsp;</span>
							<div>المسؤول المشرف<br /><?php echo ""; ?></div>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td style="border:none;height:15px;border-bottom:dashed 1px #ccc;"></td>
			</tr>
			<tr>
				<td style="border:none;" align="right">
					<div class="detailed_row">
						<div class="detailed_col">
							<span></span><span class="stretch"></span><span>يحتفظ مقدم الطلب بهذا الجزء</span>
						</div>
					</div>
					<div class="detailed_row">
						<div class="detailed_col">
							<span>رقم الطلب</span><b><?php echo $abs['lbr_abs_id']; ?></b>
							<span>اسم مقدم الطلب</span><b><?php echo $abs['usr_id'] . "# " . $abs['empname']; ?></b>
							<span>تاريخ التقديم</span><b><?php echo date("Y-m-d", $abs['lbr_abs_issue_date']); ?></b>
							<span class="stretch"></span>
							<b>توقيع المشرف</b>
						</div>
					</div>
					<div class="detailed_row">
						<div class="detailed_col">
							<span>تاريخ بداية الاجازة</span><b><?php echo date("Y-m-d", $abs['lbr_abs_start_date']); ?></b>
							<span>عدد أيام الاجازة</span><b><?php echo $abs['lbr_abs_days']; ?></b>
							<span>تاريخ العودة للعمل</span><b><?php echo $abs['returndate']; ?></b>

						</div>

					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<script>
		window.onload = function() {
			window.print()
		};
	</script>
<?php
} else {
?>

	<script>
		window.onload = function() {
			alert("Uknown Request ID");
		};
	</script>
<?php } ?>