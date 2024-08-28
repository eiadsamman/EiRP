<?php
declare(strict_types=1);

namespace System\Profiles;
use System\Models\Country;

class CompanyLegal
{
	protected ?int $internalId;
	public ?int $id;
	public string $name;
	public ?string $registrationNumber;
	public ?string $taxNumber;
	public ?string $vatNumber;
	public ?\DateTime $creationDate;
	public bool $default = false;
}

