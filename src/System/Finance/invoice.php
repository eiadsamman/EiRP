<?php

declare(strict_types=1);

namespace System\Finance;

use Exception;
use mysqli_result;
use System\Template\Gremium\Gremium;


class DocumentException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class DocumentMaterialListException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class DocumentId extends Exception
{
	public function errorPlot()
	{
		$grem = new Gremium(true);
		$grem->header()->serve("<h1>Invalid request!</h1>");
		$grem->article()->open();
		echo ("<span class=\"flex\">Document ID Token is invalid, one or more of the following might be the cause:</span>");
		echo ('<ul>
			<li>Session has expired</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			<ul>');
		$grem->getLast()->close();
		$grem->terminate();
	}
}
class DocumentToken extends Exception
{
	public function errorPlot()
	{
		$grem = new Gremium(true);
		$grem->title()->serve("<h1>Invalid request!</h1>");
		$grem->article()->open();
		echo ("<span class=\"flex\">Document ID Token is invalid, one or more of the following might be the cause:</span>");
		echo ('<ul>
			<li>Session has expired</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			<ul>');
		$grem->getLast()->close();
		$grem->terminate();
	}
}




class Invoice
{
	private const permissionlist_pageid = 246;
	private $permission_list = array();
	public $listview_rows = 0;
	public static $decimal_precision = 5;
	protected \System\App $app;
	public const map = array(
		"MAT_REQ"	=> 1,
		"PUR_QUT"	=> 2,
		"PUR_ORD"	=> 3,
		"GRIR"		=> 4,
		"PUR_INV"	=> 5
	);

	public function __construct(&$app)
	{
		$this->app = $app;
	}


	public static function DecimalsNumber(float $number): int
	{
		$output = 0;
		if ((int)$number == $number) {
			$output = 3;
		} else if (!is_numeric($number)) {
			$output = 0;
		} else {
			$output = strlen((string)$number) - strrpos((string)$number, ".") - 1;
		}
		return ($output > Invoice::$decimal_precision ? Invoice::$decimal_precision : $output);
	}

	public function GetNextSerial(int $docType, int $company, int $costcenter): int
	{
		$query = 
			"SELECT 
				IFNULL(MAX(po_serial) , 0) + 1 AS doc_serial
			FROM 
				inv_main
			WHERE 
				po_type = $docType AND po_comp_id=$company AND po_costcenter=$costcenter;";

		$r = $this->app->db->query($query);
		if ($r && $row = $r->fetch_assoc()) {
			return (int)$row['doc_serial'];
		} else {
			return 0;
		}
	}

	private function BuildPermissionList()
	{
		/*$_u_persmission = App::$user['permissions'];
		$r = $this->app->db->query("
			SELECT 
				trd_id,pfp_value,pfp_per_id
			FROM 
				pagefile_permissions
					JOIN pagefile ON trd_id = pfp_trd_id
			WHERE
				trd_parent = 246 AND pfp_per_id = $_u_persmission;");
		while($row = $r->fetch_assoc( )){
			$this->permission_list[$row['trd_id']]=new AllowedActions($_u_persmission, array($row['pfp_per_id']=>$row['pfp_value']));
		}*/
	}

	public function Per(int $pagefile_id)
	{
		if (isset($this->permission_list[$pagefile_id])) {
			return $this->permission_list[$pagefile_id];
		}
	}




	public function DocumentURI(): int|bool
	{
		try {
			if (!isset($_GET['docid']) || (int)$_GET['docid'] == 0) {
				
				throw new DocumentId("No document ID provided", 30001);
			} else if (md5("sysdoc_" . $_GET['docid'] . session_id()) != $_GET['token']) {
				throw new DocumentToken("Invlaid token", 30002);
			} else {
				return (int) $_GET['docid'];
			}
		} catch (DocumentId $e) {
			$e->errorPlot();
			return false;
		} catch (DocumentToken $e) {
			$e->errorPlot();
			return false;
		}
	}

	public function GetDocChildren(int $doc_id)
	{
		$rpo = $this->app->db->query($this->DocProccess($doc_id, 0, true));
		if ($rpo) {
			return $rpo;
		} else {
			throw new DocumentException("Requested document not found", 31001);
		}
	}

	public function Chain(int $doc_id): array
	{
		$chain = array();
		$chain[] = $doc_id;
		$current = $doc_id;
		$safety = 0;
		while ($current !== false) {
			$mysqli_result = $this->app->db->query("SELECT po_rel FROM inv_main WHERE po_id = $current;");
			if ($mysqli_result and $mysqli_record = $mysqli_result->fetch_assoc() and !is_null($mysqli_record['po_rel'])) {
				$current = $mysqli_record['po_rel'];
				$chain[] = $mysqli_record['po_rel'];
			} else {
				$current = false;
			}
			$safety++;
			if ($safety > 10) {
				break;
			}
		}
		return array_reverse($chain);
	}




