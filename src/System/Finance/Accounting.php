<?php

declare(strict_types=1);

namespace System\Finance;

use Exception;


class Accounting
{
	private $_opdef_info_array = false;
	private $_curdef_info_array = false;

	protected \System\App $app;

	public function __construct(&$app)
	{
		$this->app = $app;
	}
	
	public function get_currency_list()
	{

		$temp = array();
		if ($r = $this->app->db->query("SELECT cur_id,cur_name,cur_symbol,cur_default,cur_shortname FROM currencies;")) {
			while ($row = $r->fetch_assoc()) {
				$temp[$row['cur_id']] = array();
				$temp[$row['cur_id']]['name'] = $row['cur_name'];
				$temp[$row['cur_id']]['symbol'] = $row['cur_symbol'];
				$temp[$row['cur_id']]['shortname'] = $row['cur_shortname'];
				$temp[$row['cur_id']]['default'] = $row['cur_default'];
			}
		}
		return $temp;
	}
	
	public function system_default_currency()
	{
		$temp = false;
		if ($r = $this->app->db->query("SELECT cur_id,cur_name,cur_symbol,cur_shortname FROM currencies WHERE cur_default=1;")) {
			if ($row = $r->fetch_assoc()) {
				$temp = array(
					"id" => $row['cur_id'],
					"name" => $row['cur_name'],
					"symbol" => $row['cur_symbol'],
					"shortname" => $row['cur_shortname'],
				);
			}
		}
		return $temp;
	}

	public function currency_exchange(int $fromCur, int $toCur): float|bool
	{
		if ($fromCur == $toCur) {
			return 1;
		}
		$output = false;
		$r = $this->app->db->query("
			SELECT (_from.curexg_value / _to.curexg_value) AS _rate 
			FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to ON _from.curexg_from = " . (int)$fromCur . " AND _to.curexg_from = " . (int)$toCur . ";");
		if ($r) {
			if ($row = $r->fetch_assoc()) {
				$output = (float)$row['_rate'];
			}
		}
		return $output;
	}


}
