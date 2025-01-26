<?php
use System\Unit;

$unit = new Unit();
echo "<pre>";

foreach ($unit->list(\System\Enum\UnitSystem::Mass->value) as $u) {
	var_dump($u->symbol);
}


exit;
use System\Lib\Upload\File;
use System\Models\Material;
$material   = $prd = new Material($app);
$matProfile = $prd->load((int) $_GET['materialId']);
if ($matProfile === false) {
	exit;
}
function ph($dateTime)
{
	$now  = $dateTime;
	$r    = [0, $now->format("Y"), $now->format("m"), $now->format("d")];
	$y    = [intval($r[1]), intval($r[2]), intval($r[3])];
	$f    = (intval($y[0] / 10) % 2) == 0 ? '%2$s%1$s%3$s' : '%1$s%2$s%3$s';
	$y[0] = strval($y[0])[3];
	$y[1] = chr(intval((8 % $y[1]) / 8) + 64 + $y[1]);
	$y[2] = ($y[2] < 10) ? strval($y[2]) : chr(intval((17 % $y[2]) / 17) + 64 + $y[2] - 9);
	return "1" . sprintf($f, $y[0], $y[1], $y[2]);
}


?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" xml:lang="en">

<head>
	<meta charset="utf-8" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Statement printing </title>

	<style type="text/css">
		html {
			--label-margin: 0.1cm;
			--cell-border-width: 2px;
		}

		#pageId {
			position: fixed;
			display: flex;
			flex: 1;
			inset: 0 0 0 0;
			font-size: 3em;
			align-items: center;
			text-align: center;
			width: 100%;
		}

		#pageId>div {
			font-family: Verdana, Geneva, Tahoma, sans-serif;
			flex: 1;
			align-items: center;
			text-align: center;
		}

		#label {
			visibility: none;
			display: none;
		}

		@page {
			margin: var(--label-margin);
			size: 6cm 8cm;
		}

		/* target the first page only */
		@page :first {
			margin-top: 0.2cm;
		}

		@page :blank {
			@top-center {
				content: "This page is intentionally left blank."
			}
		}

		@media print {
			#pageId {
				display: none;
			}

			body {
				font-family: 'Trebuchet MS', Verdana, 'Noto', 'Galada', sans-serif;
				font-size: 9px;
				margin: 0;
				padding: 0px;
				color: #000;
			}

			#label {
				display: grid;
				grid-template-columns: 1fr 1.2cm 1.2cm 1.5cm;
				grid-template-rows: 1.5cm 1cm 0.5cm 0.5cm 1.2cm 0.6cm 2.8cm;
				grid-column-gap: 0px;
				grid-row-gap: 0px;
				border-top: solid var(--cell-border-width) black;
				border-left: solid var(--cell-border-width) black;
			}

			#label>div {
				border-bottom: solid var(--cell-border-width) black;
				border-right: solid var(--cell-border-width) black;
				align-self: stretch;
				align-content: center;
				text-align: center;
				padding: 2px;
				white-space: nowrap;
			}

			#label {
				.div1 {
					grid-area: 1 / 1 / 2 / 3;
				}

				.div2 {
					grid-area: 1 / 3 / 2 / 5;
				}

				.div3 {
					grid-area: 2 / 1 / 3 / 4;
				}

				.div4 {
					grid-area: 2 / 4 / 3 / 5;
				}

				.div5 {
					grid-area: 3 / 1 / 4 / 2;
				}

				.div6 {
					grid-area: 3 / 2 / 4 / 3;
				}

				.div7 {
					grid-area: 3 / 3 / 4 / 4;
				}

				.div8 {
					grid-area: 4 / 1 / 5 / 2;
				}

				.div9 {
					grid-area: 4 / 2 / 5 / 4;
				}

				.div10 {
					grid-area: 5 / 1 / 6 / 2;
				}

				.div11 {
					grid-area: 5 / 2 / 6 / 4;
				}

				.div12 {
					grid-area: 6 / 1 / 7 / 2;
				}

				.div13 {
					grid-area: 6 / 2 / 7 / 4;
				}

				.div14 {
					grid-area: 3 / 4 / 6 / 5;
				}

				.div15 {
					grid-area: 6 / 4 / 7 / 5;
				}

				.div16 {
					grid-area: 7 / 1 / 8 / 5;
				}
			}
		}
	</style>
