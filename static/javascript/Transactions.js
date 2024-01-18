$(document).ready(function (e) {
	const form_main = document.getElementById("js-ref_form-main");
	const slo_objects = $("#js-ref_form-main [data-slo]").not("#js-defines").slo();
	const value_field = document.getElementById('value');
	const description_field = document.getElementById('description');


	const initInvokers = function () {
		$("[name=value]").on("input keydown keyup mousedown mouseup select contextmenu drop", function () {
			OnlyFloat(this, null, 0);
		});
		document.getElementById("js-ref_form-main").addEventListener("submit", function (e) {
			e.preventDefault();
			postTransaction();
			return false;
		});

		description_field.addEventListener("keydown", function (e) {
			if (e.ctrlKey && e.key == "Enter") {
				postTransaction();
			}
		});
		slo_objects.getElementById("individual").slo.events.onselect = function (data) {
			slo_objects.getElementById("beneficiary").slo.set(data.hidden, data.value);
			$("#beneficiary").prop("readonly", true).prop("disabled", true);
		}
		slo_objects.getElementById("individual").slo.events.ondeselect = function () {
			$("#beneficiary").prop("readonly", false).prop("disabled", false);
		};

		$("#js-defines").slo({
			onselect: function (e) {
				const selected_option = document.querySelector("#defines option[data-id='" + e.hidden + "']");
				slo_objects.getElementById("target-account").slo.set(selected_option.dataset.account_bound, selected_option.dataset.account_bound);
				slo_objects.getElementById("category").slo.set(selected_option.dataset.category, selected_option.dataset.category);
				document.querySelectorAll(".predefined").forEach(element => {
					element.style.display = "none";
				});
				slo_objects.getElementById("beneficiary").slo.focus();
			},
			ondeselect: function (e) {
				slo_objects.clear();
				clearFields();
				document.querySelectorAll(".predefined").forEach(element => {
					element.style.display = "flex";
				});
			}
		});


		slo_objects.getElementById("target-account").slo.focus();
	}
	const disableForm = (state) => {
		$("#js-ref_form-main input, #js-ref_form-main textarea, #js-ref_form-main button").prop('disabled', state);
		overlay.state(state);
	}

	const validateFields = () => {
		let thrownerror = {
			occured: false,
			message: "",
			object: undefined
		};


		for (const reqmark of document.querySelectorAll("label>h1")) {
			reqmark.classList.remove("required");
		}
		slo_objects.each(function () {
			if (this.dataset.required != undefined && !this.slo.stamped) {
				this.slo.object.parentNode.parentNode.parentNode.querySelector("h1").classList.add("required");
				if (!thrownerror.occured) {
					thrownerror.occured = true;
					thrownerror.message = this.getAttribute("title") + " is required";
					thrownerror.object = this;
				}
			}
		});
		if (isNaN(parseFloat(value_field.value))) {
			value_field.parentNode.parentNode.querySelector("h1").classList.add("required");
			if (!thrownerror.occured) {
				thrownerror.occured = true;
				thrownerror.message = "Invalid statement value";
				thrownerror.object = value_field;
			}
		}
		if ((description_field.value).trim() == "") {
			description_field.parentNode.parentNode.querySelector("h1").classList.add("required");
			if (!thrownerror.occured) {
				thrownerror.occured = true;
				thrownerror.message = "Provide a description for the statment";
				thrownerror.object = description_field;
			}
		}


		if (thrownerror.occured) {
			thrownerror.object.focus();
			throw (thrownerror.message);
		}

	}
	const clearFields = () => {
		slo_objects.getElementById("beneficiary").slo.clear(false);
		value_field.value = "";
		description_field.value = "";
	}
	const postTransaction = async () => {
		try {
			validateFields();
		} catch (e) {
			messagesys.failure(e);
			return false;
		}

		try {
			const formData = new FormData(form_main);
			disableForm(true)
			let response = await fetch(callurl, {
				method: 'POST',
				mode: "cors", // no-cors, *cors, same-origin
				cache: "no-cache",
				credentials: "same-origin",
				referrerPolicy: "no-referrer",
				headers: {
					"Application-From": "same",
					"X-Requested-With": "fetch",
				},
				body: formData,
			});
			disableForm(false);

			if (response.ok) {
				const payload = await response.json()
				if (payload.result == true) {
					messagesys.success("Transaction `" + payload.insert_id + "` posted successfully");
					$("#jQoutput").prepend(
						"<tr>" +
						"<td>" + payload.insert_id + "</td>" +
						"<td align=\"right\">" + value_field.value + "</td>" +
						"<td>" + slo_objects.getElementById("beneficiary").slo.get()['value'] + "</td>" +
						"</tr>"
					);
					clearFields();
					Upload.clean();
					slo_objects.getElementById("beneficiary").slo.focus();
					$("#issuer-account-balance").html(payload.balance);
					$("#jqroot_bal").html(payload.balance + " " + payload.currency);
				} else {
					messagesys.failure(payload.error);
					if (payload.errno <= 199) {
						$("[data-touch=" + payload.errno + "]").slo.focus();
						alert(payload.errno);
					} else if (payload.errno <= 299) {
						$("[data-touch=200]").focus();
					} else if (payload.errno <= 399) {
						$("#js-input_submit").focus();
					}
				}
			}

		} catch (error) {
			messagesys.failure("Request failed, internal client error");
			console.log(error);
		}
	}

	initInvokers();

});