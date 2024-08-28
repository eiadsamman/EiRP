<?php
declare(strict_types=1);

namespace System\Timeline;
use System\Profiles\IndividualProfile;

class TimelineEntry
{
	public int $id;
	public Module $moduel;
	public Action $action;
	public \DateTime $timestamp;
	public int $owner;
	public IndividualProfile $issuer;
	public ?array $json;
	public ?string $message;
	public ?array $attachments = [];

	public function describe(): string
	{
		return Describer::describe($this);
	}
}

class Timeline
{
	public function __construct(protected \System\App $app)
	{
	}

	public function register(Module $module, Action $action, int $owner, ?array $json = null, ?string $message = null, \Datetime $scheduleDate = null): bool|int
	{
		$json_encoded = !is_null($json) ? json_encode($json) : null;
		$result       = $this->app->db->execute_query("INSERT INTO `timeline`
			(tl_module, tl_action, tl_owner, tl_issuer, tl_json, tl_message, tl_remind_date) VALUES (?,?,?,?,?,?,?);
			", [
			$module->value,
			$action->value,
			$owner,
			$this->app->user->info->id,
			$json_encoded,
			$message,
			$scheduleDate != null ? $scheduleDate->format("Y-m-d") : null
		]);
		if (!$result) {
			return false;
		} else {
			return $this->app->db->insert_id;
		}
	}

	public function query(int $owner): TimelineQuery
	{
		return new TimelineQuery($this->app, $owner);
	}



}