<?php
declare(strict_types=1);

namespace System\Profiles;

class CompanyProfile
{
	protected ?int $internalId;
	public ?int $id;
	public string $name;
	public ?int $logo = null;
	public ?array $photoList = null;
	public ?int $country = null;
	public ?string $state = null;
	public ?string $address = null;
	public ?int $businessField = null;
	public ?string $contactNumbers = null;
	public ?string $contactEmails = null;
	public ?string $commercialRegistrationNumber = null;
	public ?string $taxRegistrationNumber = null;
	public ?string $vatRegistrationNumber = null;
	public ?string $bankName = null;
	public ?string $bankAccountNumber = null;
}

