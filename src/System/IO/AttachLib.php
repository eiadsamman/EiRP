<?php

declare(strict_types=1);

namespace System\IO;

class AttachLib
{
	public \System\App $app;
	public function __construct(&$app)
	{
		$this->app = $app;
	}
	public function delete($attach_id)
	{
		if (empty($this->app->settings->site['cdnpath']) || !is_dir($this->app->settings->site['cdnpath'])) {
			return false;
		}
		$cdnpath = rtrim($this->app->settings->site['cdnpath'], "\\/");

		$up_id = (int) $attach_id;
		$r     = $this->app->db->query("DELETE FROM uploads WHERE up_id=$up_id;");
		if ($r) {
			try {

				if (file_exists($cdnpath . DIRECTORY_SEPARATOR . $up_id))
					unlink($cdnpath . DIRECTORY_SEPARATOR . $up_id);
				if (file_exists($cdnpath . DIRECTORY_SEPARATOR . $up_id . "_v"))
					unlink($cdnpath . DIRECTORY_SEPARATOR . $up_id . "_v");
				if (file_exists($cdnpath . DIRECTORY_SEPARATOR . $up_id . "_t"))
					unlink($cdnpath . DIRECTORY_SEPARATOR . $up_id . "_t");
			} catch (\Exception $e) {
			}
			return true;
		} else {
			return false;
		}
	}

	public function detach($attach_id)
	{
		$up_id = (int) $attach_id;
		$r     = $this->app->db->query("UPDATE uploads SET up_rel = NULL ,up_active = 0 WHERE up_id=$up_id;");
		if ($r) {
			return true;
		} else {
			return false;
		}
	}
}
