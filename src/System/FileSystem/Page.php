<?php
declare(strict_types=1);
namespace System\FileSystem;

class Page extends Data
{
	private array $files = array();
	protected \System\App $app;
	private Data $inuse;
	private array $map = ["download" => 187];
	function __construct(\System\App &$app, int $language = 1)
	{
		$this->app      = $app;
		$this->inuse    = new Data();
		$this->files[0] = new Data();
		if (
			$r = $this->app->db->query(
				"SELECT 
				trd_id, trd_directory, pfl_value, trd_parent, trd_enable, trd_visible,
				trd_keywords, trd_description, trd_header, trd_param, trd_attrib4, trd_attrib5, pfp_value, trd_loader
			FROM 
				pagefile 
					LEFT JOIN pagefile_language ON pfl_trd_id = trd_id AND pfl_lng_id = {$language}
					LEFT JOIN pagefile_permissions ON pfp_trd_id = trd_id AND pfp_per_id = " . ($this->app->user->logged ? $this->app->user->info->permissions : $this->app->base_permission) . " 
			ORDER BY
				trd_parent, trd_zorder
			;"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				$data            = new Data();
				$data->id        = (int) $row['trd_id'];
				$data->dir       = $row['trd_directory'];
				$data->directory = $row['trd_directory'];
				$data->title     = $row['pfl_value'];
				$data->parent    = (int) $row['trd_parent'];
				$data->icon      = (string) $row['trd_attrib4'];
				$data->color     = (string) $row['trd_attrib5'];
				$data->visible   = (bool) $row['trd_visible'];
				$data->enabled   = (bool) $row['trd_enable'];
				$data->loader    = $row['trd_loader'] ?? "";
				//$data->parameters = (string)$row['trd_param'];

				$data->permission            = new Permission((int) $row['pfp_value']);
				$this->files[$row['trd_id']] = $data;
			}
		}

		if (false && $rper = $this->app->db->query("SELECT per_id, pfp_value FROM permissions LEFT JOIN pagefile_permissions ON per_id=pfp_per_id AND pfp_trd_id={$row['id']};")) {
		}
	}
	function load(int $id): bool
	{
		if (array_key_exists($id, $this->files)) {
			$this->inuse = $this->files[$id];
			$this->details($id);

			$this->id         = $this->inuse->id;
			$this->dir        = $this->inuse->dir;
			$this->directory  = $this->inuse->directory;
			$this->title      = $this->inuse->title;
			$this->parent     = $this->inuse->parent;
			$this->forward    = $this->inuse->forward;
			$this->enabled    = $this->inuse->enabled;
			$this->visible    = $this->inuse->visible;
			$this->icon       = $this->inuse->icon;
			$this->color      = $this->inuse->color;
			$this->parameters = $this->inuse->parameters;
			$this->headers    = $this->inuse->headers;
			$this->cdns       = $this->inuse->cdns;
			$this->loader     = $this->inuse->loader;
			$this->permission = $this->inuse->permission;
			return true;
		}
		return false;
	}

	public function loaded(): Data
	{
		return $this->inuse;
	}
	public function __invoke(int|string $id = null): Data|bool
	{
		if ($id === null) {
			return $this->inuse;
		} else {
			return $this->find($id);
		}
	}
	public function find(int|string $id): Data|bool
	{
		if (gettype($id) == "integer" && $id != 0 && array_key_exists($id, $this->files)) {
			return $this->files[$id];
		} elseif (gettype($id) == "string" && array_key_exists($id, $this->map) && array_key_exists($this->map[$id], $this->files)) {
			return $this->files[$this->map[$id]];
		}
		return false;
	}
	public function children(int $id): \Generator
	{
		foreach ($this->files as $file) {
			if ($file->parent == $id && $file->id != 0) {
				yield $file->id => $file;
			}
		}
	}
	public function parse(string $dir): Data|bool
	{
		$_dir = trim($dir);
		foreach ($this->files as $file) {
			if ($file->dir == $_dir) {
				return $file;
			}
		}
		$this->app->responseStatus->NotFound;
		return false;
	}
	public function details(int $id): void
	{
		$stmt = $this->app->db->prepare("SELECT trd_css,trd_js,trd_header,trd_forward,trd_param FROM pagefile WHERE trd_id=?");
		$stmt->bind_param("i", $id);
		if ($stmt->execute() && $res = $stmt->get_result()) {
			if ($row = $res->fetch_assoc()) {
				$this->files[$id]->parameters = (string) $row['trd_param'];
				$this->files[$id]->cdns       = array("css" => (string) $row['trd_css'], "js" => (string) $row['trd_js']);
				$this->files[$id]->forward    = (int) $row['trd_forward'];
				$this->files[$id]->headers    = array(
					"html-header" => (int) ((string) $row['trd_header'])[1],
					"contents" => (int) ((string) $row['trd_header'])[0]
				);
			}
		}
	}

	public function __serialize(): array
	{
		return [$this->inuse->id];
	}

}