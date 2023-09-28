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
		$up_id = (int)$attach_id;
		$this->app->db->autocommit(false);
		$r = $this->app->db->query("DELETE FROM uploads WHERE up_id=$up_id;");
		if ($r) {
			$dr = true;
			try {
				if (file_exists($app->root . "uploads/" . $up_id))
					$dr &= @unlink($app->root . "uploads/" . $up_id);
				if (file_exists($app->root . "uploads/" . $up_id . "_v"))
					$dr &= @unlink($app->root . "uploads/" . $up_id . "_v");
				if (file_exists($app->root . "uploads/" . $up_id . "_t"))
					$dr &= @unlink($app->root . "uploads/" . $up_id . "_t");
			} catch (\Exception $e) {
			}
			if ($dr) {
				$this->app->db->commit();
				return true;
			} else {
				$this->app->db->rollback();
				return false;
			}
		} else {
			return false;
		}
	}

	public function detach($attach_id)
	{
		$up_id = (int)$attach_id;
		$r = $this->app->db->query("UPDATE uploads SET up_rel = NULL ,up_active = 0 WHERE up_id=$up_id;");
		if ($r) {
			return true;
		} else {
			return false;
		}
	}
}
