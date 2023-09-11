<?php

declare(strict_types=1);

namespace System;

class ResponseStatusCode
{
	public function __construct(public int $code, public string $name, public string $message, public bool $destructive)
	{
	}
	public function response(): string
	{
		header(sprintf("%s %d %s", "HTTP/1.1", $this->code, $this->name));
		if ($this->destructive) {
			die(sprintf("<h1>%d - %s</h1>%s", $this->code, $this->name, $this->message));
		}
		return '';
	}
}
class ResponseStatus
{
	public ResponseStatusCode $OK, $Accepted, $BadRequest, $Unauthorized, $Forbidden, $NotFound, $InternalServerError, $ServiceUnavailable;
	public function __construct()
	{
		$this->OK = new ResponseStatusCode(200, "OK", "", false);
		$this->Accepted = new ResponseStatusCode(202, "Accepted", "", false);
		$this->BadRequest = new ResponseStatusCode(400, "Bad Request", "Requested page does not exists on this server or current services are unavailable", true);
		$this->Unauthorized = new ResponseStatusCode(401, "Unauthorized", "Requested page does not exists on this server or current services are unavailable", true);
		$this->Forbidden = new ResponseStatusCode(403, "Forbidden", "Request page is forbidden", false);
		$this->NotFound = new ResponseStatusCode(404, "Not Found", "Requested page does not exists on this server or current services are unavailable", true);
		$this->InternalServerError = new ResponseStatusCode(500, "Internal Server Error", "Requested page does not exists on this server or current services are unavailable", true);
		$this->ServiceUnavailable = new ResponseStatusCode(503, "Service Unavailable", "Requested page does not exists on this server or current services are unavailable", true);
	}
}
