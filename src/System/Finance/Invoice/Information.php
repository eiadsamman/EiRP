<?php
declare(strict_types=1);

namespace System\Finance\Invoice;
use System\Finance\Currency;
use System\Finance\Invoice\enums\PaymentTerm;
use System\Finance\Invoice\enums\Purchase;
use System\Finance\Invoice\enums\ShippingTerm;
use System\Profiles\AccountProfile;
use System\Profiles\CompanyProfile;
use System\Profiles\CostCenterProfile;
use System\Profiles\IndividualProfile;

class Information
{
	public int $id;
	public int $companyId;
	public CostCenterProfile $costCenter;
	public ?Currency $currency;
	public Sale|Purchase $type;
	public IndividualProfile $issuedBy;
	public ?AccountProfile $departement;

	public int $serialNumber;
	public ?string $title;
	public \DateTime $issuingDate;
	public ?\DateTime $dueDate;
	public ?\DateTime $closeDate;
	public CompanyProfile $client;
	public CompanyProfile $clientShipTo;
	public CompanyProfile $clientBillTo;
	public IndividualProfile $clientAttention;
	public ?string $comments;
	public int $relatedDocumentId;
	public float $totalValue;
	public float $vatRate;
	public float $taxRate;
	public float $addtionalAmmout;
	public float $discountRate;
	public ?PaymentTerm $paymentTerm;
	public ?ShippingTerm $shippingTerm;
	public bool $voided;
	public bool $approved;


}
