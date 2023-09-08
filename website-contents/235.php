<?php
	require_once("admin/class/invoice.php");
	require_once("admin/class/accounting.php");
	$invoice= new Invoice();
	$invoice->BuildDocumentNumberPrefixList();
	$accounting=new Accounting();
	$_syscur=$accounting->system_default_currency();
?>
<table class="bom-table">
<thead>
	<tr>
		<td>ID</td>
		<td>Creation Date</td>
		<td>Company</td>
		<td>Title</td>
		<td>Due Date</td>
		<td>Status</td>
		<td>Quotations</td>
		<td style="width:100%;"></td>
	</tr>
</thead>	
<tbody>
<?php 
	$r=$sql->query("
		SELECT 
			_main.po_id,
			_main.po_title,
			DATE_FORMAT(_main.po_date,'%Y-%m-%d') AS po_date,
			DATE_FORMAT(_main.po_due_date,'%Y-%m-%d') AS po_due_date,
			_main.po_close_date,
			_main.po_usr_id,comp_name
		FROM
			inv_main AS _main
				JOIN users ON usr_id = _main.po_usr_id
				JOIN companies ON comp_id = _main.po_comp_id
		WHERE 
			_main.po_type = 3
		GROUP BY
			_main.po_id
		");
	
	if($r){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr>";
			echo "<td><a href=\"".$tables->pagefile_info(237,null,"directory")."/?docid={$row['po_id']}&token=".md5("sysdoc_".$row['po_id'].session_id())."\">".$invoice->TranslatePrefix(1,$row['po_id'])."</a></td>";
			echo "<td>{$row['po_date']}</td>";
			echo "<td>{$row['comp_name']}</td>";
			echo "<td>{$row['po_title']}</td>";
			echo "<td>{$row['po_due_date']}</td>";
			echo "<td>".(is_null($row['po_close_date'])?"Open":"Closed")."</td>";
			echo "<td>{$row['_qcount']}</td>";
			echo "<td></td>";
			echo "</tr>";
			
		}
	}
?>
</tbody>
</table>