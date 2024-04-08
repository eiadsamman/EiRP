
let form_main = inputFields = slo_input = inputFieldsSorted = slo_objects = value_field = description_field = busy = null;
let Upload = null;
let addwin = null;
$(document).ready(function (e) {

	findNextInputField = function (target) {
		let located = false;
		for (let i in inputFieldsSorted) {
			if (located) {
				return inputFieldsSorted[i];
			}
			if (inputFieldsSorted[i] == target) {
				located = true;
			}
		}
		return false;
	}

	initInvokers = function () {

		document.getElementById("js-input_edit")?.addEventListener("click", () => {

		});

		form_main = document.getElementById("js-ref_form-main");
		if (form_main == null) {
			return
		}

		Upload = $.Upload({
			objectHandler: $("#js_upload_list"),
			domselector: $("#js_uploader_btn"),
			dombutton: $("#js_upload_trigger"),
			list_button: $("#js_upload_count"),
			emptymessage: "[No files uploaded]",
			delete_method: 'permanent',
			upload_url: pageConfig.upload.url,
			relatedpagefile: pageConfig.upload.identifier,
			multiple: true,
			inputname: "attachments",
			domhandler: $("#UploadDOMHandler"),
		});
		Upload.update();

		inputFields = form_main.querySelectorAll("input,textarea");
		inputFieldsSorted = new Array();
		let firstItemFocus = null;
		for (const field of inputFields) {
			if (field.tabIndex && field.tabIndex > 0) {
				inputFieldsSorted[parseInt(field.tabIndex)] = field;
				/** Ordinary input field */
				if (field.dataset.slo == undefined) {
					field.addEventListener("keydown", function (e) {
						if (e.key === "Enter") {
							if (e.ctrlKey) {
								postTransaction();
								return;
							}
							if (this.value.trim() == "") {
								return;
							}

							if (field.tagName !== "TEXTAREA")
								e.preventDefault();

							let nextField = findNextInputField(this);
							if (nextField) {
								nextField.focus({ focusVisible: true , data: "Sex"});
								nextField.selectionStart = nextField.selectionEnd = nextField.value.length
							}

							return false;
						}
					});
				}
				if (!firstItemFocus) {
					firstItemFocus = field;
				}
			}
		}

		slo_input = $("#js-ref_form-main [data-slo]").not("#js-defines");
		slo_objects = slo_input.slo({
			onselect: function (e) {
			}, onkeydown: function (e) {
				if (this.stamped && e.key == "Enter") {
					if (e.ctrlKey) {
						postTransaction();
						return;
					}
					let nextField = findNextInputField(this.object);
					if (nextField) {
						nextField.focus({ focusVisible: true })
					}
				}
			}
		});
		description_field = document.getElementById('description');
		value_field = document.getElementById('value');


		$("[name=value]").on("input keydown keyup mousedown mouseup select contextmenu drop", function () {
			OnlyFloat(this, null, 0);
		});

		addwin = new Popup("appPopupAddBenif");
		document.getElementById("js-input_add-benif")?.addEventListener("click", () => {
			addwin.show();
		});

		document.getElementById("js-input_submit")?.addEventListener("click", () => {
			postTransaction();
		});

		form_main.addEventListener("submit", function (e) {
			e.preventDefault();
			postTransaction();
			return false;
		});

		slo_objects.getElementById("individual").slo.events.onselect = function (data) {
			slo_objects.getElementById("beneficiary").slo.set(data.key, data.value);
			$("#beneficiary").prop("readonly", true);
		}

		slo_objects.getElementById("target-account").slo.events.onselect = function (data) {
			console.log(data);
		}

		slo_objects.getElementById("individual").slo.events.ondeselect = function () {
			$("#beneficiary").prop("readonly", false);
		};

		$("#js-defines").slo({
			onselect: function (e) {
				const selected_option = document.querySelector("#defines option[data-id='" + e.key + "']");
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
		//firstItemFocus.focus()
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

			if (this.dataset.mandatory != undefined && this.slo.get().value.trim() == "") {
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
	clearFields = () => {
		slo_objects.getElementById("beneficiary").slo.clear(false);
		value_field.value = "";
		description_field.value = "";
	}
	postTransaction = async () => {
		if (busy)
			return;
		try {
			validateFields();
		} catch (e) {
			messagesys.failure(e);
			return false;
		}
		busy = true;
		try {
			const formData = new FormData(form_main);
			disableForm(true)
			let response = await fetch(form_main.action, {
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
				const payload = await response.json();
				busy = false;
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
			busy = false;
			messagesys.failure(error);
		}
	}

	initInvokers();

});