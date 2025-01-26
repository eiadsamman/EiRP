<?php

declare(strict_types=1);

namespace System\Controller\Finance;


class AccountRole
{
	public bool|null $inbound = null;
	public bool|null $outbound = null;
	public bool|null $access = null;
	public bool|null $view = null;
	public function __construct()
	{
	}
	public function sqlClause(): string
	{
		$output = " (1 ";

		if (!is_null($this->inbound)) {
			$output .= $this->inbound ? " AND upr_prt_inbound = 1 " : " AND upr_prt_inbound = 0 ";
		}
		if (!is_null($this->outbound)) {
			$output .= $this->outbound ? " AND upr_prt_outbound = 1 " : " AND upr_prt_outbound = 0 ";
		}
		if (!is_null($this->access)) {
			$output .= $this->access ? " AND upr_prt_fetch = 1 " : " AND upr_prt_fetch = 0 ";
		}
		if (!is_null($this->view)) {
			$output .= $this->view ? " AND upr_prt_view = 1 " : " AND upr_prt_view = 0 ";
		}

		$output .= " ) ";
		return $output;
	}
}
