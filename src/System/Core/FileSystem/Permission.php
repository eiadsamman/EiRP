<?php

declare(strict_types=1);

namespace System\Core\FileSystem;

use System\App;

class Permission
{
	public bool $deny = true;
	public  bool $read = false;
	public  bool $add = false;
	public  bool $edit = false;
	public  bool $delete = false;

	public App $app;

	public function __construct(?int $permission_number = null)
	{
		
		if ($permission_number != null) {
			$temp = str_pad(decbin((int)$permission_number), 4, "0", STR_PAD_LEFT);
			$this->deny = $permission_number == 0 ? true : false;
			$this->read = ((int)$temp[0] == 1 ? true : false);
			$this->add = ((int)$temp[1] == 1 ? true : false);
			$this->edit = ((int)$temp[2] == 1 ? true : false);
			$this->delete = ((int)$temp[3] == 1 ? true : false);
		}
	}
}
