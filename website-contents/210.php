<table>
<thead>
	<tr>
		<td>ID</td>
		<td>Company</td>
		<td>Title</td>
		<td>Posting Date</td>
		<td>Due Date</td>
		<td>Status</td>
		<td style="width:100%;">Quotations</td>
	</tr>
</thead>	
<tbody>
<?php 
	$r=$app->db->query("
		SELECT 
			po_id,
			CONCAT(prx_value,LPAD(po_id,prx_placeholder,'0')) AS doc_id,
			po_title,
			DATE_FORMAT(po_date,'%Y-%m-%d') AS po_date,
			DATE_FORMAT(po_due_date,'%Y-%m-%d') AS po_due_date,
			po_close_date,
			po_usr_id
		FROM
			inv_main
				JOIN users ON usr_id = po_usr_id
				JOIN system_prefix ON prx_id=1
		WHERE
			po_type=1 AND po_close_date IS NULL
		");
	//echo $app->db->error;
	if($r){
		while($row=$r->fetch_assoc()){
			echo "<tr>";
			echo "<td>{$row['doc_id']}</td>";
			echo "<td>-</td>";
			echo "<td>{$row['po_title']}</td>";
			echo "<td>{$row['po_date']}</td>";
			echo "<td>{$row['po_due_date']}</td>";
			
			echo "<td>".(is_null($row['po_close_date'])?"":"Closed")."</td>";
			echo "<td>0</td>";
			
			echo "</tr>";
		}
	}



?>
</tbody>
</table>