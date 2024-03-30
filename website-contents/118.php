<?php

use System\Individual\Attendance\Registration;
use System\Template\Gremium;

$att = new Registration($app);
$loc = $att->DefaultCheckInternalAccounts($app->user->company->id);



if (isset($_POST['serial'])) {
	$att = new Registration($app);
	try {
		$att->load((int) $_POST['serial']);
		$ratt = $att->CheckIn((int) $_POST['partition']);

		if ($ratt) {
			header("ATT_RESULT: OK");
			header("ATT_IMAGE_ID: " . ($att->info->photoid ?? "0"));
			echo $att->info->fullName();
		} else {
			header("ATT_RESULT: FAIL");
			header("ATT_IMAGE_ID: " . ($att->info->photoid ?? "0"));
			echo $att->info->fullName();
		}
	} catch (\System\Exceptions\HR\PersonNotFoundException $e) {
		header("ATT_RESULT: NOTFOUND");
		header("ATT_IMAGE_ID: 0");
	} catch (\System\Exceptions\HR\PersonResignedException $e) {
		header("ATT_RESULT: RESIGNED");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ?? "0"));
		echo $att->info->fullName();
	} catch (\System\Individual\Attendance\ExceptionNotSignedIn $e) {
		header("ATT_RESULT: NOTSIGEND");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ?? "0"));
		echo $att->info->fullName();
	} catch (\System\Individual\Attendance\LocationInvalid $e) {
		header("ATT_RESULT: SECTOR");
		header("ATT_IMAGE_ID: 0");
	} catch (\System\Individual\Attendance\ExceptionTimeLimit $e) {
		header("ATT_RESULT: TIMELIMIT");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ?? "0"));
		echo $att->info->fullName();
	}

	exit;
}

if (isset($_POST['populate'])) {
	$att = new Registration($app);
	$sector = (int) $_POST['populate'];
	$r = $att->ReportOngoingBySector(["company" => $app->user->company->id, "sector" => $sector]);
	if ($r) {
		while ($row = $r->fetch_assoc()) {

			$photo = $row['up_id'] != null ? "download/?id={$row['up_id']}&pr=t" : "static/images/user-r.jpg";
			System\Template\Body::AttendanceTicketPlot(null, $photo, $row['lbr_id'], $row['usr_firstname']);
		}
	}
	exit;
}

$grem = new Gremium\Gremium(true);
$grem->header()->serve("<h1>{$fs()->title}</h1>");


$grem->menu()->open();

echo $app->user->account ? "<input type=\"button\" data-id=\"{$app->user->account->id}\" class=\"JQLocSelection\" style=\"width:130px;margin-right:10px;\" value=\"{$app->user->account->name}\">" : "";
foreach ($loc as $lock => $locv) {
	echo "<input type=\"button\" style=\"width:130px;\" class=\"JQLocSelection\" data-id=\"{$locv[0]}\" value=\"{$locv[1]}\">";
}
echo "<span class=\"gap\"></span>";
$grem->getLast()->close();

$grem->menu()->serve('<input type="number" id="jQserialAdd" autocomplete="off" class="flex" placeholder="Serial Number" /><button type="button" style="min-width:100px;" id="jQserialSubmit">Submit</button>');



$grem->title()->serve("<span class=\"flex\">Attendance records</span>");
$grem->article()->serve("<div id=\"jqOutputOutput\" data-empty class=\"att-submitionlist\">No records requested...</div>");


echo "<br />";
$grem->title()->serve("<span class=\"flex\">Current attendance</span>");
$grem->article()->serve("<div id=\"jqOutputCurrent\" data-empty class=\"att-submitionlist\">No records found...</div>");
?>
<script type="text/javascript">
	$(function () {
		var CurrentSelection = null;
		let _jqOutput = $("#jqOutputOutput"),
			_jqInput = $("#jQserialAdd");
		var ticket = `<div>
				<span class="status l"><div></div></span>
				<span class="image"></span>
				<span class="content"><div class="employee-sid"></div><div class="employee-name">Loading...</div></span>
			</div>`;

		$(".JQLocSelection").on("click", function () {
			$(".JQLocSelection").removeClass("clr-green");
			$(this).addClass("clr-green");
			CurrentSelection = $(this).attr("data-id");
			overlay.show();
			$.ajax({
				url: '<?php echo $fs()->dir; ?>',
				method: 'POST',
				data: { 'populate': CurrentSelection }
			}).done(function (o, textStatus, request) {
				if(o==""){
					$("#jqOutputCurrent").html("No records found...");
				}else{
					$("#jqOutputCurrent").html($(o));
				}
				_jqOutput.html("No records requested...");
				_jqOutput.attr("data-empty", "");
			}).fail(function (a, b, c) {
				messagesys.failure(b + " " + c);
			}).always(function () {
				overlay.hide();
			});
		});


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
						'serial': inputid,
						'partition': CurrentSelection
					}
				}).done(function (o, textStatus, request) {
					let response = request.getResponseHeader('ATT_RESULT');

					let responseimage = request.getResponseHeader('ATT_IMAGE_ID');
					if (responseimage && responseimage != 0) {
						new_ticket.find(".image").css("background-image", `url('download/?id=${responseimage}&pr=t')`);
					} else {
						new_ticket.find(".image").css("background-image", `url('static/images/user-r.jpg')`);
					}

					if (response == "OK") {
						new_ticket.find(".employee-name").html(o);
						new_ticket.find(".status").removeClass("l").addClass("s").html("&#xf00c");
					} else {
						new_ticket.find(".status").removeClass("l").addClass("f").html("&#xf00d");
						new_ticket.find(".image").css("filter", "grayscale(100%)");
						if (response == "FAIL") {
							new_ticket.find(".employee-name").html(o);
						} else if (response == "NOTFOUND") {
							new_ticket.find(".employee-name").html("Not found");
						} else if (response == "RESIGNED") {
							new_ticket.find(".employee-name").html(o + "<br />Resigned");
						} else if (response == "NOTSIGEND") {
							new_ticket.find(".employee-name").html(o + "<br />Not signed-in");
						} else if (response == "SECTOR") {
							new_ticket.find(".employee-name").html("Sector error");
						} else if (response == "TIMELIMIT") {
							new_ticket.find(".employee-name").html(o + "<br />Time limited");
							new_ticket.find(".status").addClass("t").html("&#xe94e;");
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

	});
</script>