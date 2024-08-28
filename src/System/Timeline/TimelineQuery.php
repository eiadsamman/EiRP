<?php
declare(strict_types=1);

namespace System\Timeline;
use Generator;
use System\App;
use System\Attachment\File;
use System\Attachment\Properties;
use System\Profiles\IndividualProfile;

class TimelineQuery
{
	protected int $owner;
	protected array $_modules;
	protected array $_actions;
	protected ?\mysqli_result $_sqlResult;
	public function __construct(protected App $app, int $owner)
	{
		$this->_modules   = [];
		$this->_actions   = [];
		$this->owner      = $owner;
		$this->_sqlResult = null;
	}

	public function modules(Module ...$module): void
	{
		foreach ($module as $m) {
			$this->_modules[] = $m->value;
		}
	}

	public function actions(Action ...$action): void
	{
		foreach ($action as $m) {
			$this->_actions[] = $m->value;
		}
	}


	private function serialize(string $fieldName, array $obj): string
	{
		$output = "";
		if (sizeof($obj) > 0) {
			$output .= " AND (";
			$smart  = "";
			foreach ($obj as $i) {
				$output .= "$smart $fieldName = $i ";
				$smart  = "OR";
			}
			$output .= ") ";
		}
		return $output;
	}

	public function execute(): bool
	{
		$this->_sqlResult = null;
		$query_result     = $this->app->db->query(

			"SELECT
				tl_id, tl_issuer, tl_timestamp, tl_json, tl_message, tl_module, tl_action, 
				usr_id, usr_username, usr_firstname, usr_lastname
			FROM 
				timeline 
					JOIN users ON usr_id = tl_issuer		
			WHERE
				tl_owner = {$this->owner}
				{$this->serialize("tl_module", $this->_modules)}
				{$this->serialize("tl_action", $this->_actions)}
			ORDER BY
				tl_timestamp DESC
			"
		);
		if ($query_result) {
			$this->_sqlResult = $query_result;
			return true;
		} else {
			return false;
		}
	}

	private function decodeJson(null|string $payload): array|null
	{
		if (gettype($payload) == "string")
			try {
				return json_decode($payload, true);
			} catch (\Exception $e) {
				return [];
			}
		return null;
	}

	public function fetch(): Generator
	{
		if ($this->_sqlResult) {
			while ($row = $this->_sqlResult->fetch_assoc()) {
				$enrty     = new TimelineEntry();
				$enrty->id = (int) $row['tl_id'];

				$enrty->issuer            = new IndividualProfile();
				$enrty->issuer->id        = (int) $row['usr_id'];
				$enrty->issuer->username  = $row['usr_username'];
				$enrty->issuer->firstname = $row['usr_firstname'];
				$enrty->issuer->lastname  = $row['usr_lastname'];
				$enrty->moduel            = Module::tryFrom((int) $row['tl_module']);
				$enrty->action            = Action::tryFrom((int) $row['tl_action']);
				$enrty->timestamp         = new \DateTime($row['tl_timestamp']);
				$enrty->json              = $this->decodeJson($row['tl_json']);
				$enrty->message           = $row['tl_message'] ?? "";


				$r = $this->app->db->execute_query(
					"SELECT up_id,up_name,up_size,up_mime
					FROM uploads 
					WHERE up_rel = ? AND up_pagefile = ? AND up_active = 1"
					,
					[
						$enrty->id,
						\System\Attachment\Type::Timeline->value
					]
				);
				if ($r) {
					while ($rowup = $r->fetch_assoc()) {
						$pr       = new Properties();
						$pr->id   = $rowup['up_id'];
						$pr->name = $rowup['up_name'];
						$pr->size = $rowup['up_size'];
						$pr->mime = $rowup['up_mime'];
						$enrty->attachments[] = $pr;
					}
				}

				yield $enrty;
			}
		}
		return;
	}


}