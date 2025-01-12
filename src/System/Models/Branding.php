<?php
declare(strict_types=1);

namespace System\Models;

use System\App;


/**
 * Branding and documents naming system.
 */
class Branding
{
	private array $map;

	public function __construct(protected App &$app)
	{

		$this->buildMap();
	}

	private function buildMap()
	{
		$this->map = [];

		$query = $this->app->db->execute_query(
			"SELECT prx_sector, prx_enumid, prx_value, prx_placeholder FROM system_prefix WHERE prx_company = ?",
			[$this->app->user->company->id]
		);

		if ($query) {
			while ($row = $query->fetch_assoc()) {
				if (!isset($this->map[$row['prx_sector']])) {
					$this->map[$row['prx_sector']] = [];
				}
				$this->map[$row['prx_sector']][$row['prx_enumid']] = [$row['prx_value'], (int) $row['prx_placeholder']];
			}
		}
	}

	public function formatId(object $type, float|int|string $value, float|int|string|null $prepend = null): string
	{
		if (gettype($type) == "object") {
			if (array_key_exists($type::class, $this->map) && array_key_exists($type->value, $this->map[$type::class])) {
				return $this->map[$type::class][$type->value][0] . ($prepend !== null ? (string) $prepend : "") . str_pad((string) $value, $this->map[$type::class][$type->value][1], "0", STR_PAD_LEFT);
			}
		}
		return (string)$value;
	}
}