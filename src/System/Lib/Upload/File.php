<?php

namespace System\Lib\Upload;

use System\App;


class File
{

	public function __construct(public App &$app)
	{
	}

	private function sqlHeader(): string
	{
		return "SELECT 
			up_id, up_pagefile, up_rel, up_user, up_date ,
			up_size, up_name, up_mime, up_downloads, up_active, up_deleted, up_param_int	
		FROM 
			uploads 
		";
	}
	private function describe(array $res): Properties
	{
		$output           = new Properties();
		$output->id       = $res['up_id'];
		$output->scope    = (int) $res['up_pagefile'];
		$output->relation = (int) $res['up_rel'];
		$output->uploader = (int) $res['up_user'];
		$output->size     = (int) $res['up_size'];
		$output->name     = (string) $res['up_name'] ?? "";
		$output->mime     = (string) $res['up_mime'] ?? "";
		$output->active   = !is_null($res['up_active']) && (int) $res['up_active'] == 1 ? true : false;
		$output->deleted  = !is_null($res['up_deleted']) && (int) $res['up_deleted'] == 1 ? true : false;
		$output->default  = !is_null($res['up_param_int']) && (int) $res['up_param_int'] == 1 ? true : false;
		$output->datetime = new \DateTime($res['up_date']);
		return $output;
	}

	public function load(int $file_id): Properties|bool
	{
		$stmt = $this->app->db->prepare($this->sqlHeader() . "WHERE up_id = ?;");
		$stmt->execute([$file_id]);
		$result = $stmt->get_result();
		if ($result->num_rows > 0 && $row = $result->fetch_assoc()) {
			return $this->describe($row);
		}
		return false;
	}

	public function gallery(int $scope, int $relation): \Generator
	{
		$stmt = $this->app->db->prepare($this->sqlHeader() . "WHERE up_pagefile = ? AND up_rel = ? AND up_deleted = 0 AND up_active = 1;");
		$stmt->execute([$scope, $relation]);
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				yield $this->describe($row);
			}
		}
	}

	public function all(): \Generator
	{
		$stmt = $this->app->db->prepare($this->sqlHeader() . "WHERE up_id = 0 LIMIT 0, 1;");
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				yield $this->describe($row);
			}
		}
	}


}