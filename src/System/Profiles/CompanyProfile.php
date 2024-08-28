<?php
declare(strict_types=1);

namespace System\Profiles;
use System\Models\Country;

class CompanyProfile
{
	protected ?int $internalId;
	public ?int $id;
	public string $name;
	public ?int $logo = null;
	public ?array $photoList = null;
	public ?Country $country = null;
	public ?\DateTime $creationDate = null;
	public ?string $address = null;
	public ?string $city = null;
	public ?int $businessField = null;
	
	
	public ?float $latitude = null;
	public ?float $longitude = null;

	public ?string $contactNumbers = null;
	public ?string $contactEmails = null;
	public ?CompanyLegal $legal = null;
}

