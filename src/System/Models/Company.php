<?php
declare(strict_types=1);

namespace System\Models;

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
			"State" => $this->state,
			"Address" => $this->address,
			"BusinessField" => $this->businessField,
			"ContactNumbers" => $this->contactNumbers,
			"ContactEmails" => $this->contactEmails,
			"CommercialNo" => $this->commercialRegistrationNumber,
			"TaxNo" => $this->taxRegistrationNumber,
			"VatNo" => $this->vatRegistrationNumber,
			"BankName" => $this->bankName,
			"BankNo" => $this->bankAccountNumber,
		];
	}
	public function prepare(): void
	{
		$stringProperties = ["name", "state", "address", "contactNumbers", "contactEmails", "commercialRegistrationNumber", "taxRegistrationNumber", "vatRegistrationNumber", "bankName", "bankAccountNumber"];
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
				comp_emaillist, 
				comp_address, 
				comp_country, 
				comp_field, 
				comp_sys_default, 
				comp_comercialregnumber, 
				comp_taxnumber, 
				comp_vatnumber
			FROM 
				companies
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
				$this->id         = (int) $row['comp_id'];
				$this->internalId = (int) $row['comp_id'];
				$this->name       = $row['comp_name'];
				return true;
			}
		}
		return false;
	}

	public function insert(): bool|null
	{
		if (!$this->app->file->find($this->companySystemFileId)->permission->add) {
			throw new \System\Exceptions\Exceptions("Permissions denied");
		}
		$this->prepare();
		$stmt = $this->app->db->prepare(
			"INSERT INTO companies
				(comp_name, comp_tellist, comp_emaillist, comp_address, comp_country, comp_field, comp_sys_default, comp_comercialregnumber, comp_taxnumber, comp_vatnumber)
				VALUES
				(?,?,?,?,?,?,0,?,?,?);
				"
		);
		$stmt->bind_param(
			"ssssiisss",
			$this->name,
			$this->contactNumbers,
			$this->contactEmails,
			$this->address,
			$this->country,
			$this->businessField,
			$this->commercialRegistrationNumber,
			$this->taxRegistrationNumber,
			$this->vatRegistrationNumber
		);
		if ($stmt->execute()) {
			$this->internalId = $stmt->insert_id;
			$this->id         = $this->internalId;
			return true;
		} else {
			return false;
		}

	}
	public function update(): bool|null
	{
		if (empty($this->internalId)) {
			throw new \System\Exceptions\Exceptions("No company loaded");
		}
		if (!$this->app->file->find($this->companySystemFileId)->permission->edit) {
			throw new \System\Exceptions\Exceptions("Permissions denied");
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
					comp_sys_default = 0,
					comp_comercialregnumber = ?,
					comp_taxnumber = ?,
					comp_vatnumber = ?
				WHERE
					comp_id = ?
				"
		);
		$stmt->bind_param(
			"ssssiisssi",
			$this->name,
			$this->contactNumbers,
			$this->contactEmails,
			$this->address,
			$this->country,
			$this->businessField,
			$this->commercialRegistrationNumber,
			$this->taxRegistrationNumber,
			$this->vatRegistrationNumber,
			$this->internalId
		);
		return $stmt->execute();
	}

	public function delete(): bool|null
	{
		if (empty($this->internalId)) {
			throw new \System\Exceptions\Exceptions("No company loaded");
		}
		if (!$this->app->file->find($this->companySystemFileId)->permission->delete) {
			throw new \System\Exceptions\Exceptions("Permissions denied");
		}

		$stmt = $this->app->db->prepare("DELETE FROM companies WHERE comp_id = ?;");
		$stmt->bind_param("i", $this->internalId);
		return $stmt->execute();
	}
}
