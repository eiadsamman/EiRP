<?php
use System\Template\Gremium;


if ($app->xhttp) {

	$accounting  = new \System\Finance\Accounting($app);
	$perpage_val = 20;

	$payload = json_decode(file_get_contents('php://input'), true);

	if (isset($payload['objective']) && $payload['objective'] == 'list') {
		exit;
	}


	$grem = new Gremium\Gremium(true);

	$grem->header()->prev("href=\"{$fs(214)->dir}\" data-href=\"{$fs(214)->dir}\"")->serve("
		<h1>{$fs()->title}</h1><cite></cite>
		<div class=\"btn-set\"><button id=\"js-input_submit\" tabindex=\"9\">&nbsp;Search</button></div>
	");
	$grem->title()->serve("<span class=\"flex\">Search criteria</span>");
	$grem->article()->open();

	echo <<<HTML
		<form action="{$fs(214)->dir}" id="searchForm" style="max-width:500px">
			<div class="form">
				<label>
					<h1>Statement ID</h1>
					<div class="btn-set">
						<input class="flex" type="number" placeholder="Statement ID..." name="statement-id" tabindex="1" autofocus inputmode="decimal" title="Statement ID"  />
					</div>
				</label>
			</div>
			<div class="form">
				<label>
					<h1>Post Date</h1>
					<div class="btn-set">
						<input type="text" placeholder="Date range start..." class="flex" data-slo=":DATE" title="Date range start" tabindex="2" name="date-start" />
						<input type="text" placeholder="Date range end..." class="flex" data-slo=":DATE" title="Date range end" tabindex="3" name="date-end" />

					</div>
				</label>
			</div>
			<div class="form">
				<label>
					<h1>Beneficiary</h1>
					<div class="btn-set">
						<input class="flex" type="text" placeholder="Beneficiary name..." tabindex="4" name="beneficiary" />
					</div>
				</label>
			</div>
			<div class="form">
				<label>
					<h1>Caregory</h1>
					<div class="btn-set">
						<input type="text" placeholder="Statement category"  data-slo=":LIST" title="Category"
							data-source="_/FinanceCategoryList/slo/{$app->id}{$app->user->company->id}/slo_FinancialCategories.a" 
							data-list="jQcategoryList" tabindex="5" class="flex" name="category" id="category" />
					</div>
				</label>
			</div>

			<div class="form">
				<label>
					<h1>Description</h1>
					<div class="btn-set">
						<input class="flex" type="text" placeholder="Statement description and remarks..." tabindex="6" name="description" />
					</div>
				</label>
			</div>

			

			<div class="form" style="margin-top: 30px">
				<label for="">
					<div class="btn-set">
						<button type="submit" tabindex="7">Search</button><input type="button" class="edge-right" value="Cancel" data-href="{$fs(214)->dir}" />
					</div>
				</label>
			</div>
		</form>

	HTML;
	$grem->getLast()->close();
	$grem->terminate();
	unset($grem);

}