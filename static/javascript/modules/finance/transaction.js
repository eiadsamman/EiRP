
import { Popup } from '../gui/popup.js';

export default class Transaction {
	formMain = null;
	inputFields = null;
	slo_input = null;
	inputFieldsSorted = null;
	slo_objects = null;
	value_field = null;
	description_field = null;
	busy = null;

	uploadController = null;
	addwin = null;
	constructor() {

	}

	run = function () {
		let _instance = this;
		document.getElementById("js-input_edit")?.addEventListener("click", () => {
		});

		document.getElementById("js-input_print")?.addEventListener("click", function () {
			const objPrintFrame = window.frames['plot-iframe'];
			objPrintFrame.location = this.dataset.ploturl + "/?id=" + this.dataset.key;
			overlay.show();
			document.getElementById("plot-iframe").onload = function () {
				overlay.hide();
				objPrintFrame.focus();
				objPrintFrame.print();
			}
		});

		document.getElementById("transAttachementsList")?.childNodes.forEach(elm => {
			elm.addEventListener("click", (e) => {
				e.preventDefault();
				let popAtt = new Popup();
				popAtt.addEventListener("close", function (p) {
					this.destroy();
				});
				popAtt.contentForm({ title: "Attachement preview" }, "<div style=\"text-align: center;\"><img style=\"max-width:600px;width:100%\" src=\"" + elm.href + "\" /></div>");
				popAtt.show();
			});
		});

		this.formMain = document.getElementById("js-ref_form-main");

		if (this.formMain == null) {
			return;
		}

		this.uploadController = $.Upload({
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
			popupview: new Popup()
		});

		this.uploadController.update();

		this.inputFields = this.formMain.querySelectorAll("input,textarea");
		this.inputFieldsSorted = new Array();
		let firstItemFocus = null;
		for (const field of this.inputFields) {
			if (field.tabIndex && field.tabIndex > 0) {
				this.inputFieldsSorted[parseInt(field.tabIndex)] = field;
				/** Ordinary input field */
				if (field.dataset.slo == undefined) {
					field.addEventListener("keydown", function (e) {
						if (e.key === "Enter") {
							if (e.ctrlKey) {
								_instance.post();
								return;
							}
							if (this.value.trim() == "") {
								return;
							}

							if (field.tagName !== "TEXTAREA")
								e.preventDefault();

							let nextField = _instance.findNextInputField(this);
							if (nextField) {
								nextField.focus({ focusVisible: true, data: "Sex" });
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

		this.slo_input = $("#js-ref_form-main [data-slo]").not("#js-defines");
		this.slo_objects = this.slo_input.slo({
			onselect: function (e) {
			}, onkeydown: function (e) {
				if (this.stamped && e.key == "Enter") {
					if (e.ctrlKey) {
						_instance.post();
						return;
					}
					let nextField = _instance.findNextInputField(this.object);
					if (nextField) {
						nextField.focus({ focusVisible: true })
					}
				}
			}
		});
		this.description_field = document.getElementById('description');
		this.value_field = document.getElementById('value');


		$("[name=value]").on("input keydown keyup mousedown mouseup select contextmenu drop", function () {
			OnlyFloat(this, null, 0);
		});

		/* this.addwin = new Popup("appPopupAddBenif");
		document.getElementById("js-input_add-benif")?.addEventListener("click", () => {
			this.addwin.show();
		}); */

		document.getElementById("js-input_submit")?.addEventListener("click", () => {
			this.post();
		});

		this.formMain.addEventListener("submit", (e) => {
			e.preventDefault();
			this.post();
			return false;
		});

		this.slo_objects.getElementById("individual").slo.events.onselect = function (data) {
			this.slo_objects.getElementById("beneficiary").slo.set(data.key, data.value);
			$("#beneficiary").prop("readonly", true);
		}

		this.slo_objects.getElementById("target-account").slo.events.onselect = function (data) {
			console.log(data);
		}

		this.slo_objects.getElementById("individual").slo.events.ondeselect = function () {
			$("#beneficiary").prop("readonly", false);
		};

		$("#js-defines").slo({
			onselect: function (e) {
				const selected_option = document.querySelector("#defines option[data-id='" + e.key + "']");
				this.slo_objects.getElementById("target-account").slo.set(selected_option.dataset.account_bound, selected_option.dataset.account_bound);
				this.slo_objects.getElementById("category").slo.set(selected_option.dataset.category, selected_option.dataset.category);
				document.querySelectorAll(".predefined").forEach(element => {
					element.style.display = "none";
				});
				this.slo_objects.getElementById("beneficiary").slo.focus();
			},
			ondeselect: function (e) {
				this.slo_objects.clear();
				clearFields();
				document.querySelectorAll(".predefined").forEach(element => {
					element.style.display = "flex";
				});
			}
		});
		//firstItemFocus.focus()
	}

	findNextInputField = function (target) {
		let located = false;
		for (let i in this.inputFieldsSorted) {
			if (located) {
				return this.inputFieldsSorted[i];
			}
			if (this.inputFieldsSorted[i] == target) {
				located = true;
			}
		}
		return false;
	}
	disableForm = function (state) {
		if (document.getElementById("js-input_submit"))
			document.getElementById("js-input_submit").disabled = state;
		this.formMain.querySelectorAll("input, textarea, button").forEach(function (elm) {
			if (elm != undefined)
				elm.disabled = state;
		});
	}
	clearFields = function () {
		if (this.slo_objects) {
			this.slo_objects.getElementById("beneficiary").slo.clear(false);
			this.value_field.value = "";
			this.description_field.value = "";
		}
	}
	validateFields = function () {
		let thrownerror = {
			occured: false,
			message: "",
			object: undefined
		};

		for (const reqmark of document.querySelectorAll("label>h1")) {
			reqmark.classList.remove("required");
		}

		/* jQuery Loop */
		this.slo_objects.each(function () {
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

		if (isNaN(parseFloat(this.value_field.value))) {
			this.value_field.parentNode.parentNode.querySelector("h1").classList.add("required");
			if (!thrownerror.occured) {
				thrownerror.occured = true;
				thrownerror.message = "Invalid statement value";
				thrownerror.object = this.value_field;
			}
		}
		if ((this.description_field.value).trim() == "") {
			this.description_field.parentNode.parentNode.querySelector("h1").classList.add("required");
			if (!thrownerror.occured) {
				thrownerror.occured = true;
				thrownerror.message = "Provide a description for the statment";
				thrownerror.object = this.description_field;
			}
		}
		if (thrownerror.occured) {
			thrownerror.object.focus();
			throw (thrownerror.message);
		}
	}


	post = async function () {
		if (this.busy)
			return;
		try {
			this.validateFields();
		} catch (e) {
			messagesys.failure(e);
			return false;
		}
		this.busy = true;

		// try {
		const formData = new FormData(this.formMain);
		this.disableForm(true)
		let response = await fetch(this.formMain.action, {
			method: 'POST',
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			referrerPolicy: "no-referrer",
			headers: {
				"Application-From": "same",
				"X-Requested-With": "fetch",
			},
			body: formData,
		});
		this.disableForm(false);

		if (response.ok) {
			const payload = await response.json();
			this.busy = false;

			console.log(payload);

			return
			if (payload.result == true) {
				messagesys.success("Transaction `" + payload.insert_id + "` posted successfully");
				$("#jQoutput").prepend(
					"<tr>" +
					"<td>" + payload.insert_id + "</td>" +
					"<td align=\"right\">" + this.value_field.value + "</td>" +
					"<td>" + this.slo_objects.getElementById("beneficiary").slo.get()['value'] + "</td>" +
					"</tr>"
				);
				this.clearFields();
				this.uploadController.clean();
				this.slo_objects.getElementById("beneficiary").slo.focus();
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
		// } catch (error) {
		// 	this.busy = false;
		// 	messagesys.failure(error);
		// }
	}
}
