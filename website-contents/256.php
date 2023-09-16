<?php

use System\Template\Body;

$expirydate = false;
$rexp = $app->db->query("SELECT UNIX_TIMESTAMP(lbr_resigndate) AS rdate FROM labour WHERE lbr_id = {$app->user->info->id};");
if ($rexp && $rrow = $rexp->fetch_assoc()) {
	$expirydate = $rrow['rdate'];
}


if ($expirydate != false && $expirydate <= time()) {
	$_TEMPLATE = new Body("Candas");
	$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ false,/*Sticky Frame*/ false);
	$_TEMPLATE->FrameTitlesStack(true);
	$_TEMPLATE->SetWidth("100%");

	$_TEMPLATE->Title("&nbsp;Free trial has expired", null, "", "mark-error");
	$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Solid/profiled pipes analys free trial has expired</span>");
	$_TEMPLATE->NewFrameBody('<ul>
		<li>Registered application in invalid</li>
		<li>Expiry date has reached</li>
		<li>Contact us at `info@candas.cn` for more information</li>
		<ul>');
	exit;
}
if (isset($_POST['method']) && $_POST['method'] == "saveimage") {
	$filename = uniqid() . ".png";
	if (isset($_FILES['imagefile'])) {
		$res = move_uploaded_file($_FILES['imagefile']['tmp_name'], $app->root . "/" . $filename);
		if ($res) {
			echo "Image saved to\n" . "uploades/$filename\n\n";
			exit;
		} else {
			echo "Image saving failed";
			exit;
		}
	}
	echo "No files reached server side";
	exit;
}



