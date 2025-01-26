<?php
declare(strict_types=1);

namespace System\Controller\Finance\Invoice\structs;
use System\Controller\Finance\Currency;
use System\Controller\Finance\Invoice\enums\PaymentTerm;
use System\Controller\Finance\Invoice\enums\Purchase;
use System\Controller\Finance\Invoice\enums\ShippingTerm;
use System\Models\Country;
use System\Profiles\AccountProfile;
use System\Profiles\CompanyProfile;
use System\Profiles\CostCenterProfile;
use System\Profiles\IndividualProfile;

class InvoiceDetails
{
	public int $id;
	public int $companyId;
	public CostCenterProfile $costCenter;
	public ?Currency $currency;
	public Sale|Purchase $type;
	public IndividualProfile $issuedBy;
	public ?AccountProfile $departement;

	public ?int $parentId;
	public ?int $parentSerialNumber;

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
	public float $totalValue = 0;
	public ?float $vatRate = null;
	public ?float $taxRate;
	public float $addtionalAmmout = 0;
	public float $discountRate = 0;
	public ?PaymentTerm $paymentTerm = null;
	public ?ShippingTerm $shippingTerm = null;
	public bool $voided;
	public bool $approved;


	protected function sqlSelectQuery(): string
	{
		return "
			a1.po_id,a1.po_comp_id,a1.po_costcenter,a1.po_cur_id,a1.po_type,a1.po_issuedby_id,
			a1.po_departement_id,a1.po_serial,a1.po_title,a1.po_date,a1.po_due_date,
			a1.po_close_date,a1.po_client_id,a1.po_shipto_id,a1.po_billto_id,a1.po_attention_id,
			a1.po_remarks,a1.po_rel,a1.po_total,a1.po_vat_rate,a1.po_tax_rate,
			a1.po_additional_amount,a1.po_discount,a1.po_payment_term,a1.po_shipping_term,a1.po_voided,
			a1.po_rel,

			a2.po_id AS parent_po_id,
			a2.po_serial AS parent_po_serial,
			
			/* OUTER JOINS */
			cur_id, cur_name, cur_shortname, cur_symbol,

			issuer.usr_id AS _issuer_id,
			issuer.usr_firstname AS _issuer_firstname,
			issuer.usr_lastname AS _issuer_lastname,

			client_company.comp_id AS _client_company_id,
			client_company.comp_name AS _client_company_name,
			client_company.comp_address AS _client_company_address,
			client_company.comp_country AS _client_company_country,
			client_company.comp_city AS _client_company_city,
			client_company.comp_tellist AS _client_company_tellist,
			client_company.cntry_name AS _client_company_country_name,
			client_company.cntry_code AS _client_company_country_code,
			
			usrccc_usr_id,
			ccc_name,ccc_id, ccc_vat,
			prt_id, prt_name
			";
	}

	public function __construct(?array $sqlFetchAssoc = null)
	{
		if (empty($sqlFetchAssoc))
			return;


		if (gettype($sqlFetchAssoc) == "array") {

			$this->id                 = (int) $sqlFetchAssoc['po_id'];
			$this->serialNumber       = (int) $sqlFetchAssoc['po_serial'];
			$this->costCenter         = new CostCenterProfile((int) $sqlFetchAssoc['po_costcenter'], $sqlFetchAssoc['ccc_name'], (float) $sqlFetchAssoc['ccc_vat']);
			$this->parentId           = empty($sqlFetchAssoc['parent_po_id']) ? null : (int) $sqlFetchAssoc['parent_po_id'];
			$this->parentSerialNumber = empty($sqlFetchAssoc['parent_po_serial']) ? null : (int) $sqlFetchAssoc['parent_po_serial'];

			$this->currency          = is_null($sqlFetchAssoc['po_cur_id']) ? null : new Currency((int) $sqlFetchAssoc['cur_id'], $sqlFetchAssoc['cur_name'], $sqlFetchAssoc['cur_symbol'], $sqlFetchAssoc['cur_shortname']);
			$this->relatedDocumentId = (int) $sqlFetchAssoc['po_rel'];
			$this->totalValue        = (float) $sqlFetchAssoc['po_total'];
			$this->vatRate           = (float) $sqlFetchAssoc['po_vat_rate'];
			$this->taxRate           = (float) $sqlFetchAssoc['po_tax_rate'];
			$this->addtionalAmmout   = (float) $sqlFetchAssoc['po_additional_amount'];
			$this->discountRate      = (float) $sqlFetchAssoc['po_discount'];
			$this->shippingTerm      = is_null($sqlFetchAssoc['po_shipping_term']) ? null : ShippingTerm::tryFrom((int) $sqlFetchAssoc['po_shipping_term']);
			$this->paymentTerm       = is_null($sqlFetchAssoc['po_payment_term']) ? null : PaymentTerm::tryFrom((int) $sqlFetchAssoc['po_payment_term']);

			$this->voided              = (int) $sqlFetchAssoc['po_voided'] == 1;
			$this->issuedBy            = new IndividualProfile();
			$this->issuedBy->id        = (int) $sqlFetchAssoc['_issuer_id'];
			$this->issuedBy->firstname = $sqlFetchAssoc['_issuer_firstname'];
			$this->issuedBy->lastname  = $sqlFetchAssoc['_issuer_lastname'];


			if (!is_null($sqlFetchAssoc['prt_id'])) {
				$this->departement       = new AccountProfile();
				$this->departement->id   = (int) $sqlFetchAssoc['prt_id'];
				$this->departement->name = $sqlFetchAssoc['prt_name'];
			} else {
				$this->departement = null;
			}


			if (!is_null($sqlFetchAssoc['_client_company_id'])) {
				$this->client          = new CompanyProfile();
				$this->client->id      = (int) $sqlFetchAssoc['_client_company_id'];
				$this->client->name    = $sqlFetchAssoc['_client_company_name'];
				$this->client->address = $sqlFetchAssoc['_client_company_address'];
				if (!is_null($sqlFetchAssoc['_client_company_country_name'])) {
					$this->client->country       = new Country($sqlFetchAssoc['_client_company_id']);
					$this->client->country->name = $sqlFetchAssoc['_client_company_country_name'] ?? "";
					$this->client->country->code = (string) $sqlFetchAssoc['_client_company_country_code'];
				}
				$this->client->city           = $sqlFetchAssoc['_client_company_city'];
				$this->client->contactNumbers = $sqlFetchAssoc['_client_company_tellist'];
			}
			$this->title       = $sqlFetchAssoc['po_title'];
			$this->comments    = $sqlFetchAssoc['po_remarks'];
			$this->issuingDate = new \DateTime($sqlFetchAssoc['po_date']);
			$this->dueDate     = is_null($sqlFetchAssoc['po_due_date']) ? null : new \DateTime($sqlFetchAssoc['po_due_date']);
			$this->closeDate   = is_null($sqlFetchAssoc['po_close_date']) ? null : new \DateTime($sqlFetchAssoc['po_close_date']);
		}
	}
}
