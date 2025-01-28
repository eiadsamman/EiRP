<?php
declare(strict_types=1);

namespace System\Controller\Finance;

use System\Core\Exceptions\Finance\AccountNotFoundException;
use System\Profiles\CompanyProfile;
use System\Profiles\AccountProfile;





class Account extends AccountProfile
{
	private ?AccountRole $accessRole;

	public function __toString(): string
	{
		return print_r([
			'id' => $this->id,
			'name' => $this->name,
			'balance' => $this->balance,
			'currency' => $this->currency,
			'role' => $this->role,
		], true);
	}

	public function __construct(protected \System\App &$app, int $accountId, ?AccountRole $role = null)
	{
		if ($role == null) {
			$this->accessRole         = new AccountRole();
			$this->accessRole->access = true;
		} else {
			$this->accessRole = $role;
		}

		$this->load($accountId);
	}

	public function load(int $accountId): bool
	{
		if (
			$mysqli_result = $this->app->db->query(
				"SELECT 
					prt_id,prt_name, cur_symbol, cur_name, cur_id, cur_shortname,prt_term,
					upr_prt_inbound, upr_prt_outbound, upr_prt_fetch, upr_prt_view,
					prt_company_id, comp_name
				FROM 
					acc_accounts
						JOIN currencies ON cur_id = prt_currency
						JOIN user_partition ON upr_prt_id = prt_id AND upr_usr_id = {$this->app->user->info->id} AND {$this->accessRole->sqlClause()}
						JOIN companies ON comp_id = prt_company_id
				WHERE 
					prt_id = $accountId;"
			)
		) {

			if ($mysqli_result->num_rows > 0 && $row = $mysqli_result->fetch_assoc()) {
				$this->internalId = (int) $row['prt_id'];
				$this->id         = (int) $row['prt_id'];
				$this->currency   = new \System\Controller\Finance\Currency();
				$this->role       = new \System\Controller\Finance\AccountRole();
				$this->company    = new CompanyProfile();

				$this->name                = $row['prt_name'];
				$this->company->id         = (int) $row['prt_company_id'];
				$this->company->name       = $row['comp_name'];
				$this->currency->id        = (int) $row['cur_id'];
				$this->currency->name      = $row['cur_name'] ?? "";
				$this->currency->symbol    = $row['cur_symbol'] ?? "";
				$this->currency->shortname = $row['cur_shortname'] ?? "";
				$this->role->inbound       = isset($row['upr_prt_inbound']) && (int) $row['upr_prt_inbound'] == 1 ? true : false;
				$this->role->outbound      = isset($row['upr_prt_outbound']) && (int) $row['upr_prt_outbound'] == 1 ? true : false;
				$this->role->access        = isset($row['upr_prt_fetch']) && (int) $row['upr_prt_fetch'] == 1 ? true : false;
				$this->role->view          = isset($row['upr_prt_view']) && (int) $row['upr_prt_view'] == 1 ? true : false;
				$this->balance             = $this->role->view ? $this->getBalance() : null;

				$this->term = Term::from((int) $row['prt_term']);
			} else {
				throw new AccountNotFoundException("Account not found or insufficient privileges");
			}
		} else {
			throw new AccountNotFoundException("Database error");
		}
		return true;
	}
	public function getBalance(): float|bool
	{
		if (!isset($this->id)) {
			return false;
		}
		if (
			$mysqli_result = $this->app->db->query(
				"SELECT 
					SUM(atm_value) AS balance
				FROM 
					acc_temp
						JOIN user_partition ON atm_account_id = upr_prt_id AND upr_usr_id = {$this->app->user->info->id} AND upr_prt_view = 1
						JOIN acc_main ON acm_id = atm_main
				WHERE 
					acm_rejected = 0 AND atm_account_id = {$this->id};"
			)
		) {
			if ($fetch_row = $mysqli_result->fetch_row()) {
				return (float) $fetch_row[0];
			}
		}
		return 0;
	}

	public function __debugInfo()
	{
		return [
			'ID' => $this->id,
			'Name' => $this->name,
		];
	}
}