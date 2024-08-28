<?php
use System\Template\Gremium;

if ($app->xhttp) {
	$payload = json_decode(file_get_contents('php://input'), true);

	$grem = new Gremium\Gremium(true);

	$grem->header()->prev("href=\"{$fs(173)->dir}\" data-href=\"{$fs(173)->dir}\"")->serve("
		<h1>{$fs()->title}</h1><cite></cite>
		<div class=\"btn-set\"><button id=\"js-input_submit\" tabindex=\"9\">&nbsp;Search</button></div>
	");
	$grem->title()->serve("<span class=\"flex\">Search criteria</span>");
	$grem->article()->open();

	$hash = md5($app->id . $app->user->company->id);
	
	echo <<<HTML
		<form action="{$fs(173)->dir}" id="searchForm" style="max-width:500px">
			<div class="form">
				<label>
					<h1>Customer</h1>
					<div class="btn-set">
						<input name="company" id="company" type="text" placeholder="Select company..." class="flex" title="Company name" data-slo=":LIST"
						tabindex="-1" data-source="_/CompaniesList/slo/{$hash}/slo_CompaniesList.a" />
					</div>
				</label>
			</div>

			<div class="form" style="margin-top: 30px">
				<label for="">
					<div class="btn-set">
						<button type="submit" tabindex="8">Search</button><input type="button" class="edge-right" value="Cancel" data-href="{$fs(173)->dir}" />
					</div>
				</label>
			</div>
		</form>

	HTML;
	$grem->getLast()->close();
	$grem->terminate();
	unset($grem);

}