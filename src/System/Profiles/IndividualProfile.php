<?php
declare(strict_types=1);

namespace System\Profiles;

enum Gender: int
{
	case Male = 1;
	case Female = 2;
}

class IndividualProfile
{
	public int $id;
	public string $name;
	public string $firstname;
	public ?string $middlename;
	public ?string $lastname;
	public string $username;
	public int $permissions;
	public int $level;
	public ?int $photoid;
	public ?string $contactNumber;
	public ?Gender $gender;
	public ?string $resignDate;
	public ?bool $resigned;
	public ?bool $active = false;


	public function fullName(): string
	{
		return(!empty($this->firstname) && $this->firstname != "" ? $this->firstname : "") .
			(!empty($this->middlename) && $this->middlename != "" ? " " . $this->middlename : "") .
			(!empty($this->lastname) && $this->lastname != "" ? " " . $this->lastname : "");
	}

	
}
