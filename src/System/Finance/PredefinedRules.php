<?php

declare(strict_types=1);

namespace System\Finance;


class DefinedRule
{
	public function __construct(public int $id, public string $name, public int $inbound_account, public int $outbound_account, public int $category)
	{
	}
}
class PredefinedRules
{
	public function __construct(protected \System\App &$app)
	{

	}


	private function getRules(int $type): array
	{
		if(is_null($this->app->user->account)){
			return [];
		}

			
		$output = array();
		$stmt = $this->app->db->prepare(
			"SELECT 
				accdef_id,
				accdef_name,
				accdef_in_acc_id,
				accdef_out_acc_id,
				accdef_category
			FROM 
				acc_predefines
					LEFT JOIN user_partition AS AliasInbound ON (AliasInbound.upr_prt_id = accdef_in_acc_id AND AliasInbound.upr_usr_id = {$this->app->user->info->id} AND AliasInbound.upr_prt_inbound = 1) 
					LEFT JOIN user_partition AS AliasOutbound ON (AliasOutbound.upr_prt_id = accdef_out_acc_id AND AliasOutbound.upr_usr_id = {$this->app->user->info->id} AND AliasOutbound.upr_prt_outbound = 1) 
					
			WHERE
				accdef_operation = {$type} AND
				(accdef_company = {$this->app->user->company->id} OR accdef_company IS NULL OR accdef_company = 0)  
				AND
				(
					
					(AliasInbound.upr_prt_inbound = 1 AND accdef_out_acc_id IS NULL ) OR
					(accdef_in_acc_id IS NULL AND AliasOutbound.upr_prt_outbound = 1  ) OR
					(AliasInbound.upr_prt_inbound = 1 AND AliasOutbound.upr_prt_outbound = 1  ) 
				)
			"
		);
		/* (accdef_in_acc_id IS NULL AND accdef_out_acc_id IS NULL ) OR */
		$stmt->execute();

		$result = $stmt->get_result();
		while ($row = $result->fetch_assoc()) {
			array_push(
				$output,
				new DefinedRule(
					(int) $row['accdef_id'],
					$row['accdef_name'],
					is_null($row['accdef_in_acc_id']) ? $this->app->user->account->id : (int) $row['accdef_in_acc_id'],
					is_null($row['accdef_out_acc_id']) ? $this->app->user->account->id : (int) $row['accdef_out_acc_id'],
					is_null($row['accdef_category']) ? 0 : (int) $row['accdef_category']
				)
			);
		}
		return $output;
	}
	public function incomeRules(): array
	{
		return $this->getRules(1);
	}
	public function paymentRules(): array
	{
		return $this->getRules(2);
	}
}