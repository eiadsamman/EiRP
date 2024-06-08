<?php
declare(strict_types=1);

namespace System\Finance;

use System\Exceptions\Finance\ForexException;

class BuySellValues
{
	public function __construct(public float $buy, public float $sell)
	{
	}
}

class Forex
{
	private array $rates;

	public function __construct(protected \System\App &$app, protected ?Currency $currency = null)
	{
		$result = $this->app->db->query(
			"SELECT 
				curexg_from, curexg_value, curexg_sell
			FROM 
				currency_exchange
			WHERE
				curexg_to = " . ($currency != null ? $currency->id : $this->app->currency->id) . ";"
		);
		if ($result) {
			while ($row = $result->fetch_row()) {
				$this->rates[(int) $row[0]] = new BuySellValues((float) $row[1], (float) $row[2]);
			}
		}
		unset($result);
		unset($app);
	}

	public function exchange(int $from, int $to, float $value): float
	{
		if ($to == $from) {
			return $value;
		}
		if (isset($this->rates[$from], $this->rates[$to])) {
			if ($this->rates[$to]->buy == 0) {
				return 0;
			} else {
				return $this->rates[$from]->buy / $this->rates[$to]->buy * $value;
			}
		} else {
			throw new ForexException();
		}
	}

	public function exchangeBuyCurrency(int $from, int $to, float $value): float
	{
		if ($to == $from) {
			return $value;
		}
		if (isset($this->rates[$from], $this->rates[$to])) {
			if ($this->rates[$to]->buy == 0) {
				return 0;
			} else {
				return $this->rates[$from]->buy / $this->rates[$to]->buy * $value;
			}
		} else {
			throw new ForexException();
		}
	}
	public function exchangeSellCurrency(int $from, int $to, float $value): float
	{
		if ($to == $from) {
			return $value;
		}
		if (isset($this->rates[$from], $this->rates[$to])) {
			if ($this->rates[$to]->sell == 0) {
				return 0;
			} else {
				return $this->rates[$from]->sell / $this->rates[$to]->sell * $value;
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