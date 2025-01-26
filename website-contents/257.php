<?php

use System\Layout\Gremium\Gremium;
if ($h__requested_with_ajax && isset($_POST['posubmit'])) {
}





?>
<style type="text/css">
	.l {
		position: relative;
		font-size: 0.8em;
		top: 3px
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

	@media only screen and (max-width: 1200px) {
		.input-error {
			max-width: 100%;
		}

		.tcell {
			display: block;
		}
	}
</style>

<script>MathJax = { options: { enableMenu: false, } }</script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>



<?php
$grem = new Gremium();

//echo "<a href=\"" . $fs(258)->dir . "\" style=\"color:#000\" target=\"_blank\">Materials manager</a>";
//<button id=\"jQpostSubmit\" type=\"button\">Print Report</button>


$grem->header()->serve("<h1>Nominal ring stifness</h1>");
$grem->menu()->sticky(false)->serve("<span class=\"gap\"></span><button id=\"jQpostSubmit\" class=\"edge-left\" type=\"button\">Update</button>");


$grem->title()->serve("Solid-wall pipes");
$grem->article()->open();
?>
<table class="main">
	<tbody>
		<tr>
			<td width="100%">Material type</td>
			<td></td>
			<td class="btn-set">
				<select id="js-input-material" style="width:100%">
					<?php
					$rmat = $app->db->query("SELECT cndmat_id,cndmat_name,cndmat_young,cndmat_creep FROM candas_materials ");
					if ($rmat) {
						while ($rowmat = $rmat->fetch_assoc()) {
							echo "<option data-value=\"{$rowmat['cndmat_young']}\" data-creepmodulus=\"{$rowmat['cndmat_creep']}\">{$rowmat['cndmat_name']}</option>";
						}
					}

					?>
				</select>
			</td>
		</tr>
		<tr>
			<td>Young’s modulus</td>
			<td>E</td>
			<td>
				<div class="btn-set"><input class="flex" id="js-outout-youngmodulus" readonly type="text"><span>MPa</span></div>
			</td>
		</tr>
		<tr>
			<td>Standard Dimensions Ratio</td>
			<td>SDR</td>
			<td>
				<div class="btn-set"><input data-error="err61" class="flex" id="js-input-sdr" type="text" value="11"><span>D/s</span>
				</div>
				<span class="input-error" id="err61">Invalid SDR value, accepted range (1~100)</span>
			</td>
		</tr>
		<tr>
			<td>Creep modulus</td>
			<td>c</td>
			<td>
				<div class="btn-set"><input class="flex" id="js-outout-creepmod" readonly type="text"></div>
			</td>
		</tr>
		<tr>
			<td>Nominal ring stiffness</td>
			<td>SN</td>
			<td>
				<div class="btn-set"><input class="flex" id="js-output-nomringstiff" readonly type="text"><span>KPa</span></div>
			</td>
		</tr>
		<tr>
			<td>Long term young modulus</td>
			<td>E0</td>
			<td>
				<div class="btn-set"><input class="flex" id="js-outout-longtermyoungmod" readonly type="text"><span>MPa</span></div>
			</td>
		</tr>
		<tr>
			<td>Long term stiffness</td>
			<td>SN<span class="l">0</span></td>
			<td>
				<div class="btn-set"><input class="flex" id="js-outout-longtermstiff" readonly type="text"><span>KPa</span></div>
			</td>
		</tr>
	</tbody>
</table>
<?php
$grem->getLast()->close();

$grem->title()->serve("Structured-wall pipes <b>ISO 9969</b>");
$grem->article()->open();

?>
<table class="main">
	<tbody>
		<tr>
			<td width="100%">Load @ 3% (ISO 9969)</td>
			<td>F<span class="l">3%</span></td>
			<td>
				<div class="btn-set"><input class="flex" data-error="err95" id="js-input-load" type="text" value="1200"><span>N</span>
				</div>
				<span class="input-error" id="err95">Invalid Load value, accepted range (1~10000)N</span>
			</td>
		</tr>


		<tr>
			<td>Sample length</td>
			<td>L</td>
			<td>
				<div class="btn-set"><input class="flex" data-error="err103" id="js-input-samplelength" type="text" value="300"><span>mm</span></div>
				<span class="input-error" id="err103">Invalid Sample length, accepted range (1~10000)N</span>
			</td>
		</tr>

		<tr>
			<td>Pipe internal diameter</td>
			<td>Di</td>
			<td>
				<div class="btn-set"><input class="flex" data-error="err110" id="js-input-internaldim" type="text" value="135"><span>mm</span></div>
				<span class="input-error" id="err110">Invalid diameter, accepted range (1~10000)N</span>
			</td>
		</tr>

		<tr>
			<td>Nominal ring stiffness</td>
			<td>SN</td>
			<td>
				<div class="btn-set"><input class="flex" id="js-output-sSN" readonly type="text"><span>KPa</span></div>
			</td>
		</tr>

	</tbody>
</table>
<?php
$grem->getLast()->close();


$grem->article()->open();

?>
<h1>Nominal Ring Stiffness</h1>
<div>
	<p style="font-size:1.1em;line-height: 2em;padding-left: 10px;">
		<span
			  style="margin: 15px;float:right;background-size: 150px;width: 150px;height: 150px;background-repeat: no-repeat; border-radius: 200px;background-image:url('candas-pipe_sn.jpg');background-position:50% 50%;display: inline-block;"></span>
		The ring stiffness of a pipe describes the force-deformation ratio under a radially acting external mechanical load. Ring
		stiffness corresponds to an upward slope in the force-deformation diagram.
		This characteristic is typically measured to <b>ISO 9969</b> or <b>ASTM D2412</b> for thermoplastic pipes, and to <b>EN
			1228</b> for glass fiber-reinforced pipes.
		<br /><br />This tool calculate the SN using two alternative methods:
	<ul style="font-size:1.1em;line-height: 1.2em;">
		<li>
			Nominal ring stiffness<br /><i>(Solid-wall pipes made of PE or PVC-U)</i>
			<p>\[SN \approx {E \over {12 (SDR - 1)^3 } }\]</p>
		</li>
		<li>
			Nominal ring stiffness<br /><i>(Structured-wall pipes tested according to ISO 9969)</i>
			<p>\[SN \approx {+ {0.01935 * F_{3\%}} \over ( 0.03 * LD_i ) }\]</p>
		</li>
	</ul>
	</p>
</div>

<?php
$grem->terminate();
?>


<script type="text/javascript">
	isNumber = function isNumber(value) {
		return typeof value === 'number' && isFinite(value);
	}
	let events = ["input", "change"];
	let DOMFields = {
		input: {
			material: {
				class: "option",
				obj: document.getElementById("js-input-material"),
				val: [0, 0]
			},
			sdr: {
				class: "input",
				obj: document.getElementById("js-input-sdr"),
				val: false,
				translate: [(v) => parseFloat(v), 1, 100]
			},
			load: {
				class: "input",
				obj: document.getElementById("js-input-load"),
				val: false,
				translate: [(v) => parseFloat(v), 1, 10000]
			},
			samplelength: {
				class: "input",
				obj: document.getElementById("js-input-samplelength"),
				val: false,
				translate: [(v) => parseFloat(v), 1, 10000]
			},
			internaldim: {
				class: "input",
				obj: document.getElementById("js-input-internaldim"),
				val: false,
				translate: [(v) => parseFloat(v), 1, 10000]
			},
		},
		output: {
			youngModulus: {
				class: "input",
				obj: document.getElementById("js-outout-youngmodulus"),
				val: false
			},
			nominalRingStiffness: {
				class: "input",
				obj: document.getElementById("js-output-nomringstiff"),
				val: false
			},
			creepModulus: {
				class: "input",
				obj: document.getElementById("js-outout-creepmod"),
				val: false
			},
			longTermYoungModulus: {
				class: "input",
				obj: document.getElementById("js-outout-longtermyoungmod"),
				val: false
			},
			longTermStiffness: {
				class: "input",
				obj: document.getElementById("js-outout-longtermstiff"),
				val: false
			},
			SN: {
				class: "input",
				obj: document.getElementById("js-output-sSN"),
				val: false
			}

		}
	}


	for (let i in DOMFields.input) {
		let jsEntity = DOMFields.input[i];
		for (let e = 0; e < events.length; e++) {
			jsEntity.obj.addEventListener(events[e], function (event) {
				this.style.background = "#fffad6";
			});
		}
		jsEntity.obj.addEventListener("keypress", function (event) {
			if (event.key === "Enter") {
				event.preventDefault();
				calc();
				if (jsEntity.class == "input") {
					this.select();
				}
			}
		});
	}
	for (let i in DOMFields.output) {
		DOMFields.output[i].obj.style.background = "#f9f9fb";
		DOMFields.output[i].obj.tabIndex = -1;
	}



	function calc() {
		let error = false;
		for (let i in DOMFields.input) {
			let jsEntity = DOMFields.input[i];
			if (jsEntity.class == "input") {
				DOMFields.input[i].val = jsEntity.translate[0](jsEntity.obj.value);

				if (jsEntity.obj.dataset.error != undefined) {
					if (!isNumber(DOMFields.input[i].val) || (DOMFields.input[i].val < jsEntity.translate[1] || DOMFields.input[i].val > jsEntity.translate[2])) {
						document.getElementById(jsEntity.obj.dataset.error).style.display = 'block';
						error = true;
					} else {
						DOMFields.input[i].obj.value = DOMFields.input[i].val;
						document.getElementById(jsEntity.obj.dataset.error).style.display = 'none';
					}
				} else {
					error = true;
				}

			} else if (jsEntity.class == "option") {
				DOMFields.input[i].val[0] = jsEntity.obj.options[jsEntity.obj.selectedIndex].dataset.value;
				DOMFields.input[i].val[1] = jsEntity.obj.options[jsEntity.obj.selectedIndex].dataset.creepmodulus;
			}

			jsEntity.obj.style.background = "#ffffff";
		}

		if (!error) {
			DOMFields.output.SN.val = ((DOMFields.input.material.val[0] / 12) * 1000) / Math.pow(DOMFields.input.sdr.val - 1, 3);
			DOMFields.output.youngModulus.obj.value = (~~DOMFields.input.material.val[0]).numberFormat(2);
			DOMFields.output.creepModulus.obj.value = DOMFields.input.material.val[1];
			DOMFields.output.nominalRingStiffness.obj.value = (DOMFields.output.SN.val).numberFormat(2);
			DOMFields.output.longTermYoungModulus.obj.value = ((DOMFields.input.material.val[0]) / DOMFields.input.material.val[1]).numberFormat(2);
			DOMFields.output.longTermStiffness.obj.value = (DOMFields.output.SN.val / DOMFields.input.material.val[1]).numberFormat(2);
			DOMFields.output.SN.obj.value = (0.01935 * DOMFields.input.load.val * 1000 / (DOMFields.input.samplelength.val * 0.03 * DOMFields.input.internaldim.val)).numberFormat(2);
		}
	}
	calc();
	$(function () {

		let Update = function () {
			overlay.show();
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: $("#jQpostFormDetails").serialize() + "&" + $("#jQpostFormMaterials").serialize(),
			}).done(function (o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				if (response == "INERR") {
					messagesys.failure(o);
				} else if (response == "SUCCESS") {
					messagesys.success("Posted successfully");
					Template.PageRedirect("<?php echo $fs(240)->dir; ?>" + o, "<?php echo "{$c__settings['site']['title']} - " . $fs(240)->title; ?>", true);
					Template.ReloadSidePanel();
				} else if (response == "DBERR") {
					messagesys.failure(o);
				}
			}).fail(function (m) {
				messagesys.failure(m);
			}).always(function () {
				overlay.hide();
			});
		}
	});
</script>