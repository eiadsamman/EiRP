<?php

declare(strict_types=1);

namespace System\Finance\Transaction;

use System\Finance\Forex;


class Transaction
{
	private TransactionDetails $transactionDetails;
	
	private int $nature;
	private int $main_statement_id;
	public function __construct(protected \System\App &$app, protected \System\Finance\Transaction\Prepare &$prepare)
	{
		if ($prepare instanceof Receipt) {
			$this->nature = \System\Finance\Transaction\Nature::Income->value;
		} elseif ($prepare instanceof Receipt) {

		}
	}

	public function execute(): bool
	{

		$this->app->db->autocommit(false);
		$this->transactionDetails = $this->prepare->transactionDetails();
		if ($this->postTransaction()) {
		}
		return false;
	}


	

	private function postTransaction(): bool|int
	{
		$stmt = $this->app->db->prepare("INSERT INTO acc_main (
			acm_usr_id,
			acm_editor_id,
			acm_ctime,
			acm_time,
			acm_type,
			acm_beneficial,

			acm_category,
			acm_comments,
			acm_reference,
			acm_realvalue,
			acm_realcurrency,
			
			acm_forex_rate,
			acm_rel,
			acm_party
			) VALUES (?,?,?,?,?,? ,?,?,?,?,?, ?,?,?);");


		$dateTime = $this->transactionDetails->dateTime->format("Y-m-d");
		$timeStamp = (new \DateTime("now"))->format("Y-m-d H:i:s");
		$forex_exchange = $this->prepare->forex->exchange(
			$this->transactionDetails->issuer_account->currency->id,
			$this->transactionDetails->target_account->currency->id,
			1
		);
		$stmt->bind_param(
			"iissisissdidii",

			$this->transactionDetails->individual,
			$this->app->user->info->id,
			$dateTime,
			$timeStamp,
			$this->nature,
			$this->transactionDetails->beneficiary,

			$this->transactionDetails->category,
			$this->transactionDetails->description,
			$this->transactionDetails->reference,
			$this->transactionDetails->value,
			$this->transactionDetails->issuer_account->currency->id,

			$forex_exchange,
			$this->transactionDetails->relation,
			$this->app->user->company->id
		);

		if ($stmt->execute()) {
			$this->main_statement_id = $stmt->insert_id;
			return true;
		} else {
			return false;
		}


	}

}