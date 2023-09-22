<?php

declare(strict_types=1);

namespace System\Finance;

use Exception;


class Accounting
{
	private $_opdef_info_array = false;
	private $_curdef_info_array = false;
	private $_transaction_types = array(
		1 => "Income",
		2 => "Payment",
		3 => "Transfer",
		4 => "Exchange",
	);
	protected \System\App $app;

	public function __construct(&$app)
	{
		$this->app = $app;
	}

	private $__err = array(
		7301100 => "Registerd account isn't valid",
		7301101 => "Account operation isn't set",
		7301102 => "Transaction date isn't valid",
		7301103 => "Employee not found",
		7301104 => "Exchange rates aren't set",
		7301105 => "Transaction execution failed",
		7301106 => "Transaction account is invalid",
		7301107 => "Beneficial isn't valid",
		7301108 => "Category isn't valid",
	);

	/** Accounting | Transaction
	 * New payment
	 * @param {array} $param
	 */
	public function TransactionPayment($param = array(
		"debitorAccount" => null,
		"value" => null,
		"beneficialID" => null,
		"beneficialName" => "",
		"date" => null,
		"category" => null,
		"comments" => null,
		"reference" => null,
		"relation" => null,
	))
	{



		$_param = array(
			"debitorAccount" => (int)$param['debitorAccount'],
			"value" => (float)$param['value'],
			"beneficialID" => ((int)$param['beneficialID'] == 0 ? "NULL" : (int)$param['beneficialID']),
			"beneficialName" => (trim(addslashes($param['beneficialName'])) == "" || is_null($param['beneficialName']) ? null : "'" . trim(addslashes($param['beneficialName'])) . "'"),
			"date" => false,
			"category" => (int)$param['category'],
			"comments" => (trim(addslashes($param['comments'])) == "" || is_null($param['comments']) ? null : "'" . trim(addslashes($param['comments'])) . "'"),
			"reference" => (trim(addslashes($param['reference'])) == "" || is_null($param['reference']) ? null : "'" . trim(addslashes($param['reference'])) . "'"),
			"relation" => null
		);




		if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $param['date'], $match)) {
			if (checkdate((int)$match[2], (int) $match[3], (int)$match[1])) {
				$_param['date'] = mktime(0, 0, 0, (int) $match[2], (int)$match[3], (int)$match[1]);
			}
		}
		if (false === $_param['date']) {
			throw new Exception($this->__err[7301102], 7301102);
		}




		$_value_from = false;
		$_value_to = false;
		$_exchangerate_crd = 1;
		$_exchangerate_dbt = 1;


		$r_checkCategory = $this->app->db->query("SELECT acccat_id FROM acc_categories WHERE acccat_id={$_param['category']};");
		if ($r_checkCategory->num_rows === 0) {
			throw new Exception($this->__err[7301108], 7301108);
		}


		if (is_null($_param['beneficialName'])) {
			throw new Exception($this->__err[7301107], 7301107);
		}


		$__workingaccount = $this->account_information($this->app->user->account->id);
		if ($__workingaccount === false) {
			throw new Exception($this->__err[7301100], 7301100);
		}


		$__transctionaccount = $this->account_information($_param['debitorAccount']);
		if ($__transctionaccount === false) {
			throw new Exception($this->__err[7301106], 7301106);
		}



		$_value_from = $_param['value'];
		$_value_to = $_param['value'];

		/*No need to exchange between default acocunt and its own currency, so just exchanging registered account with default account currencies*/
		if ($__transctionaccount['currency']['id'] != $__workingaccount['currency']['id']) {
			$exchangerate = $this->currency_exchange($__transctionaccount['currency']['id'], $__workingaccount['currency']['id']);
			if ($exchangerate === false) {
				throw new Exception($this->__err[7301104], 7301104);
			}
			$_value_from =		$exchangerate * $_value_from;
			$_exchangerate_dbt =	$exchangerate;
		}



		$result = true;
		$this->app->db->autocommit(false);

		$qacc_main = sprintf(
			"
			INSERT INTO acc_main (acm_usr_id,acm_editor_id,acm_ctime,acm_type,acm_beneficial,acm_category,acm_comments,acm_month,acm_realvalue,acm_realcurrency,acm_time,acm_realcurrency_crd,acm_realcurrency_dbt,acm_rel,acm_reference) 
			VALUES (%1\$s,%2\$d,%3\$s,%4\$d,%5\$s,%6\$d,%7\$s,%8\$s,%9\$f,%10\$d,%11\$s,%12\$f,%13\$f,%14\$s,%15\$s);",
			$_param['beneficialID'],
			$this->app->user->info->id,
			"FROM_UNIXTIME({$_param['date']})",
			2,
			$_param['beneficialName'],
			$_param['category'],
			$_param['comments'],
			"NULL",
			$_param['value'],
			(int)$__transctionaccount['currency']['id'],
			"FROM_UNIXTIME(" . time() . ")",
			$_exchangerate_crd,
			$_exchangerate_dbt,
			"NULL",
			(is_null($_param['reference']) ? "NULL" : $_param['reference'])
		);

		$result &= $this->app->db->query($qacc_main);
		if ($result) {
			$mainid = $this->app->db->insert_id;

			$qacc_release = sprintf("INSERT INTO acc_temp (atm_account_id,atm_value,atm_dir,atm_main) VALUES (%1\$d,%2\$f,%3\$d,%4\$d);", $__transctionaccount['id'], $_value_to, 1, $mainid);
			$result &= $this->app->db->query($qacc_release);
			if (!$result) {
				$this->app->db->rollback();
				throw new Exception($this->__err[7301105], 7301105);
			}

			$qacc_insert = sprintf("INSERT INTO acc_temp (atm_account_id,atm_value,atm_dir,atm_main) VALUES (%1\$d,%2\$f,%3\$d,%4\$d);", $__workingaccount['id'], -1 * $_value_from, 0, $mainid);
			$result &= $this->app->db->query($qacc_insert);
			if (!$result) {
				$this->app->db->rollback();
				throw new Exception($this->__err[7301105], 7301105);
			}

			if ($result) {
				$this->app->db->commit();
				return $mainid;
			} else {
				$this->app->db->rollback();
				throw new Exception($this->__err[7301105], 7301105);
			}
		} else {
			$this->app->db->rollback();
			throw new Exception($this->__err[7301105], 7301105);
		}
	}

	public function operation_default_account($operation)
	{
		$this->_opdef_info_array = false;
		if ($r = $this->app->db->query("
			SELECT 
				_inbound_acc.prt_id AS _inbound_acc_id,_inbound_acc.prt_name AS _inbound_acc_name,_inbound_acc.cur_id AS _inbound_acc_cur_id,_inbound_acc.cur_symbol AS _inbound_acc_cur_symbol,_inbound_acc.cur_shortname AS _inbound_acc_cur_shortname,_inbound_acc.cur_name AS _inbound_acc_cur_name,
				_outbound_acc.prt_id AS _outbound_acc_id,_outbound_acc.prt_name AS _outbound_acc_name,_outbound_acc.cur_id AS _outbound_acc_cur_id,_outbound_acc.cur_symbol AS _outbound_acc_cur_symbol,_outbound_acc.cur_shortname AS _outbound_acc_cur_shortname,_outbound_acc.cur_name AS _outbound_acc_cur_name,
				_cats.acccat_id,_cats.acc_catdet,accdef_operation
			FROM 
				acc_predefines 
					
					LEFT JOIN (SELECT prt_name,prt_id,cur_id,cur_symbol,cur_shortname,cur_name FROM `acc_accounts` JOIN currencies ON cur_id=prt_currency) AS _inbound_acc  ON _inbound_acc.prt_id =accdef_in_acc_id 
					LEFT JOIN (SELECT prt_name,prt_id,cur_id,cur_symbol,cur_shortname,cur_name FROM `acc_accounts` JOIN currencies ON cur_id=prt_currency) AS _outbound_acc ON _outbound_acc.prt_id=accdef_out_acc_id 
					
					JOIN (SELECT acccat_id,CONCAT_WS(\" : \",acccat_name,accgrp_name) AS acc_catdet FROM acc_categories JOIN acc_categorygroups ON acccat_group=accgrp_id) AS _cats ON _cats.acccat_id=accdef_category
			WHERE 
				accdef_name='$operation'
			;")) {
			if ($row = $r->fetch_assoc()) {

				$this->_opdef_info_array = array(

					"account" => array(
						"inbound" => array(
							"id" => $row['_inbound_acc_id'],
							"name" => $row['_inbound_acc_name'],
							"default_operation" => $row['accdef_operation'],
							"currency" => array(
								"id" => $row['_inbound_acc_cur_id'],
								"name" => $row['_inbound_acc_cur_name'],
								"symbol" => $row['_inbound_acc_cur_symbol'],
								"shortname" => $row['_inbound_acc_cur_shortname'],
							)
						),
						"outbound" => array(
							"id" => $row['_outbound_acc_id'],
							"name" => $row['_outbound_acc_name'],
							"default_operation" => $row['accdef_operation'],

							"currency" => array(
								"id" => $row['_outbound_acc_cur_id'],
								"name" => $row['_outbound_acc_cur_name'],
								"symbol" => $row['_outbound_acc_cur_symbol'],
								"shortname" => $row['_outbound_acc_cur_shortname'],
							)
						),
					),


					"category" => array("id" => $row['acccat_id'], "name" => $row['acc_catdet']),

				);
				if (is_null($row['_inbound_acc_id'])) {
					$this->_opdef_info_array['account']['inbound'] = false;
				}
				if (is_null($row['_outbound_acc_id'])) {
					$this->_opdef_info_array['account']['outbound'] = false;
				}
			}
		}
		return $this->_opdef_info_array;
	}

	public function get_currency_list()
	{

		$temp = array();
		if ($r = $this->app->db->query("SELECT cur_id,cur_name,cur_symbol,cur_default,cur_shortname FROM currencies;")) {
			while ($row = $r->fetch_assoc()) {
				$temp[$row['cur_id']] = array();
				$temp[$row['cur_id']]['name'] = $row['cur_name'];
				$temp[$row['cur_id']]['symbol'] = $row['cur_symbol'];
				$temp[$row['cur_id']]['shortname'] = $row['cur_shortname'];
				$temp[$row['cur_id']]['default'] = $row['cur_default'];
			}
		}
		return $temp;
	}

	public function account_information(int $accountId)
	{

		$temp = false;
		if ($r = $this->app->db->query(
			"SELECT 
				prt_id,prt_name,SUM(subq_acc.atm_value) AS balance,
				COUNT(subq_acc.acm_id) AS records_count,subq_acc.acm_usr_id,subq_acc.acm_rejected,
				cur_id,cur_name,cur_symbol,cur_shortname,comp_name,ptp_name,
				upr_prt_inbound,upr_prt_outbound,upr_prt_fetch,upr_prt_view
			FROM 
				`acc_accounts` 
					JOIN user_partition ON prt_id=upr_prt_id AND upr_usr_id=" . $this->app->user->info->id . " 
					JOIN currencies ON cur_id=prt_currency
					LEFT JOIN (
						SELECT acm_usr_id,atm_account_id,acm_id,atm_value,acm_rejected
						FROM acc_main JOIN acc_temp ON acm_id=atm_main
						WHERE acm_rejected=0
					) AS subq_acc ON subq_acc.atm_account_id=prt_id
					JOIN companies ON comp_id=prt_company_id
					JOIN `acc_accounttype` ON ptp_id=prt_type
			WHERE 
				prt_id=" . $accountId . "
				
			;"
		)) {
			if ($row = $r->fetch_assoc()) {
				if ($row['prt_id'] == null) {
					$temp = false;
				} else {
					$temp = array(
						"id" => $row['prt_id'],
						"name" => $row['prt_name'],
						"balance" => ((int)$row['upr_prt_view'] == 1 ? $row['balance'] : false),
						"count" => $row['records_count'],
						"company" => $row['comp_name'],
						"group" => $row['ptp_name'],
						"status" => $row['balance'] == 0 ? "" : ($row['balance'] < 0 ? "Credit" : "Debit"),
						"currency" => array(
							"id" => $row['cur_id'],
							"name" => $row['cur_name'],
							"symbol" => $row['cur_symbol'],
							"shortname" => $row['cur_shortname'],
						),
						"rule" => array(
							"inbound" => isset($row['upr_prt_inbound']) && (int)$row['upr_prt_inbound'] == 1 ? true : false,
							"outbound" => isset($row['upr_prt_outbound']) && (int)$row['upr_prt_outbound'] == 1 ? true : false,
							"fetch" => isset($row['upr_prt_fetch']) && (int)$row['upr_prt_fetch'] == 1 ? true : false,
							"view" => isset($row['upr_prt_view']) && (int)$row['upr_prt_view'] == 1 ? true : false,
						)
					);
				}
			}
		}

		return $temp;
	}

	public function system_default_currency()
	{
		$temp = false;
		if ($r = $this->app->db->query("SELECT cur_id,cur_name,cur_symbol,cur_shortname FROM currencies WHERE cur_default=1;")) {
			if ($row = $r->fetch_assoc()) {
				$temp = array(
					"id" => $row['cur_id'],
					"name" => $row['cur_name'],
					"symbol" => $row['cur_symbol'],
					"shortname" => $row['cur_shortname'],
				);
			}
		}
		return $temp;
	}

	public function account_default_currency($account)
	{

		if ($account == false) {
			return false;
		}
		if ((int)$account == 0) {
			return false;
		}
		if ($r = $this->app->db->query("
			SELECT 
				cur_name,cur_symbol,cur_shortname,cur_id
			FROM 
				`acc_accounts` 
					LEFT JOIN currencies ON cur_id = prt_currency
			WHERE
				prt_id=" . ((int)$account) . ";")) {
			if ($row = $r->fetch_assoc()) {
				$this->_curdef_info_array = array(
					"id" => $row['cur_id'],
					"name" => $row['cur_name'],
					"symbol" => $row['cur_symbol'],
					"shortname" => $row['cur_shortname'],
				);
			}
		}
		return $this->_curdef_info_array;
	}

	public function currency_exchange(int $fromCur, int $toCur): float|bool
	{

		if ($fromCur == $toCur) {
			return 1;
		}
		$output = false;
		$r = $this->app->db->query("
			SELECT (_from.curexg_value / _to.curexg_value) AS _rate 
			FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to ON _from.curexg_from = " . (int)$fromCur . " AND _to.curexg_from = " . (int)$toCur . ";");
		if ($r) {
			if ($row = $r->fetch_assoc()) {
				$output = $row['_rate'];
			}
		}
		return $output;
	}

	public function get_transaction_type($type)
	{
		if (isset($this->_transaction_types[$type])) {
			return $this->_transaction_types[$type];
		} else {
			return null;
		}
	}
}
