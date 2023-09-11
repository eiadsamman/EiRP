<?php
if(isset($_POST['id'])){
	$transaction_id=isset($_POST['id'])?(int)$_POST['id']:null;
}elseif(isset($_GET['id'])){
	$transaction_id=isset($_GET['id'])?(int)$_GET['id']:null;
}
if(is_null($transaction_id)){
	exit;
}

$arr_transaction=null;
if($r=$app->db->query("
	SELECT 
		acm_id,acm_usr_id,acm_editor_id,UNIX_TIMESTAMP(acm_ctime) AS acm_ctime,acm_type,acm_beneficial,acm_comments,acm_reference,
		_category._catname,_category._catid,acctyp_name,
		CONCAT_WS(' ',COALESCE(_usr.usr_firstname,''),IF(NULLIF(_usr.usr_lastname, '') IS NULL, NULL, _usr.usr_lastname)) AS _usrname,
		CONCAT_WS(' ',COALESCE(_editor.usr_firstname,''),IF(NULLIF(_editor.usr_lastname, '') IS NULL, NULL, _editor.usr_lastname)) AS _editorname,
		acm_rejected,
		acm_realvalue, cur_name AS realcurrency
	FROM 
		acc_main 
			LEFT JOIN 
			(
				SELECT
					acccat_id AS _catid,CONCAT(accgrp_name,\": \",acccat_name) AS _catname
				FROM
					acc_categories JOIN acc_categorygroups  ON acccat_group=accgrp_id
			) AS _category ON _category._catid=acm_category
			LEFT JOIN users AS _usr ON _usr.usr_id=acm_usr_id
			LEFT JOIN users AS _editor ON _editor.usr_id=acm_editor_id
			LEFT JOIN acc_transtypes ON acctyp_type=acm_type
			LEFT JOIN currencies ON cur_id=acm_realcurrency
	WHERE 
		acm_id=$transaction_id;")){
	if($row=$r->fetch_assoc()){
		$arr_transaction=$row;
	}
}
if(is_null($arr_transaction)){
	echo "Invalid transaction ID";
	exit;
}

$arr_transaction['transactions']=array();
if($r=$app->db->query("
	SELECT 
		atm_id,atm_account_id,atm_value,atm_dir,cur_name,cur_symbol,prt_name,cur_id
	FROM
		`acc_accounts`
			RIGHT JOIN acc_temp ON prt_id=atm_account_id
			LEFT JOIN 
				currencies ON cur_id = prt_currency
	WHERE
		atm_main={$arr_transaction['acm_id']}")){
	while($row=$r->fetch_assoc()){
		$arr_transaction['transactions'][$row['atm_dir']]=$row;
	}
}
?>
<input type="hidden" id="jQtransactionID" value="<?php echo $arr_transaction['acm_id'];?>" />
<table class="bom-table" id="jQformTable">
<thead>
	<tr class="special"><td colspan="4">Displaying transaction statement `<?php echo $arr_transaction['acm_id'];?>`</td></tr>
</thead>
<tbody>
<tr>
	<th>Type</th>
	<td><?php echo $arr_transaction['acctyp_name'];?></td>
	<th>Status</th>
	<td>
		<?php echo $arr_transaction['acm_rejected']==1?"<span style=\"color:#f03\">Canceled</span>":"Active";?>
	</td>
</tr>
<tr>
	<th style="min-width:100px">Creditor</th>
	<td width="50%"><?php echo $arr_transaction['transactions'][0]['prt_name']." (".$arr_transaction['transactions'][0]['cur_symbol'].")";?></td>
	<th style="min-width:100px">Beneficial</th>
	<td width="50%"><?php echo $arr_transaction['acm_beneficial'];?></td>
</tr>
<tr>
	<th>Date</th>
	<td><?php echo date("F d,Y",$arr_transaction['acm_ctime']);?></td>
	<th>Reference</th>
	<td><?php echo $arr_transaction['acm_reference'];?></td>
</tr>
<tr>
	<th>Debitor</th>
	<td><?php echo $arr_transaction['transactions'][1]['prt_name']." (".$arr_transaction['transactions'][1]['cur_symbol'].")";?></td>
	<th>Employee ID</th>
	<td><?php echo $arr_transaction['acm_usr_id']==0?"-":$arr_transaction['acm_usr_id'].", ".$arr_transaction['_usrname'];?></td>
</tr>
<tr>
	<th>Category</th>
	<td><?php echo $arr_transaction['_catname'];?></td>
	<th rowspan="2">Comments</th>
	<td rowspan="2" valign="top"><div style="max-height:55px;overflow:auto"><?php echo !is_null($arr_transaction['acm_comments'])?nl2br($arr_transaction['acm_comments']):"";?></div></td>
</tr>
<tr>
	<th>Value</th>
	<td><?php echo number_format(abs($arr_transaction['acm_realvalue']),2,".",",");?>
	<?php echo $arr_transaction['realcurrency'];?>
	</td>
</tr>
<?php echo isset($_GET['ajax'])?"<tr><td colspan=\"4\"><div class=\"btn-set\" style=\"justify-content:center\"><button id=\"jQpopupCancel\">Close</button></div></td></tr>":"";?>
</tbody></table>
<script>
	$(document).ready(function(e) {
		$("#jQpopupCancel").on('click',function(){
			popup.hide();
		});
	});
</script>