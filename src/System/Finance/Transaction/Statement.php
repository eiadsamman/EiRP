<?php

declare(strict_types=1);

namespace System\Finance\Transaction;


use System\Exceptions\Finance\TransactionException;
use System\Finance\Account;
use System\Finance\Currency;
use System\Individual\PersonData;



class Statement
{
	public function __construct(protected \System\App &$app)
	{
	}


	public function read(int $id): StatementProperty|bool
	{
		if (
			$r = $this->app->db->query(
				"SELECT 
				acm_id,
				acm_usr_id,
				acm_editor_id,
				acm_ctime,
				acm_type,
				acm_beneficial,
				acm_comments,
				acm_reference,
				acm_category,
				_category.accgrp_name,_category.acccat_name,

				CONCAT_WS(' ',COALESCE(_usr.usr_firstname,''),IF(NULLIF(_usr.usr_lastname, '') IS NULL, NULL, _usr.usr_lastname)) AS _usrname,
				CONCAT_WS(' ',COALESCE(_editor.usr_firstname,''),IF(NULLIF(_editor.usr_lastname, '') IS NULL, NULL, _editor.usr_lastname)) AS _editorname,

				acm_rejected,
				acm_realvalue, 
				cur_id, cur_name, cur_symbol,cur_shortname
			FROM 
				acc_main 
					LEFT JOIN 
					(
						SELECT
							acccat_id AS _catid,
							accgrp_name,
							acccat_name
						FROM
							acc_categories JOIN acc_categorygroups  ON acccat_group = accgrp_id
					) AS _category ON _category._catid = acm_category
					LEFT JOIN users AS _usr ON _usr.usr_id = acm_usr_id
					LEFT JOIN users AS _editor ON _editor.usr_id = acm_editor_id
					LEFT JOIN currencies ON cur_id = acm_realcurrency
			WHERE 
				acm_id = $id;"
			)
		) {
			if ($row = $r->fetch_assoc()) {
				$result = new StatementProperty();
				$result->id = (int) $row['acm_id'];
				$result->canceled = (int) $row['acm_rejected'] == 1;
				$result->type = Nature::tryFrom((int) $row['acm_type']);
				$result->dateTime = new \DateTime($row['acm_ctime']);
				$result->reference = $row['acm_reference'];
				$result->description = $row['acm_comments'];
				$result->beneficiary = $row['acm_beneficial'];
				$result->value = (float) $row['acm_realvalue'];

				if (empty($row['acm_usr_id'])) {
					$result->individual = null;
				} else {
					$result->individual = new PersonData();
					$result->individual->id = (int) $row['acm_usr_id'];
					$result->individual->name = $row['_usrname'];
				}

				$result->editor = new PersonData();
				$result->editor->id = (int) $row['acm_editor_id'];
				$result->editor->name = $row['_editorname'];
				$result->category = new StatementCategoryProperty((int) $row['acm_category'], $row['accgrp_name'], $row['acccat_name']);
				$result->currency = new Currency((int) $row['cur_id'], $row['cur_name'], $row['cur_symbol'], $row['cur_shortname']);
				$this->pairs($result);
				$this->getAttachements($result);
				return $result;
			}
		}
		return false;
	}


	private function pairs(StatementProperty &$statementProperty)
	{
		if (
			$r = $this->app->db->query(
				"SELECT 
					atm_account_id, atm_value, atm_dir
				FROM
					acc_temp 
				WHERE
					atm_main = {$statementProperty->id}"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				if ((int) $row['atm_dir'] == 0) {
					/* Creditor */
					$statementProperty->creditor = new Account($this->app, (int) $row['atm_account_id']);
				} else {
					/* Debitor */
					$statementProperty->debitor = new Account($this->app, (int) $row['atm_account_id']);
				}
			}
		}
	}
	private function getAttachements(StatementProperty &$statementProperty)
	{
		$statementProperty->attachments= array();
		if (
			$r = $this->app->db->query(
				"SELECT 
					up_id, up_name
				FROM 
					uploads 
				WHERE 
					up_pagefile = " . \System\Attachment\Type::FinanceRecord->value . " AND 
					up_deleted = 0 AND
					up_active = 1 AND
					up_rel = {$statementProperty->id}"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				$file = new \System\Attachment\Properties();
				$file->id = (int) $row['up_id'];
				$file->name = $row['up_name'] ?? "";
				$statementProperty->attachments[] = $file;
			}
		}
	}
}