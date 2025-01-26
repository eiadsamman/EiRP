<?php
use System\Layout\Gremium;

if (isset($_POST['bulk'])) {
	$att = new System\Controller\Individual\Attendance\Registration($app);
	$output = "";
	$lines = explode("\n", $_POST['bulk']);
	$unique = array();
	foreach ($lines as $line) {
		$_id = (int) $line;
		if ($_id != 0) {
			if (in_array($_id, $unique)) {
				continue;
			}
			$unique[] = $_id;
		}
	}
	echo $output;
	exit;
}


if (isset($_POST['serial'])) {
	$att = new System\Controller\Individual\Attendance\Registration($app);

	try {
		$att->load($_POST['serial']);
		$ratt = $att->CheckOut();

		if ($ratt) {
			header("ATT_RESULT: OK");
			header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
			echo $att->info->fullName();
		} else {
			header("ATT_RESULT: FAIL");
			header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
			echo $att->info->fullName();
		}
	} catch (\System\Core\Exceptions\HR\PersonNotFoundException $e) {
		header("ATT_RESULT: NOTFOUND");
		header("ATT_IMAGE_ID: 0");
	} catch (System\Controller\Individual\Attendance\ExceptionCheckedout $e) {
		header("ATT_RESULT: DUPLICATE");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
		echo $att->info->fullName();
	}
	exit;
}


$grem = new Gremium\Gremium(true);
$grem->header()->serve("<h1>{$fs()->title}</h1>");
$grem->menu()->serve('<input type="number" id="jQserialAdd" autocomplete="off" class="flex" placeholder="Serial Number" /><button type="button" style="min-width:100px;" id="jQserialSubmit">Submit</button>');
$grem->title()->serve("<span class=\"flex\">Attendance records</span>");
$grem->article()->serve("<div id=\"jqOutput\" data-empty class=\"att-submitionlist\">No records requested...</div>");
$grem->terminate();

?>


<script>
	$(function () {
		let _jqOutput = $("#jqOutput"),
			_jqInput = $("#jQserialAdd");
		var ticket = `<div>
				<span class="status l"><div></div></span>
				<span class="image"></span>
				<span class="content"><div class="employee-sid"></div><div class="employee-name">Loading...</div></span>
			</div>`;

		var submitserial = function () {
			let inputid = _jqInput.val().trim();
			if (inputid != "") {

				let new_ticket = $(ticket);
				new_ticket.find(".employee-sid").html(inputid);
				if (_jqOutput.attr("data-empty") !== undefined) {
					_jqOutput.removeAttr("data-empty");
					_jqOutput.html("")
				}
				_jqOutput.prepend(new_ticket);
				_jqInput.val("");

				$.ajax({
					url: '<?php echo $fs()->dir; ?>',
					method: 'POST',
					data: {
						'serial': inputid
					}
				}).done(function (o, textStatus, request) {
					let response = request.getResponseHeader('ATT_RESULT');

					let responseimage = request.getResponseHeader('ATT_IMAGE_ID');
					if (responseimage && responseimage != 0) {
						new_ticket.find(".image").css("background-image", `url('download/?id=${responseimage}&pr=t')`);
					} else {
						new_ticket.find(".image").css("background-image", `url('static/images/user-r.jpg')`);
					}

					if (response == "OK" || response == "DUPLICATE") {
						new_ticket.find(".employee-name").html(o);
						new_ticket.find(".status").removeClass("l").addClass("s").html("&#xf00c");
					} else {
						new_ticket.find(".status").removeClass("l").addClass("f").html("&#xf00d");
						if (response == "FAIL") {
							new_ticket.find(".employee-name").html(o);
						} else if (response == "NOTFOUND") {
							new_ticket.find(".employee-name").html("Not found");
						}
					}

				}).fail(function (a, b, c) {
					messagesys.failure(b + " " + c);
				}).always(function () {
					_jqInput.focus().select();
				});
			}
		}

		_jqInput.on('keydown', function (e) {
			if ((e.keyCode ? e.keyCode : e.which) == 13) {
				submitserial();
			}
		});

		$("#jQserialSubmit").on("click", function (e) {
			submitserial();
		});

		_jqInput.focus();
	});
</script>