<?php
declare(strict_types=1);

namespace System\Models;


/**
 * Represents a company entity.
 */
class Country
{
	public ?int $id;
	public ?string $name;
	public ?string $code = null;
	public ?int $callingCodes = null;


	public function __toString(): string
	{
		return $this->name;
	}

	public function __construct(?int $id = null)
	{
		$this->id = $id;
	}
}