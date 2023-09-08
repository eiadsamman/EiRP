<?php
if(isset($_POST['docid'],$_POST['token'],$_POST['release']) && $_POST['release']=="true" && $_POST['token']==md5("sysdoc_".((int)$_POST['docid']).session_id())){
	$docid=(int)$_POST['docid'];
	$remarks=$sql->escape($_POST['remarks']);
	
	$newdoc=0;
	//Find Quotation
	$r=$sql->query("SELECT po_id FROM inv_main WHERE po_id=$docid");
	if($r and $row=$sql->fetch_assoc($r)){
	}else{
		header("HTTP_X_RESPONSE: ODOC");
	}
	
	
	$sql->autocommit(false);
	$r=$sql->query("
		INSERT INTO inv_main (po_type,po_comp_id,po_shipto_acc_id,po_billto_acc_id,po_att_id,po_usr_id,po_date,po_title,po_remarks,po_rel,po_total,po_vat_rate,po_tax_rate,po_additional_amount,po_discount,po_cur_id)
		SELECT 3,po_comp_id,po_shipto_acc_id,po_billto_acc_id,po_att_id,{$USER->info->id},NOW(),po_title,po_remarks,$docid,po_total,po_vat_rate,po_tax_rate,po_additional_amount,po_discount,po_cur_id
		FROM inv_main
		WHERE po_id = $docid
	");
	if($r){
		$newdoc=$sql->insert_id();
		$r &= $sql->query("
		INSERT INTO inv_records (pols_po_id ,pols_item_id ,pols_issued_qty,pols_delivered_qty,pols_price,pols_discount,pols_bom_part) 
		SELECT $newdoc,pols_item_id ,pols_issued_qty,pols_delivered_qty,pols_price,pols_discount,pols_bom_part
		FROM inv_records 
		WHERE pols_po_id = $docid
		");
		
	}
	if($r){
		$sql->commit();
		header("HTTP_X_RESPONSE: OK");
		echo "docid=$newdoc&token=".md5("sysdoc_".($newdoc).session_id());
	}else{
		$sql->fallback();
		header("HTTP_X_RESPONSE: FAIL");
		echo "Submitting pruchase order failed";
	}
	
	exit;
}

if ($h__requested_with_ajax){exit;}
?>