<?php
declare(strict_types=1);

namespace System\Models;

use System\Profiles\CompanyLegal;
use System\Profiles\CompanyProfile;


/**
 * Represents a company entity.
 */
class Company extends CompanyProfile
{
	private int $companySystemFileId = 147;
	public function __construct(private \System\App $app)
	{

	}

	/**
	 * Serializes the Company object into a JSON string.
	 *
	 * @return string JSON representation of the Company object.
	 */
	public function serialize(): array
	{
		return [
			"InternalID" => empty($this->internalId) ? null : $this->internalId,
			"ID" => empty($this->id) ? null : $this->id,
			"Name" => empty($this->name) ? null : $this->name,
			"Logo" => $this->logo,
			"PhotoList" => $this->photoList,
			"Country" => $this->country,
			"Address" => $this->address,
			"BusinessField" => $this->businessField,
			"ContactNumbers" => $this->contactNumbers,
			"ContactEmails" => $this->contactEmails,
		];
	}
	public function prepare(): void
	{
		$stringProperties = ["name", "address", "contactNumbers", "contactEmails"];
		foreach (get_object_vars($this) as $key => $value) {
			if (in_array($key, $stringProperties))
				$this->{$key} = empty($value) ? null : trim($value);
		}
	}
	public function load(int $companyId): bool
	{
		$stmt = $this->app->db->prepare(
			"SELECT 
				comp_id,
				comp_name, 
				comp_tellist, 
				comp_date,
				comp_emaillist, 
				comp_address, 
				comp_country, 
				comp_city, 
				comp_field, 
				comp_sys_default,
				comp_latitude,
				comp_longitude,
				cntry_name,
				cntry_abrv,
				cntry_id,
				cntry_code,
				financial_balance
			FROM 
				companies
					LEFT JOIN countries ON cntry_id = comp_country

					LEFT JOIN (
						SELECT acm_party, SUM( acm_realvalue * IF( acm_type = 1 , 1 , -1) * curexg_value ) AS financial_balance
						FROM acc_main JOIN currency_exchange ON acm_realcurrency = curexg_from 
						GROUP BY acm_party
					) AS _cash
					ON _cash.acm_party = comp_id
			WHERE 
				comp_id = ?
			"
		);
		$stmt->bind_param(
			"i",
			$companyId
		);

		if ($stmt->execute()) {
			$result = $stmt->get_result();
			if ($result && $result->num_rows > 0 && $row = $result->fetch_assoc()) {
				$this->id             = (int) $row['comp_id'];
				$this->internalId     = (int) $row['comp_id'];
				$this->name           = $row['comp_name'];
				$this->contactNumbers = $row['comp_tellist'] ?? null;
				$this->address        = $row['comp_address'] ?? "-";
				$this->city           = $row['comp_city'] ?? "-";

				$this->financialBalance = $row['financial_balance'];
				$this->longitude        = is_null($row['comp_longitude']) ? null : (float) $row['comp_longitude'];
				$this->latitude         = is_null($row['comp_latitude']) ? null : (float) $row['comp_latitude'];

				$this->creationDate = is_null($row['comp_date']) ? null : new \DateTime($row['comp_date']);

				if (!is_null($row['cntry_id'])) {
					$this->country               = new Country();
					$this->country->id           = $row['cntry_id'];
					$this->country->name         = $row['cntry_name'];
					$this->country->code         = $row['cntry_abrv'];
					$this->country->callingCodes = $row['cntry_code'];
				}

				$exequery = $this->app->db->execute_query(
					"SELECT 
						commercial_id ,commercial_legalName,commercial_registrationNumber,commercial_creationDate,commercial_taxNumber,commercial_vatNumber,commercial_default 
					FROM 
						companies_legal 
					WHERE commercial_companyId = ? AND commercial_default = 1;",
					[$this->id]
				);
				if ($exequery) {
					if ($lg = $exequery->fetch_assoc()) {
						$this->legal                     = new CompanyLegal();
						$this->legal->id                 = $lg['commercial_id'];
						$this->legal->name               = $lg['commercial_legalName'];
						$this->legal->registrationNumber = $lg['commercial_registrationNumber'] ?? "-";
						$this->legal->taxNumber          = $lg['commercial_taxNumber'] ?? "-";
						$this->legal->vatNumber          = $lg['commercial_taxNumber'] ?? "-";
						$this->legal->creationDate       = new \DateTime($lg['commercial_creationDate']);
						$this->legal->default            = true;
					}
				}
				return true;
			}
		}
		return false;
	}



	private function checkIntegrity(): bool
	{
		if (null == $this->name || "" == trim($this->name)) {
			throw new \System\Core\Exceptions\Company\InvalidData("Invalid company name");
		} else {
			$this->name = trim($this->name);

			$r = $this->app->db->execute_query("SELECT comp_id FROM companies WHERE comp_name = ?", [
				$this->name
			]);
			if ($r->num_rows > 0) {
				throw new \System\Core\Exceptions\Company\InvalidData("Company name already exists", 100);
			}
		}

		return true;
	}
	public function add(): bool|null
	{
		if (!$this->app->file->find($this->companySystemFileId)->permission->add) {
			throw new \System\Core\Exceptions\Exceptions("Permissions denied");
		}
		$this->checkIntegrity();
		$stmt = $this->app->db->execute_query(
			"INSERT INTO companies
				(comp_name, comp_tellist, comp_emaillist, comp_address, comp_country, comp_city, comp_field, comp_sys_default,
				comp_latitude,comp_longitude)
				VALUES
				(?,?,?,?,?,?,?,0,?,?);",
			[
				$this->name,
				$this->contactNumbers,
				$this->contactEmails,
				$this->address,
				$this->country->id,
				$this->city,
				$this->businessField,
				$this->latitude,
				$this->longitude
			]
		);

		if ($stmt) {
			$this->internalId = $this->app->db->insert_id;
			$this->id         = $this->internalId;
			return true;
		} else {
			return false;
		}

	}
	public function update(): bool|null
	{
		if (empty($this->internalId)) {
			throw new \System\Core\Exceptions\Exceptions("No company loaded");
		}
		if (!$this->app->file->find($this->companySystemFileId)->permission->edit) {
			throw new \System\Core\Exceptions\Exceptions("Permissions denied");
		}
		$this->prepare();
		$stmt = $this->app->db->prepare(
			"UPDATE companies
				SET 
					comp_name = ?,
					comp_tellist = ?,
					comp_emaillist = ?,
					comp_address = ?,
					comp_country = ?,
					comp_field = ?,
					comp_sys_default = 0
				WHERE
					comp_id = ?
				"
		);
		$stmt->bind_param(
			"ssssiii",
			$this->name,
			$this->contactNumbers,
			$this->contactEmails,
			$this->address,
			$this->country->id,
			$this->businessField,
			$this->internalId
		);
		return $stmt->execute();
	}

	public function delete(): bool|null
	{
		if (empty($this->internalId)) {
			throw new \System\Core\Exceptions\Exceptions("No company loaded");
		}
		if (!$this->app->file->find($this->companySystemFileId)->permission->delete) {
			throw new \System\Core\Exceptions\Exceptions("Permissions denied");
		}

		$stmt = $this->app->db->prepare("DELETE FROM companies WHERE comp_id = ?;");
		$stmt->bind_param("i", $this->internalId);
		return $stmt->execute();
	}
}
