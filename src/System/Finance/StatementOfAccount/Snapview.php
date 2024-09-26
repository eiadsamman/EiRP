<?php

declare(strict_types=1);

namespace System\Finance\StatementOfAccount;

class Snapview
{
	public Criteria $criteria;

	public function __construct(protected \System\App &$app)
	{
		$this->criteria = new Criteria();
	}

	public function chunk(): \mysqli_result|bool
	{
		if (is_null($this->app->user->account)) {
			return false;
		}
		$query = "SELECT * 
		FROM ( 
			SELECT 
			 _master.atm_value, 
			 statements_view.acm_id, acm_ctime, statements_view.acm_beneficial, statements_view.acm_comments, issuer_badge,
			 statements_view.accgrp_name,statements_view.acccat_name,acm_editor_id,usr_firstname, usr_lastname
			
			FROM 
				acc_temp AS _master
				LEFT JOIN 
					(SELECT 
						atm_id, atm_main
					FROM 
						acc_temp 
							JOIN view_financial_accounts ON view_financial_accounts.prt_id = atm_account_id
					) _slave ON _slave.atm_id != _master.atm_id AND _slave.atm_main = _master.atm_main
				JOIN
					(SELECT 
						acc_main.acm_id, acc_main.acm_beneficial, acc_main.acm_comments, acc_main.acm_ctime	,acc_main.acm_rejected,
						acccat_name, accgrp_name, acc_main.acm_editor_id, editor_image.issuer_badge,
						editor_profile.usr_firstname, editor_profile.usr_lastname, acc_main.acm_category
					FROM 
						acc_main
						JOIN (
							SELECT acccat_id,acccat_name,accgrp_name
							FROM acc_categorygroups JOIN acc_categories ON accgrp_id = acccat_group
							) category_view ON category_view.acccat_id = acc_main.acm_category
			
						LEFT JOIN
							(
								SELECT up_id AS issuer_badge, up_rel FROM uploads WHERE up_deleted = 0 AND up_pagefile = " . \System\Attachment\Type::HrPerson->value . " AND 1 GROUP BY up_rel 
							) AS editor_image ON editor_image.up_rel = acm_editor_id
						LEFT JOIN 
							(
								SELECT usr_id, usr_firstname, usr_lastname FROM users
							) AS editor_profile ON editor_profile.usr_id = acc_main.acm_editor_id

					) statements_view
					 ON _master.atm_main = statements_view.acm_id
			WHERE
				_master.atm_account_id = {$this->app->user->account->id}
				AND statements_view.acm_rejected = 0
				AND ({$this->criteria->where()})
			ORDER BY
				statements_view.acm_ctime DESC, statements_view.acm_id DESC
			) AS _pagination
		ORDER BY
				_pagination.acm_ctime DESC, _pagination.acm_id DESC
		LIMIT {$this->criteria->limit()}
		;";
		$this->app->errorHandler->customError($query);
		$stmt = $this->app->db->prepare($query);
		$stmt->execute();
		return $stmt->get_result();
	}


}