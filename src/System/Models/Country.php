<?php
declare(strict_types=1);

namespace System\Models;


/**
 * Represents a company entity.
 */
class Country
{
	public $id;
	public $name;
	public $code;
	public $callingCodes;


	public function __tostring(): string
	{
		return $this->name;
	}
}