	public function GetDocValue(int $doc_id): float|bool
	{
		$r = $this->app->db->query("
			SELECT
				SUM(pols_price * pols_issued_qty) AS doc_value 
			FROM
				inv_main
					JOIN inv_records ON po_id = pols_po_id
			WHERE
				po_id=$doc_id 
			");
		if ($r && $row = $r->fetch_assoc()) {
			if (is_null($row['doc_value'])) {
				return false;
			} else {
				return $row['doc_value'];
			}
		} else {
			return false;
		}
	}
	public function GetMaterialRequestDoc(int $doc_id): array|bool
	{
		return $this->GetGeneralDoc($doc_id, Invoice::map['MAT_REQ']);
	}
	public function GetPurchaseQuotationDoc(int $doc_id): array|bool
	{
		return $this->GetGeneralDoc($doc_id, Invoice::map['PUR_QUT']);
	}
	public function GetPurchaseOrderDoc(int $doc_id): array|bool
	{
		return $this->GetGeneralDoc($doc_id, Invoice::map['PUR_ORD']);
	}
	public function GetGRIRDoc(int $doc_id): array|bool
	{
		return $this->GetGeneralDoc($doc_id, Invoice::map['GRIR']);
	}

	private function GetGeneralDoc(int $doc_id, int $doc_type, bool $children = false): array|bool
	{
		$r = $this->app->db->query($this->DocProccess($doc_id, $doc_type, $children));
		if ($r && $row = $r->fetch_assoc()) {
			$row['doc_value'] = $this->GetDocValue($doc_id);
			$row['po_remarks'] = stripcslashes(nl2br($row['po_remarks']));
			return $row;
		} else {
			throw new DocumentException("Requested document not found", 31001);
		}
	}



	private function DocProccess(int $doc_id, int $type, bool $children = false): string
	{
		$output =
			"SELECT 
				po_id, po_title, po_att_id, po_remarks,po_comp_id,po_cur_id,po_type,
				po_total,po_vat_rate,po_additional_amount,po_discount,cur_shortname,
				po_serial,po_att_id,
				
				usr_issue_join.usr_id AS doc_usr_id,
				
				_benf_comp.comp_id AS comp_id,
				_benf_comp.comp_name,
				
				_dest_comp.comp_id AS comp_dest_id,
				_dest_comp.comp_name AS comp_dest_name,
				
				DATE_FORMAT(po_date,'%H:%i %W, %M %d, %Y') AS po_date,
				DATE_FORMAT(po_due_date,'%Y-%m%-%d') AS po_due_date, po_close_date,
				
				CONCAT_WS(' ',usr_issue_join.usr_firstname,usr_issue_join.usr_lastname) AS po_usr_name,
				CONCAT_WS(' ',usr_att_join.usr_firstname,usr_att_join.usr_lastname) AS po_att_name,
				
				ccc_id,ccc_name,ccc_vat,po_benf_comp_id
				
			FROM
				inv_main
					JOIN companies AS _benf_comp ON po_benf_comp_id = _benf_comp.comp_id
					JOIN companies AS _dest_comp ON po_comp_id = _dest_comp.comp_id
					JOIN inv_records ON po_id = pols_po_id
					JOIN inv_costcenter ON ccc_id = po_costcenter
					JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id=" . $this->app->user->info->id . "
					
					JOIN users AS usr_issue_join ON usr_issue_join.usr_id = po_usr_id
					LEFT JOIN users AS usr_att_join ON usr_att_join.usr_id = po_att_id
					LEFT JOIN currencies ON cur_id = po_cur_id
			WHERE ";

		if ($children) {
			$output .= "po_rel = $doc_id ";
		} else {
			$output .= "po_id = $doc_id AND po_type = $type ";
		}

		$output .= " GROUP BY po_id ";
		return $output;
	}

	public function DocGetMaterialList(int $doc_id): mysqli_result|bool
	{
		$r = $this->app->db->query(
			"SELECT 
				pols_id,pols_bom_part,pols_issued_qty,pols_price,pols_discount,
				_mat_materials.mat_long_id,_mat_materials.mat_name,_mat_materials.cat_alias,_mat_materials.mattyp_name,_mat_materials.unt_name,_mat_materials.unt_decim,
				CONCAT(_part_of.mat_long_id,'<br />',_part_of.cat_alias,', ',_part_of.mat_name) AS _mat_bom
			FROM
				inv_records 
				JOIN(
					SELECT
						mat_id, mat_long_id,mat_name,cat_alias,mattyp_name,unt_name,unt_decim
					FROM
						mat_materials 
							JOIN mat_materialtype ON mattyp_id=mat_mattyp_id  
							JOIN mat_unit ON unt_id = mat_unt_id
							LEFT JOIN 
								(
									SELECT 
										CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
									FROM 
										mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
								) AS _category ON mat_matcat_id=_category.matcat_id
				) AS _mat_materials ON _mat_materials.mat_id = pols_item_id
				
				LEFT JOIN(
					SELECT
						mat_id,mat_long_id,mat_name,cat_alias
					FROM
						mat_materials 
							LEFT JOIN 
								(
									SELECT 
										CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
									FROM 
										mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
								) AS _category ON mat_matcat_id=_category.matcat_id
				) AS _part_of ON _part_of.mat_id = pols_bom_part
			WHERE
				pols_po_id=$doc_id AND pols_issued_qty>0
			ORDER BY
				pols_bom_part,pols_id
			"
		);
		if ($r) {
			return $r;
		} else {
			throw new DocumentMaterialListException("Material List query failed", 31002);
		}
	}

	public function __toString(): string
	{
		return "";
	}
}
