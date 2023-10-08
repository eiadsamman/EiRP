<?php

declare(strict_types=1);

namespace System\Finance\Transaction;

use System\Exceptions\Finance\TransactionPrepareException;
use System\Finance\Account;



class Receipt extends Prepare
{

	public function releaseStatementPairs(int $ownerID): bool
	{
		$stmt = $this->app->db->prepare(
			"INSERT INTO 
				acc_temp (atm_account_id, atm_value, atm_dir, atm_main) 
			VALUES 
				(?,?,?,?); 
			"
		);
		$account = $value = $dir = null;
		$stmt->bind_param(
			"idii",
			$account,
			$value,
			$dir,
			$ownerID
		);



		$account = $this->details->issuer_account->id;
		$dir = 1;
		$value = 1 * $this->details->value;
		if (!$stmt->execute()) {
			return false;
		}

		$account = $this->details->target_account->id;
		$dir = 0;
		$value = -1 *

			(
				$this->details->issuer_account->currency->id == $this->details->target_account->currency->id ?
				$this->details->value :
				$this->forex->exchange(
					$this->details->issuer_account->currency->id,
					$this->details->target_account->currency->id,
					$this->details->value
				)
			);
		if (!$stmt->execute()) {
			return false;
		}
		return true;
	}

	public function __construct(protected \System\App &$app)
	{
		parent::__construct($app, Nature::Income);
		$this->sub_pair = [true, false];
	}

	public final function issuerAccount(Account $account): self
	{
		if (!$account->role->inbound) {
			throw new TransactionPrepareException("Account isn't set for inbound operations", 201);
		}
		$this->details->issuer_account = $account;
		$this->accountConflict();
		return $this;
	}
	public final function targetAccount(Account $account): self
	{
		if (!$account->role->outbound) {
			throw new TransactionPrepareException("Account isn't set for outbound operations", 202);
		}
		$this->details->target_account = $account;
		$this->accountConflict();
		return $this;
	}
}