$_TEMPLATE = new Body("");
$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ false,/*Sticky Frame*/ false);
$_TEMPLATE->FrameTitlesStack(true);
$_TEMPLATE->SetWidth("100%");
$_TEMPLATE->Title("Solid/profiled pipes ", null, ($expirydate != false ? '<span style="color:#f03;font-size:0.7em;padding:8px;">
	Free trial expires on ' . date("Y-m-d", $expirydate) . '
	<span style="font-family:icomoon4;display:inline-block;padding-left:5px">&#xea08</span>
	</span>' : ""));
?>

<style type="text/css">
	.slider {
		appearance: none;
		-webkit-appearance: none;
		width: 100%;
		height: 12px;
		border-radius: 10px;
		background: #eeeeee;
		outline: none;
		opacity: 0.7;
		margin: 10px 0px;
	}

	.slider::-webkit-slider-thumb {
		appearance: none;
		-webkit-appearance: none;
		width: 25px;
		height: 25px;
		border-radius: 50%;
		background: #0066cc;
		border: none;
	}

	.slider::-moz-range-thumb {
		width: 25px;
		height: 25px;
		border-radius: 50%;
		border: none;
		background: #0066cc;
	}

	.bom-table>tbody>tr>th {
		line-height: 1.3em;
		min-width: 130px;
	}

	.bom-table>tbody>tr>td>div.btn-set {
		max-width: 340px;
	}

	.cmdbar {
		align-items: center;
		justify-content: flex-end;
		max-width: 500px;
		white-space: nowrap;
	}

	.frame-title {
		padding: 20px 0px 7px 0px;
		font-weight: bold;
		color: #555;
		margin-bottom: 2px;
		font-style: italic;
	}

	.main-view {
		width: 100%;
		border-collapse: collapse;
		border: none;
	}

	.input-error {
		color: #f03;
		margin-top: 5px;
		padding: 5px 7px;
		background-color: #fffad6;
		max-width: 340px;
		font-size: 0.9em;
		white-space: normal;
		line-height: 1.4em;
		display: none;
	}

	@media only screen and (max-width: 900px) {
		.main-view>tbody>tr>td {
			display: block;
			padding-left: 0px;
			width: 100%;
		}

		.main-view>tbody>tr>td:nth-child(2) {
			display: none;
		}

		.bom-table>tbody>tr>td>div.btn-set {
			max-width: 100%;
		}

		.input-error {
			max-width: 100%;
		}

		.cmdbar {
			max-width: 100%;
			;
		}
	}
</style>

<table class="main-view">
	<tbody>
		<tr>
			<td valign="top" style="min-width: 430px;">
				<div style="position: sticky;top: 111px;">
					<?php $_TEMPLATE->NewFrameTitle("<span class=\"flex\">Profile sketch</span>"); ?>
					<br />
					<table class="bom-table">
	</tbody>
	<tr>
		<th style="min-width:100px;">Profile drawing</th>
		<td width="100%">
			<input type="file" id="js-inputfile" style="display: none;">
			<div class="btn-set">
				<input type="button" value="Browse..." onclick="document.getElementById('js-inputfile').click();" />
			</div>

		</td>
	</tr>
	<tr>
		<th>Options</th>
		<td>
			<div><input id="js-invertcolors" name="js-invertcolors" type="checkbox"><label for="js-invertcolors">Invert Colors</label></div>
			<div style="max-width:300px;"><input type="range" min="1" class="slider" max="252" value="180" id="js-thresholdslider"></div>
		</td>
	</tr>
	<!--
							<tr>
								<th>Input dimensions</th>
								<td class="btn-set"><input type="text" id="js-output-imgsrc_dim" readonly></td>
							</tr>
							<tr>
								<th>Centroid</th>
								<td class="btn-set"><input type="text" id="js-output-centroid" readonly></td>
							</tr>
							<tr>
								<th>Surface Area</th>
								<td class="btn-set"><input type="text" id="js-output-area" readonly></td>
							</tr>
							-->
	<tr style="display:none">
		<td colspan="2">
			<div class="btn-set"><button id="js-saveimage">Save image</button><button id="js-clearall">Clear</button></div>
		</td>
	</tr>

	</tbody>
</table>

<div style="border:solid 1px #E6E6EB;text-align: center;margin-top: -1px;">
	<canvas id="js-canvas" style="text-align:center"></canvas>
</div>
</div>
</td>
<td style="min-width:15px"></td>
<td valign="top" width="100%">

	<?php $_TEMPLATE->NewFrameTitle("<span class=\"flex\">Profile data for radial calculation</span>"); ?>
	<br />
	<div>
		<table class="bom-table">
			<tbody>
				<tr>
					<th>A type<br /> predeformation</th>
					<td width="100%">
						<div class="btn-set"><input class="flex" style="text-align:right;" type="text" value="1"><span style="width:50px;text-align: right;">%</span></div>
					</td>
				</tr>

				<tr>
					<th>Local<br />predeformation</th>
					<td width="100%">
						<div class="btn-set"><input class="flex" style="text-align:right;" type="text" value="0"><span style="width:50px;text-align: right;">%</span></div>
					</td>
				</tr>
				<tr>
					<th>Inner diameter</th>
					<td width="100%">
						<div class="btn-set"><span style="width:40px;">d<sub>i</sub></span><input class="flex" style="text-align:right;" type="text" id="js-input-pipediameter_inner" value="2500"><span style="width:50px;text-align: right;">mm</span></div>
						<div class="input-error" id="js-input-pipediameter_inner_">Invalid `Pipe inner diameter`, accepted range (1~10,000)mm</div>
					</td>
				</tr>

				<tr>
					<th>Profile width</th>
					<td width="100%">
						<div class="btn-set"><input class="flex" style="text-align:right;" type="text" id="js-output-actualwidth" value="120"><span style="width:50px;text-align: right;">mm</span></div>
						<div class="input-error" id="js-output-actualwidth_">Invalid profile width, accepted range (1~2000)mm</div>
					</td>
				</tr>
			</tbody>
		</table>

		<div class="frame-title">General values</div>
		<table class="bom-table">
			<tbody>
				<tr>
					<th>Material density</th>
					<td width="100%">
						<div class="btn-set"><input class="flex" style="text-align:right;" type="text" id="js-input-mat_density" value="0.949"><span style="width:70px;text-align: right;">g/cm³</span></div>
						<div class="input-error" id="js-input-mat_density_">Invalid material density, accepted range (0~100)g/mm³</div>
					</td>
				</tr>
				<tr>
					<th>Specific weight</th>
					<td width="100%">
						<div class="btn-set"><input class="flex" style="text-align:right;" type="text" value="9.4"><span style="width:70px;text-align: right;">kN/m³</span></div>
					</td>
				</tr>
				<tr>
					<th>Poission's ratio</th>
					<td width="100%">
						<div class="btn-set"><input class="flex" style="text-align:right;" type="text" value="0.38"><span style="width:50px;text-align: right;">[-]</span></div>
					</td>
				</tr>

			</tbody>
		</table>



		<div class="frame-title">Radial values</div>
		<table class="bom-table">
			<tbody>
				<tr>
					<th>Young's modulus</th>
					<td width="100%">
						<div class="btn-set"><span>short term</span><input class="flex" style="text-align:right;" type="text" id="js-input-youngmod-short" value="800"><span style="width:70px;text-align: right;">N/mm²</span></div>
						<div class="input-error" id="js-input-youngmod-short_">Invalid input</div>

					</td>
				</tr>

				<tr>
					<th>Ultimate flexural<br />tensile stress</th>
					<td width="100%">
						<div class="btn-set"><span>short term</span><input class="flex" style="text-align:right;" type="text" value="21"><span style="width:70px;text-align: right;">N/mm²</span></div>
					</td>
				</tr>
				<tr>
					<th>Ultimate flexural<br />compressive stress</th>
					<td width="100%">
						<div class="btn-set"><span>short term</span><input class="flex" style="text-align:right;" type="text" value="35"><span style="width:70px;text-align: right;">N/mm²</span></div>
					</td>
				</tr>
				<tr>
					<th>Hoop tensile<br />strength</th>
					<td width="100%">
						<div class="btn-set"><span>short term</span><input class="flex" style="text-align:right;" type="text" value="17"><span style="width:70px;text-align: right;">N/mm²</span></div>
					</td>
				</tr>
				<!--
								long terms N/mm2
								160
								14
								23
								8.4
							-->

			</tbody>
		</table>

		<div class="frame-title">
			<div class="btn-set cmdbar">
				<button id="js-update-stud" style="max-width: 200px;" class="flex">Update pipe parameters</button>
			</div>
		</div>

	</div>

	<?php $_TEMPLATE->NewFrameTitle("<span class=\"flex\">Profile properties</span>"); ?>
	<br />
	<table class="bom-table">
		<tbody>
			<tr>
				<th>Profile height</th>
				<td>
					<div class="btn-set"><span style="width:40px;">h</span><input class="flex" type="text" style="text-align:right;" id="js-output-body_height" readonly><span style="width:50px;text-align: right;">mm</span></div>
				</td>
			</tr>
			<tr>
				<th>Profile centroid</th>
				<td>
					<div class="btn-set"><input class="flex" type="text" style="text-align:right;" id="js-output-body_centroid" readonly><span style="width:50px;text-align: right;">mm</span></div>
				</td>
			</tr>
			<tr>
				<th>Profile surface area</th>
				<td width="100%">
					<div class="btn-set"><input class="flex" type="text" style="text-align:right;" id="js-output-body_area" readonly><span style="width:50px;text-align: right;">mm</span></div>
				</td>
			</tr>
			<tr>
				<th>Profile moment of<br /> Inertia</th>
				<td width="100%">
					<div class="btn-set"><span style="width:50px">Lxx</span><input class="flex" style="text-align:right;" type="text" id="js-output-moi_lxx" readonly><span style="width:70px;text-align: right;">g/mm²</span></div>
					<br />
					<div class="btn-set"><span style="width:50px">Lyy</span><input class="flex" style="text-align:right;" type="text" id="js-output-moi_lyy" readonly><span style="width:70px;text-align: right;">g/mm²</span></div>
				</td>
			</tr>

			<tr>
				<th>Pipe inner diameter</th>
				<td>
					<div class="btn-set"><input class="flex" type="text" style="text-align:right;" id="js-output-inner_diameter" readonly><span style="width:50px;text-align: right;">mm</span></div>
				</td>
			</tr>
			<tr>
				<th>Pipe outer diameter</th>
				<td>
					<div class="btn-set"><input class="flex" type="text" style="text-align:right;" id="js-output-outer_diameter" readonly><span style="width:50px;text-align: right;">mm</span></div>
				</td>
			</tr>
			<tr>
				<th>Pipe mean radius</th>
				<td>
					<div class="btn-set"><input class="flex" type="text" style="text-align:right;" id="js-output-mean_radius" readonly><span style="width:50px;text-align: right;">mm</span></div>
				</td>
			</tr>

			<tr>
				<th>Profiled pipe mass</th>
				<td width="100%">
					<div class="btn-set"><span style="width:70px">1 meter</span><input class="flex" type="text" style="text-align:right;" id="js-output-pipemass" readonly><span style="width:50px;text-align: right;">Kg</span></div>
				</td>
			</tr>


			<tr>
				<th>SR</th>
				<td width="100%">
					<div class="btn-set"><input class="flex" type="text" style="text-align:right;" id="js-output-sr" readonly><span style="width:70px;text-align: right;">N/mm²</span></div>
				</td>
			</tr>
			<tr>
				<th>SN</th>
				<td width="100%">
					<div class="btn-set"><input class="flex" type="text" style="text-align:right;" id="js-output-sn" readonly><span style="width:70px;text-align: right;">N/mm²</span></div>
				</td>
			</tr>

		</tbody>
	</table>


</td>
</tr>
</tbody>
</table>

<script type="text/javascript">
	$(document).ready(function(e) {

		let DOMFields = {
			outputProfileCentroid: document.getElementById("js-output-body_centroid"),
			outputProfileArea: document.getElementById("js-output-body_area"),
			outputProfileLxx: document.getElementById("js-output-moi_lxx"),
			outputProfileLyy: document.getElementById("js-output-moi_lyy"),
			outputProfileHeight: document.getElementById("js-output-body_height"),
			outputProfileSR: document.getElementById("js-output-sr"),
			outputProfileSN: document.getElementById("js-output-sn"),
			outputPipeInnerDiameter: document.getElementById("js-output-inner_diameter"),
			outputPipeOuterDiameter: document.getElementById("js-output-outer_diameter"),
			outputPipeMeanRadius: document.getElementById("js-output-mean_radius"),
			outputPipeMass: document.getElementById("js-output-pipemass"),


			inputFile: document.getElementById("js-inputfile"),
			inputProfileWidth: document.getElementById("js-output-actualwidth"),
			inputProfileThickness: document.getElementById("js-output-actualthickness"),
			inputPipeInnerDiam: document.getElementById("js-input-pipediameter_inner"),
			inputDataYoungShort: document.getElementById("js-input-youngmod-short"),
			inputDataMaterialDensity: document.getElementById("js-input-mat_density"),

			cmdClear: document.getElementById("js-clearall"),
			cmdCalculate: document.getElementById("js-update-stud"),
		}

		let DOMInvoke = [
			[DOMFields.inputProfileWidth, null, (v) => p.param.ProfileWidth = v],
			[DOMFields.inputPipeInnerDiam, null, (v) => p.param.PipeDiameter = v],
			[DOMFields.inputDataYoungShort, null, (v) => p.param.YoungModulus_short = v],
			[DOMFields.inputDataMaterialDensity, null, (v) => p.param.MaterialDensity = v]
		]

		let Update = function() {
			let confirminput = true;
			for (let i = 0; i < DOMInvoke.length; i++) {
				try {
					DOMInvoke[i][2](DOMInvoke[i][0].value)
					if (DOMInvoke[i][1] != null) {
						DOMInvoke[i][1].style.display = 'none';
					}
					DOMInvoke[i][0].style.background = "#ffffff";
				} catch (e) {
					confirminput &= false;
					if (DOMInvoke[i][1] != null) {
						DOMInvoke[i][1].style.display = 'block';
					}
				}
			}
			if (confirminput)
				p.Process();
		}


		p = candas.Pyhsics({
			onupdate: function(result) {
				DOMFields.outputProfileCentroid.value = Math.round(result.extracted.centroid.X) + "x" + Math.round(result.extracted.centroid.Y);
				DOMFields.outputProfileArea.value = result.extracted.area.numberFormat(2);
				DOMFields.outputProfileLxx.value = result.extracted.moi.lxx.numberFormat(2);
				DOMFields.outputProfileLyy.value = result.extracted.moi.lyy.numberFormat(2);
				DOMFields.outputProfileHeight.value = result.extracted.height.numberFormat(2);
				DOMFields.outputProfileSR.value = result.extracted.sr.numberFormat(2);
				DOMFields.outputProfileSN.value = result.extracted.sn.numberFormat(2);
				DOMFields.outputPipeMass.value = (result.pipe.mass / 10 ** 3).numberFormat(2);

				DOMFields.outputPipeInnerDiameter.value = result.pipe.inner_diameter.numberFormat(2);
				DOMFields.outputPipeOuterDiameter.value = result.pipe.outer_diameter.numberFormat(2);
				DOMFields.outputPipeMeanRadius.value = result.pipe.mean_radius.numberFormat(2);

			},
			onload: function(result) {
				/*document.getElementById("js-output-imgsrc_dim").value = result.inputsize.X + "x" + result.inputsize.Y;*/
			},
			centroid_axis_color: "#55BB88",
			color_threshold: 100,
			plotter_width: 426
		});
		p.PlotCanvas(document.getElementById("js-canvas"));


		DOMFields.cmdClear.onclick = function() {
			DOMFields.inputFile.value = null;
			//document.getElementById("js-output-imgsrc_dim").value 	= "";
			//document.getElementById("js-output-centroid").value 	= "";
			//document.getElementById("js-output-area").value 		= "";
			DOMFields.outputProfileCentroid.value = "0";
			DOMFields.outputProfileArea.value = "0";
			DOMFields.outputProfileLxx.value = "0";
			DOMFields.outputProfileLyy.value = "0";
			DOMFields.outputProfileHeight.value = "0";
			DOMFields.outputProfileSR.value = "0";
			DOMFields.outputProfileSN.value = "0";
			DOMFields.outputPipeInnerDiameter.value = "0";
			DOMFields.outputPipeOuterDiameter.value = "0";
			DOMFields.outputPipeMeanRadius.value = "0";
			DOMFields.outputPipeMass.value = "0";

			p.Clear();
		}

		DOMFields.inputFile.onchange = function() {
			if (DOMFields.inputFile.files && DOMFields.inputFile.files[0]) {
				p.ReceiveFile(DOMFields.inputFile.files[0]);
			}
		}

		for (let i = 0; i < DOMInvoke.length; i++) {
			DOMInvoke[i][1] = document.getElementById(DOMInvoke[i][0].id + "_");

			DOMInvoke[i][0].addEventListener("keydown", function(event) {
				this.style.background = "#fffad6";
			});
			DOMInvoke[i][0].addEventListener("keypress", function(event) {
				if (event.key === "Enter") {
					event.preventDefault();
					Update();
					this.select();
				}
			});
		}

		DOMFields.cmdCalculate.onclick = function() {
			Update();
		}

		let domThresholdSlider = document.getElementById("js-thresholdslider");
		domThresholdSlider.oninput = function() {
			p.ColorThreshold(this.value);
			p.Process();
		}

		let domInvertColors = document.getElementById("js-invertcolors");
		domInvertColors.onchange = function() {
			p.ColorInvert(this.checked);
			p.Process();
		}

		let domButtonSave = document.getElementById("js-saveimage");
		domButtonSave.onclick = function() {
			//p.SaveImage();
		}


		p.ColorInvert(false);
		p.LoadURL("<?php echo $app->http_root . "sample.png"; ?>");
	});
</script>