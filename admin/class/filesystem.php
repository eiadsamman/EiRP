<?php

namespace System;

class FilePermission
{
	public bool $deny = true;
	public  bool $read = false;
	public  bool $add = false;
	public  bool $edit = false;
	public  bool $delete = false;
}
class FileData
{
	public int $id = 0;
	public string $dir = "";
	public string $directory = "";
	public string $title = "";
	public int $parent = 0;
	public bool $enabled = false;
	public bool $visible = false;
	public string $icon = "";
	public string $color = "";
	public string $parameters = "";
	public FilePermission $permission;
}

class FileSystem
{
	private array $files = array();
	private \System\FileData $use;
	function __construct(int $language = 1)
	{
		$this->use = new \System\FileData();
		$this->files[0] =  new \System\FileData();

		if ($r = Pool::$sql->query(
			"SELECT 
				trd_id, trd_directory,pfl_value,trd_parent,trd_enable, trd_visible,
				trd_keywords, trd_description, trd_header, trd_param, trd_attrib4, trd_attrib5, pfp_value
			FROM 
				pagefile 
					LEFT JOIN pagefile_language ON pfl_trd_id = trd_id AND pfl_lng_id={$language}
					LEFT JOIN pagefile_permissions ON pfp_trd_id = trd_id AND pfp_per_id = " . (Pool::$_user->logged ? Pool::$_user->info->permissions : Pool::$base_permission) . " 
			ORDER BY
				trd_parent, trd_zorder
			;"
		)) {
			while ($row = Pool::$sql->fetch_assoc($r)) {
				$data = new \System\FileData();
				$data->id = (int)$row['trd_id'];
				$data->dir = $row['trd_directory'];
				$data->directory = $row['trd_directory'];
				$data->title = $row['pfl_value'];
				$data->parent = (int)$row['trd_parent'];
				$data->icon = (string)$row['trd_attrib4'];
				$data->color = (string)$row['trd_attrib5'];
				$data->visible = (bool)$row['trd_visible'];
				$data->enabled = (bool)$row['trd_enable'];
				$data->parameters = (string)$row['trd_param'];


				$temp = str_pad(decbin(($row['pfp_value'])), 4, "0", STR_PAD_LEFT);
				$data->permission = new \System\FilePermission;
				$data->permission->deny = $row['pfp_value'] == 0 ? true : false;
				$data->permission->read = ((int)$temp[0] == 1 ? true : false);
				$data->permission->add = ((int)$temp[1] == 1 ? true : false);
				$data->permission->edit = ((int)$temp[2] == 1 ? true : false);
				$data->permission->delete = ((int)$temp[3] == 1 ? true : false);

				$this->files[$row['trd_id']] = $data;
			}
		}

		if (false && $rper = Pool::$sql->query("SELECT per_id,pfp_value FROM permissions LEFT JOIN pagefile_permissions ON per_id=pfp_per_id AND pfp_trd_id={$row['id']};")) {
		}
	}
	function setUse(int $id): bool
	{
		if (array_key_exists($id, $this->files)) {
			$this->use = $this->files[$id];
			return true;
		}
		return false;
	}

	public function __invoke(int $id): \System\FileData|bool
	{
		return $this->find($id);
	}
	function use(): \System\FileData
	{
		return $this->use;
	}

	public function find(int $id): \System\FileData|bool
	{
		if (array_key_exists($id, $this->files)) {
			return $this->files[$id];
		} else {
			return false;
			//return $this->files[0];
		}
	}


	public function dir(string $dir): \System\FileData|bool
	{
		return false;
	}
}