</head>

<body>
	<!-- https://cssgrid-generator.netlify.app/ -->
	<?php


	echo "<div id=\"pageId\"><div><div style=\"font-size:0.5em\">Printing...</div>{$matProfile->longId}</div></div>";
	//var_dump($matProfile->brand->name);
	$files = new File($app);

	$matLogoSVG = null;
	$matLogoPNG = null;
	foreach ($files->gallery(\System\Lib\Upload\Type::Material->value, $matProfile->id) as $file) {
		if ($matLogoSVG === null && $file->mime == "image/svg+xml") {
			$matLogoSVG = $file;
		} elseif ($matLogoPNG === null && $file->mime == "image/png") {
			$matLogoPNG = $file;
		}
	}

	$matLogo = null;
	if ($matLogoSVG !== null) {
		$matLogo = $app->http_root . $fs("download")->dir . "/?id=$matLogoSVG->id";
	} elseif ($matLogoPNG !== null) {
		$matLogo = $app->http_root . $fs("download")->dir . "/?id=$matLogoPNG->id";
	}

	$logoSVG = null;
	$logoPNG = null;
	$logo    = null;
	if ($matProfile->brand) {
		foreach ($files->gallery(\System\Lib\Upload\Type::BrandLogo->value, $matProfile->brand->id) as $file) {
			if ($logoSVG === null && $file->mime == "image/svg+xml") {
				$logoSVG = $file;
			} elseif ($logoPNG === null && $file->mime == "image/png") {
				$logoPNG = $file;
			}
		}


		if ($logoSVG !== null) {
			$logo = $app->http_root . $fs("download")->dir . "/?id=$logoSVG->id";
		} elseif ($logoPNG !== null) {
			$logo = $app->http_root . $fs("download")->dir . "/?id=$logoPNG->id";
		}
	}

	?>
	<div id="label">
		<div class="div1" style="">
			<?= $logo != null ? "<img style=\"max-width:2.5cm;\" src=\"$logo\" />" : ""; ?>
		</div>
		<div class="div2" style="">
			<?= $matProfile->eanCode; ?>
		</div>
		<div class="div3" style="text-align: left;white-space: wrap">
			<?= $matProfile->longName; ?>
		</div>
		<div class="div4" style="font-size: 20px;font-weight: bold;font-family: 'Trebuchet MS';">
			XX
		</div>
		<div class="div5" style="">
			<?= $matProfile->unitsPerBox . " " . $matProfile->category->name; ?>
		</div>
		<div class="div6" style="font-size: 6px;">
			Made in Egypt
		</div>
		<div class="div7" style="font-size: 6px;">
			XXXX XXX XXXX
		</div>
		<div class="div8" style="">
			1CT/<?= $matProfile->unitsPerBox . $matProfile->unit->name; ?>
		</div>
		<div class="div9" style="">
			<?= str_pad($matProfile->brand->id, 3, "0", STR_PAD_LEFT) . " " . str_pad($matProfile->category->group->id, 3, "0", STR_PAD_LEFT) . " " . str_pad($matProfile->category->id, 3, "0", STR_PAD_LEFT) . " " . str_pad($matProfile->id, 5, "0", STR_PAD_LEFT); ?>
		</div>
		<div class="div10" style="align-content: start !important;vertical-align: top;font-weight: bold;font-size: 1.1em;text-align: left;">
			<span style="padding:5px;">Mfg.</span>
		</div>
		<div class="div11" style="font-weight: bold;font-size: 1.1em;text-align: left;">
			EAN1<br />
			<?= $matProfile->eanCode === null || trim($matProfile->eanCode) == "" ? "-" : $matProfile->eanCode; ?>
		</div>
		<div class="div12" style="">
			SKU: <?= $matProfile->longId; ?>
		</div>
		<div class="div13" style="font-size:1.2em;padding-top:2px;text-align: left;">
			<span style="padding-left: 10px"><?= ph(new DateTime()); ?></span><span style="padding-right: 10px;float:right">(EG)</span>
		</div>
		<div class="div14" style="">
			<img style="width: 100%; max-height: 2cm;" src="<?= $matLogo; ?>" />
		</div>
		<div class="div15" style="">

		</div>

		<div class="div16" style="">
			16
		</div>
	</div>

	</hr>
</body>

</html>