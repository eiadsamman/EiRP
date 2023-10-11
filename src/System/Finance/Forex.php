<?php

declare(strict_types=1);

namespace System\Finance;

use System\Exceptions\Finance\ForexException;

class Forex
{
	private array $rates;

	public function __construct(protected \System\App &$app)
	{

		$result = $this->app->db->query(
			"SELECT 
				curexg_from, curexg_value
			FROM 
				currency_exchange
			WHERE
				curexg_to = {$this->app->currency->id}
			;"
		);
		if ($result) {
			while ($row = $result->fetch_row()) {
				$this->rates[(int) $row[0]] = (float) $row[1];
			}
		}
		unset($result);
		unset($app);
	}


	public function exchange(int $from, int $to, float $value): float
	{
		if (isset($this->rates[$from], $this->rates[$to])) {
			if ($this->rates[$to] == 0) {
				return 0;
			} else {
				return $this->rates[$from] / $this->rates[$to] * $value;
			}
		} else {
			throw new ForexException();
		}
	}

	public function table(): array
	{
		return $this->rates;
	}
}