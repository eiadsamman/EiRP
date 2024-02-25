<?php

declare(strict_types=1);

namespace System\Finance\StatementOfAccount;

class StatementOfAccount
{
	public Criteria $criteria;

	public function __construct(protected \System\App &$app)
	{
		$this->criteria = new Criteria();
	}

	public function chunk(?bool $sort_ascending = true): \mysqli_result|bool
	{
		/**
		 * @running_total := @running_total + _master.atm_value AS cumulative_sum
		 * JOIN (SELECT @running_total := 0) r
		 * 
		 * Using Windowed query `OVER (ORDER BY ....)` worked like a charm
		 * 
		 *  UNIX_TIMESTAMP(acm_ctime) AS acm_ctime ???? timestamp issue
		 */

		$sort_direction = ($sort_ascending ? "ASC" : "DESC");
		$s = microtime(true);
		$query = "SELECT * 
		FROM ( 
			SELECT 
			 _master.atm_value, 
			 statements_view.acm_id, acm_ctime, statements_view.acm_beneficial, statements_view.acm_comments,
			 statements_view.accgrp_name,statements_view.acccat_name,
			_slave.comp_id, _slave.comp_name, _slave.prt_id, _slave.prt_name, _slave.cur_id, _slave.cur_shortname
			,SUM(_master.atm_value) OVER(ORDER BY statements_view.acm_ctime {$sort_direction}, statements_view.acm_id {$sort_direction}) AS cumulative_sum
			,up_count
			FROM 
				acc_temp _master
				INNER JOIN 
					(SELECT 
						atm_id, atm_main, comp_name, comp_id, prt_name,prt_id, cur_id,cur_shortname
					FROM 
						acc_temp 
							JOIN view_financial_accounts ON view_financial_accounts.prt_id = atm_account_id
					) _slave ON _slave.atm_id != _master.atm_id AND _slave.atm_main=_master.atm_main
				JOIN
					(SELECT 
						acc_main.acm_id, acc_main.acm_beneficial, acc_main.acm_comments, acc_main.acm_ctime	,acc_main.acm_rejected,
						acccat_name,accgrp_name,sub_uploads.up_count
					FROM 
						acc_main
						JOIN (
							SELECT acccat_id,acccat_name,accgrp_name
							FROM acc_categorygroups JOIN acc_categories ON accgrp_id = acccat_group
							) category_view ON category_view.acccat_id = acc_main.acm_category
						LEFT JOIN
							(
								SELECT up_rel, COUNT(1) AS up_count FROM uploads WHERE up_pagefile=" . \System\Attachment\Type::FinanceRecord->value . " AND up_deleted = 0 GROUP BY up_rel
							) AS sub_uploads ON  up_rel = acm_id 
					) statements_view
					 ON _master.atm_main = statements_view.acm_id
			WHERE
				1 
				AND _master.atm_account_id = {$this->app->user->account->id}
				AND statements_view.acm_rejected = 0
				AND ({$this->criteria->where()})
			ORDER BY
				statements_view.acm_ctime {$sort_direction}, statements_view.acm_id {$sort_direction}
			) AS _pagination
		LIMIT {$this->criteria->limit()}
		;";
		//$this->app->errorHandler->customError((string) (microtime(true) - $s));

		$stmt = $this->app->db->prepare($query);
		$stmt->execute();
		return $stmt->get_result();
	}

	public function complete(): \Generator
	{
		$stmt = $this->app->db->prepare(
			"SELECT 
				_master.atm_value, 
				acc_main.acm_id, acm_ctime, acc_main.acm_beneficial, acc_main.acm_comments,
				_slave.comp_id, _slave.comp_name, _slave.prt_name
				,SUM(_master.atm_value) OVER(ORDER BY acc_main.acm_ctime ASC, acc_main.acm_id ASC) AS cumulative_sum
			FROM 
				acc_temp  _master
				INNER JOIN 
					(SELECT 
						atm_id, atm_main, comp_name, comp_id, prt_name,prt_id, cur_id,cur_shortname
					FROM 
						acc_temp 
							JOIN view_financial_accounts ON view_financial_accounts.prt_id = atm_account_id
					)  _slave ON _slave.atm_id != _master.atm_id AND _slave.atm_main=_master.atm_main
				JOIN
					acc_main ON _master.atm_main = acm_id
			WHERE
				1 
				AND _master.atm_account_id = {$this->app->user->account->id}
				AND acc_main.acm_rejected = 0
				AND ({$this->criteria->where()})
			ORDER BY
				acc_main.acm_ctime ASC, acc_main.acm_id ASC
			;"
		);

		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				yield $row;
			}
		}
	}


	public function summary(int &$count, float &$sum): void
	{
		$stmt = $this->app->db->prepare(
			"SELECT 
				COUNT(atm_value) AS fn_count, SUM(atm_value) AS fn_sum
			FROM 
				acc_temp 
					JOIN acc_main ON atm_main = acm_id
			WHERE
				1 
				AND atm_account_id = {$this->app->user->account->id}
				AND acc_main.acm_rejected = 0
				AND ({$this->criteria->where()})
			ORDER BY
				acc_main.acm_ctime ASC, acc_main.acm_id ASC
			"
		);
		if ($stmt->execute()) {
			$result = $stmt->get_result();
			if ($row = $result->fetch_assoc()) {
				$count = $row['fn_count'];
				$sum = $row['fn_sum'];
			}
		}
	}
}