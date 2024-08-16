<?php

declare(strict_types=1);

namespace System\Finance\Transaction;

use System\Exceptions\Finance\AccountNotFoundException;
use System\Finance\Account;
use System\Finance\AccountRole;
use System\Finance\Currency;
use System\Profiles\CompanyProfile;
use System\Profiles\IndividualProfile;


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
					acm_party,
					comp_id, comp_name,
					_category.accgrp_name,
					_category.acccat_name,

					_usr.usr_id AS ben_usr_id,
					_usr.usr_firstname AS ben_usr_firstname,
					_usr.usr_lastname AS ben_usr_lastname,

					_editor.usr_firstname AS edt_usr_firstname,
					_editor.usr_lastname AS edt_usr_lastname,

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
						LEFT JOIN companies ON comp_id = acm_party
						
				WHERE 
					acm_id = $id;"
			)
		) {
			if ($row = $r->fetch_assoc()) {

				$result              = new StatementProperty();
				$result->id          = (int) $row['acm_id'];
				$result->canceled    = (int) $row['acm_rejected'] == 1;
				$result->type        = Nature::tryFrom((int) $row['acm_type']);
				$result->dateTime    = new \DateTime($row['acm_ctime']);
				$result->reference   = $row['acm_reference'];
				$result->description = $row['acm_comments'];
				$result->beneficiary = $row['acm_beneficial'];

				if (!is_null($row['comp_id']) && $row['comp_id'] != 0) {
					$result->party       = new CompanyProfile();
					$result->party->id   = (int)$row['comp_id'];
					$result->party->name = $row['comp_name'];

				}

				$result->value = (float) $row['acm_realvalue'];
				if (empty($row['acm_usr_id'])) {
					$result->individual = null;
				} else {
					$result->individual            = new IndividualProfile();
					$result->individual->id        = (int) $row['acm_usr_id'];
					$result->individual->firstname = $row['ben_usr_firstname'];
					$result->individual->lastname  = $row['ben_usr_lastname'];
				}

				$result->editor            = new IndividualProfile();
				$result->editor->id        = (int) $row['acm_editor_id'];
				$result->editor->firstname = $row['edt_usr_firstname'];
				$result->editor->lastname  = $row['edt_usr_lastname'];

				$result->category = new StatementCategoryProperty((int) $row['acm_category'], $row['accgrp_name'], $row['acccat_name']);
				$result->currency = new Currency((int) $row['cur_id'], $row['cur_name'], $row['cur_symbol'], $row['cur_shortname']);

				$this->pairs($result);

				if (!is_null($result->creditAmount) && !is_null($result->debitAmount) && $result->creditAmount > 0 && $result->debitAmount > 0)
					$result->forexRate = $result->creditAmount > $result->debitAmount ? $result->creditAmount / $result->debitAmount : $result->debitAmount / $result->creditAmount;

				$this->getAttachements($result);
				return $result;
			}
		}
		return false;
	}


	private function pairs(StatementProperty &$statementProperty)
	{
		$statementProperty->creditor     = false;
		$statementProperty->debitor      = false;
		$statementProperty->creditAmount = 0;
		$statementProperty->debitAmount  = 0;
		$view_role                       = new AccountRole();
		$view_role->view                 = true;
		if (
			$r = $this->app->db->query(
				"SELECT 
					atm_account_id, atm_value, atm_dir
				FROM
					acc_temp 
						JOIN view_financial_accounts ON prt_id = atm_account_id
				WHERE
					atm_main = {$statementProperty->id}"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				try {
					if ((int) $row['atm_dir'] == 0) {
						$statementProperty->creditAmount = (float) $row['atm_value'];
						$statementProperty->creditor     = $this->app->user->findAssosiateAccount((int) $row['atm_account_id']);
					} else {
						$statementProperty->debitAmount = (float) $row['atm_value'];
						$statementProperty->debitor     = $this->app->user->findAssosiateAccount((int) $row['atm_account_id']);
					}
				} catch (AccountNotFoundException $e) {
				}
			}
		}
	}
	private function getAttachements(StatementProperty &$statementProperty)
	{
		$statementProperty->attachments = array();
		if (
			$r = $this->app->db->query(
				"SELECT 
					up_id, up_name, up_mime, up_size
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
				$file                             = new \System\Attachment\Properties();
				$file->id                         = (int) $row['up_id'];
				$file->name                       = $row['up_name'] ?? "";
				$file->mime                       = $row['up_mime'] ?? "";
				$file->size                       = (int) $row['up_size'];
				$statementProperty->attachments[] = $file;
			}
		}
	}
}