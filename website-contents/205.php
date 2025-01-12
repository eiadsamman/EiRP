<?php
if ($app->xhttp) {
	if (!empty($_POST['method']) && $_POST['method'] == "save") {
		if (!empty($_POST['prefix']) && !empty($_POST['digits'])) {

			try {
				$stmt = $app->db->prepare("INSERT INTO system_prefix (prx_company, prx_sector, prx_enumid, prx_value, prx_placeholder ) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE prx_value=?, prx_placeholder=?");
				$stmt->bind_param("isisisi", $company, $sector, $enumid, $prefix, $digits, $prefix, $digits);

				$company = $app->user->company->id;
				foreach ($_POST['prefix'] as $sectorkey => $enums) {
					foreach ($enums as $enumkey => $enumvalue) {
						$sector = $sectorkey;
						$enumid = (int) $enumkey;
						$prefix = $_POST['prefix'][$sectorkey][$enumkey] ?? "";
						$digits = (int) $_POST['digits'][$sectorkey][$enumkey];

						$stmt->execute();
					}
				}
				echo "success";
			} catch (Exception $e) {
				$app->errorHandler->customError($e->getMessage());
			}
		}
	}

	exit;
}







use System\Template\Gremium\Gremium;


$maps = [
	["Finance \ Purchasing", System\Finance\Invoice\enums\Purchase::class],
	["Finance \ Sales", System\Finance\Invoice\enums\Sale::class],
	["Finance \ Transactions", System\Finance\Transaction\enums\Type::class],
];



/**
 * List all records 
 * 
 **/
$records = [];
$query   = $app->db->execute_query(
	"SELECT prx_sector, prx_enumid, prx_value, prx_placeholder FROM system_prefix WHERE prx_company = ?",
	[$app->user->company->id]
);

if ($query) {
	while ($row = $query->fetch_assoc()) {
		if (!isset($records[$row['prx_sector']])) {
			$records[$row['prx_sector']] = [];
		}
		$records[$row['prx_sector']][$row['prx_enumid']] = [$row['prx_value'], (int) $row['prx_placeholder']];
	}
}


echo "<form id=\"js-form_main\" action=\"{$fs()->dir}\">";
echo "<fieldset id=\"form-fieldset\">";
$grem = new Gremium(true, false);
$grem->header()->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\"><button class=\"success\" id=\"js-input_submit\">&nbsp;Save changes</button></div>");
$grem->title()->serve("<span>Documents branding</span>");
$grem->title()->serve("<span>Documents naming & prefixes</span>");


$grem->article()->open();

foreach ($maps as $map) {
	echo ("<h1>{$map[0]}</h1>");

	echo <<<HTML
		<div class="table local01">
			<header>
				<div>Prefix</div>
				<div>Digits</div>
				<div>Document</div>
			</header>
	HTML;
	foreach ($map[1]::cases() as $enum) {
		if ($enum->value == 0)
			continue;
		$values = ["", 0];
		if (array_key_exists($map[1], $records) && array_key_exists($enum->value, $records[$map[1]])) {
			$values    = $records[$map[1]][$enum->value];
			$values[0] = htmlentities($values[0], ENT_QUOTES);
		}
		echo "<main>";
		echo "<div><input class=\"number-field compact\" name=\"prefix[{$map[1]}][$enum->value]\" type=\"text\" value=\"{$values[0]}\" title=\"Prefix\" /></div>";
		echo "<div><input class=\"number-field compact\" name=\"digits[{$map[1]}][$enum->value]\" type=\"text\" value=\"{$values[1]}\" title=\"Digits\" /></div>";
		echo "<div class=\"ellipsis\" style=\"padding-top:16px\">$enum->name</div>";
		echo "</main>";
	}
	echo <<<HTML
		</div>
	HTML;
}
$grem->getLast()->close();
$grem->terminate();

echo "</fieldset>";
echo "</form>";

?>
<style>
	.table {
		&.local01 {
			grid-template-columns: 130px 80px minmax(130px, 1fr);
			padding-bottom: 40px;

			>main {
				& .compact {
					text-align: left;
				}
			}
		}
	}
</style>

<script type="module">
	import { Navigator } from './static/javascript/modules/app.js';

	var nav = new Navigator({
		'page': 1,
		'_search': ''
	}, '<?= $fs()->dir; ?>');


	class Branding {
		constructor() {
			this.fetch = null;
			this.form = document.getElementById("js-form_main");
			this.fieldset = document.getElementById("form-fieldset");
			this.form.addEventListener("submit", (e) => {
				e.preventDefault();
				this.save();
				return false;
			});
		}

		save() {
			const formData = new FormData(this.form);
			formData.append("method", "save");
			overlay.show();
			this.fieldset.disabled = true;
			this.fetch = fetch(this.form.action, {
				method: 'POST',
				mode: "cors",
				cache: "no-cache",
				credentials: "same-origin",
				referrerPolicy: "no-referrer",
				headers: {
					"X-Requested-With": "fetch",
					"Application-From": "same",
					'Accept': 'application/json',
				},
				body: formData,
			}).then(response => {
				this.fieldset.disabled = false;
				overlay.hide();
				if (response.ok) return response.text();
				return Promise.reject(response);
			}).then(res => {
				if (res == "success") {
					messagesys.success("Changes saved");
				}
			}).catch(response => {
				overlay.hide();
				this.fieldset.disabled = false;
				console.log(response);
			});
		}
	}

	new Branding();

</script>