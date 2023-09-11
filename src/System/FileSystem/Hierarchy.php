<?php

declare(strict_types=1);

namespace System\FileSystem;


class Hierarchy
{
	private $parentList = array();
	private $_permissions = null;
	protected \System\App $app;


	private function Iterate($parent, $nest = 1)
	{
		echo "<div>";
		foreach ($this->parentList[$parent] as $k => $v) {
			$hadChildren = isset($this->parentList[$k]);
			echo "<b " . ($hadChildren ? "class=\"nested\"" : "") . " style=\"padding-left:" . ($nest * 33) . "px;\">" .
				($v['trd_attrib4'] == null ? "" : "<span " . ($v['trd_attrib5'] != null ? "style=\"color:#{$v['trd_attrib5']}\"" : "") . ">&#xe{$v['trd_attrib4']};</span>") .
				"<a class=\"alink\" href=\"{$v['trd_directory']}\">{$v['pfl_value']}</a></b>";
			if ($hadChildren) {
				$this->Iterate($k, $nest + 1);
			}
		}
		echo "</div>";
	}
	public function __construct(&$app, $permissions = null)
	{
		$this->app = $app;
		$output = array();
		if ($permissions == null) {
			$this->_permissions = 0;
		} else {
			$this->_permissions = (int)$permissions;
		}
		$r = $this->app->db->query(
			"SELECT 
				trd_directory, trd_id, pfl_value, trd_attrib4, trd_attrib5, trd_parent
			FROM 
				pagefile 
					JOIN pagefile_language ON pfl_trd_Id=trd_id AND pfl_lng_id = 1 
					JOIN pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$this->_permissions} AND pfp_value > 0
			WHERE 
				trd_enable = 1 AND trd_visible = 1 
			ORDER BY trd_parent,trd_zorder;"
		);
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				if (!isset($this->parentList[$row['trd_parent']])) {
					$this->parentList[$row['trd_parent']] = array();
				}
				$this->parentList[$row['trd_parent']][$row['trd_id']] = $row;
			}
		}
		if (isset($this->parentList[0]) && is_array($this->parentList[0])) {
			foreach ($this->parentList[0] as $k => $v) {
				echo "<b>" . ($v['trd_attrib4'] == null ? "" : "<span " . ($v['trd_attrib5'] != null ? "style=\"color:#{$v['trd_attrib5']}\"" : "") . ">&#xe{$v['trd_attrib4']};</span>") . "<a class=\"alink\" href=\"{$v['trd_directory']}\">{$v['pfl_value']}</a></b>";
				if (isset($this->parentList[$k])) {
					$this->Iterate($k);
				}
			}
		}
	}
}
