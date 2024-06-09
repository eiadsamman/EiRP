import { Popup } from '../gui/popup.js';
import App from '../app.js';
//import MathEvaluator from '../math-evaluator.js';


export default class Transaction {
	busy = null;
	addwin = null;
	formMain = null;
	inputFields = null;
	slo_input = null;
	slo_objects = null;
	value_field = null;
	uploadController = null;
	inputFieldsSorted = null;
	description_field = null;
	panelNavigator = null;
	exchangeObjects = {}
	constructor(panelNavigator) {
		this.panelNavigator = panelNavigator;
	}

	forexFieldState(state) {
		if (state) {
			this.exchangeObjects.form.style.display = "flex";
		} else {
			this.exchangeObjects.form.style.display = "none";
			this.exchangeObjects.value.value = "";
			this.exchangeObjects.action.innerText = "";
			this.exchangeObjects.hint.innerText = "";
			this.exchangeObjects.field_from.value = "";
			this.exchangeObjects.field_to.value = "";
			this.exchangeObjects.overridden = false;
			this.exchangeObjects.field_override.value = "false";
		}
	}

	forexSelectionHandler = function (data, editing, caller) {
		let againstAccount = false;
		let statementType = null;
		let sourceAccount = this.slo_objects.getElementById("source-account");
		let targetAccount = this.slo_objects.getElementById("target-account");

		let account = App.Instance.assosiatedAccounts.find(el => {
			return (el.id == parseInt(data) ? el : false);
		});
		this.exchangeObjects.value.value = "";
		this.exchangeObjects.action.innerText = "";
		this.exchangeObjects.field_from.value = "";
		this.exchangeObjects.field_to.value = "";
		if (editing) {
			statementType = parseInt(this.slo_objects.getElementById("statement-nature").slo.htmlhidden[0].value);
			let statementNature = this.slo_objects.getElementById("statement-nature").slo.htmlhidden[0].value;
			if (caller == "target") {
				againstAccount = App.Instance.assosiatedAccounts.find(el => {
					return (el.id == parseInt(sourceAccount.slo.htmlhidden[0].value) ? el : false);
				});
				if (account && statementNature == 1) {
					this.exchangeObjects.currency_hint.innerHTML = account.currency.shortname;
				}
			} else {
				againstAccount = App.Instance.assosiatedAccounts.find(el => {
					return (el.id == parseInt(targetAccount.slo.htmlhidden[0].value) ? el : false);
				});
				if (account && statementNature == 2) {
					this.exchangeObjects.currency_hint.innerHTML = account.currency.shortname;
				}
			}
		} else {
			statementType = parseInt(document.getElementById("statement-nature").value);
			againstAccount = App.Account;
		}
		if (isNaN(statementType)) return;
		if (account && againstAccount) {
			if (account.id == parseInt(data)) {
				let forex = null;
				if (statementType == 1) {
					/* Receipt : Forex Buy */
					forex = App.Instance.forex.buyingRates(againstAccount.currency.id, account.currency.id);
				} else if (statementType == 2) {
					/* Payment : Forex Sell */
					forex = App.Instance.forex.sellingRates(againstAccount.currency.id, account.currency.id);
				}
				if (againstAccount.currency.id == account.currency.id) {
					this.forexFieldState(false);
				} else if (forex) {
					this.forexFieldState(true);
					this.exchangeObjects.hint.classList.remove("highlight");
					if (forex[0] > forex[1]) {
						this.exchangeObjects.field_from.value = againstAccount.currency.id;
						this.exchangeObjects.field_to.value = account.currency.id;
						this.exchangeObjects.value.value = forex[0] / forex[1];
						this.exchangeObjects.value.dataset.default = forex[0] / forex[1];
						this.exchangeObjects.hint.innerText = App.Instance.numberFormat(forex[0] / forex[1], 4);
						this.exchangeObjects.action.innerText = againstAccount.currency.shortname + " → " + account.currency.shortname;
					} else {
						this.exchangeObjects.field_from.value = account.currency.id;
						this.exchangeObjects.field_to.value = againstAccount.currency.id;
						this.exchangeObjects.value.value = forex[1] / forex[0];
						this.exchangeObjects.value.dataset.default = forex[1] / forex[0];
						this.exchangeObjects.hint.innerText = App.Instance.numberFormat(forex[1] / forex[0], 4);
						this.exchangeObjects.action.innerText = account.currency.shortname + " → " + againstAccount.currency.shortname;
					}
				}
			}
		} else {
			messagesys.failure("Loading forex information failed, resign to your account and try again");
		}
	}

	forexEventSubmit(skip = false) {
		this.exchangeObjects.hint.style.display = "flex";
		this.exchangeObjects.value.style.display = "none";
		if (skip) {
			return true;
		}
		if (isNaN(parseFloat(this.exchangeObjects.value.value))) {
			this.exchangeObjects.overridden = false;
			this.exchangeObjects.hint.innerText = this.exchangeObjects.value.dataset.default;
			this.exchangeObjects.value.value = this.exchangeObjects.value.dataset.default;
			this.exchangeObjects.field_override.value = "false";
			this.exchangeObjects.hint.classList.remove("highlight");
		} else {
			this.exchangeObjects.overridden = true;
			this.exchangeObjects.hint.classList.add("highlight");
			this.exchangeObjects.value.value = parseFloat(this.exchangeObjects.value.value);
			this.exchangeObjects.hint.innerText = App.Instance.numberFormat(this.exchangeObjects.value.value, 4);
			this.exchangeObjects.field_override.value = "true";
			this.value_field.focus();
			this.value_field.select();
		}
	}

	forexVendor() {
		let sourceAccount = this.slo_objects.getElementById("source-account");
		let targetAccount = this.slo_objects.getElementById("target-account");
		this.exchangeObjects.form = document.getElementById("exchange-form");
		this.exchangeObjects.action = document.getElementById("exchange-action");
		this.exchangeObjects.hint = document.getElementById("exchange-hint");
		this.exchangeObjects.value = document.getElementById("exchange-value");
		this.exchangeObjects.field_override = document.getElementById("exchange-override");
		this.exchangeObjects.field_from = document.getElementById("exchange-dir-from");
		this.exchangeObjects.field_to = document.getElementById("exchange-dir-to");
		this.exchangeObjects.currency_hint = document.getElementById("currency-hint");
		this.exchangeObjects.overridden = false;

		for (let key in this.exchangeObjects) {
			if (this.exchangeObjects[key] === undefined || this.exchangeObjects[key] === null) {
				return false;
			}
		};

		this.exchangeObjects.currency_hint.addEventListener('animationend', function (e) {
			e.target.classList.remove("flash");
		});
		this.exchangeObjects.action.addEventListener("click", e => {
			if (e.target.tagName !== "A") return;
			e.preventDefault();
			this.exchangeObjects.hint.style.display = "none";
			this.exchangeObjects.value.style.display = "flex";
			this.exchangeObjects.value.focus();
			this.exchangeObjects.value.select();
			return false;
		});
		this.exchangeObjects.value.addEventListener("keydown", e => {
			if (e.key == "Enter") {
				this.forexEventSubmit()
			}
			if (e.key == "Escape") {
				this.value_field.focus();
				this.value_field.select();
			}
		})
		this.exchangeObjects.value.addEventListener("blur", e => {
			this.forexEventSubmit(true)
		});

		/* Handle editing form account fields  */
		if (document.getElementById("statement-id")) {
			let statementNature = this.slo_objects.getElementById("statement-nature").slo.htmlhidden[0];

			this.slo_objects.getElementById("statement-nature").slo.events.onselect = (data) => {
				this.forexSelectionHandler(targetAccount.slo.htmlhidden[0].value, true, "target");
				this.forexSelectionHandler(sourceAccount.slo.htmlhidden[0].value, true, "source");
			};
			this.slo_objects.getElementById("statement-nature").slo.events.ondeselect = (data) => {
				this.exchangeObjects.currency_hint.innerHTML = "";
			}

			targetAccount.slo.events.onselect = (data) => {
				this.forexFieldState(false);
				this.forexSelectionHandler(data.key, true, "target");
			}
			targetAccount.slo.events.ondeselect = () => {
				this.forexFieldState(false);
				if (parseInt(statementNature.value) == 1)
					this.exchangeObjects.currency_hint.innerHTML = "";
			};

			sourceAccount.slo.events.onselect = (data) => {
				this.forexFieldState(false);
				this.forexSelectionHandler(data.key, true, "source");
			}
			sourceAccount.slo.events.ondeselect = () => {
				this.forexFieldState(false);
				if (parseInt(statementNature.value) == 2)
					this.exchangeObjects.currency_hint.innerHTML = "";
			};

		} else {
			targetAccount.slo.events.onselect = (data) => {
				this.forexSelectionHandler(data.key, false, "target");
			}
			targetAccount.slo.events.ondeselect = () => { this.forexFieldState(false); };
		}
	}

	run = function () {
		let _instance = this;
		document.getElementById("js-input_edit")?.addEventListener("click", () => {
		});

		/* Printing function */
		document.getElementById("js-input_print")?.addEventListener("click", function () {
			const objPrintFrame = window.frames['plot-iframe'];
			objPrintFrame.location = this.dataset.ploturl + "/?id=" + this.dataset.key;
			overlay.show(true);
			document.getElementById("plot-iframe").onload = function () {
				objPrintFrame.focus();
				setTimeout(() => { overlay.hide(); objPrintFrame.print(); }, 100);
			}
		});

		/* Open statement attachment popup */
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

		/* Main application form */
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

		/* Get application form input field sorted by tabIndex value */
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

		/* Run SLO object excluding pre defined operation field */
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

		this.slo_objects.getElementById("individual").slo.events.onselect = (data) => {
			this.slo_objects.getElementById("beneficiary").slo.set(data.key, data.value);
			$("#beneficiary").prop("readonly", true);
			/* this.value_field.focus(); */
		}

		this.slo_objects.getElementById("individual").slo.events.ondeselect = () => {
			$("#beneficiary").prop("readonly", false);
			this.slo_objects.getElementById("beneficiary").slo.clear();
		};

		$("#js-defines").slo({
			onselect: function (e) {
				const selected_option = document.querySelector("#defines option[data-id='" + e.key + "']");
				_instance.slo_objects.getElementById("target-account").slo.set(selected_option.dataset.account_bound, selected_option.dataset.account_bound);
				_instance.slo_objects.getElementById("category").slo.set(selected_option.dataset.category, selected_option.dataset.category);

				let currentDate = new Date();
				const offset = currentDate.getTimezoneOffset()
				currentDate = new Date(currentDate.getTime() - (offset * 60 * 1000))

				_instance.slo_objects.getElementById("post-date").slo.set(currentDate.toISOString().split('T')[0], currentDate.toISOString().split('T')[0]);
				document.querySelectorAll(".predefined").forEach(element => {
					element.style.display = "none";
				});
				_instance.slo_objects.getElementById("beneficiary").slo.focus();
			},
			ondeselect: function (e) {
				_instance.slo_objects.clear();
				_instance.clearFields();
				document.querySelectorAll(".predefined").forEach(element => {
					element.style.display = "flex";
				});
			}
		});
		//firstItemFocus.focus()
		this.forexVendor();
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
			this.slo_objects.getElementById("individual").slo.clear(false);
			$("#beneficiary").prop("readonly", false);
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

		if (this.exchangeObjects.overridden && isNaN(parseFloat(this.exchangeObjects.value.value))) {
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
		try {
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
				if (payload.result == true) {
					$("#issuer-account-balance").html(payload.balance);
					$("#jqroot_bal").html(payload.balance + " " + payload.currency);

					if (payload.type == "receipt" || payload.type == "payment") {
						messagesys.success("Transaction `" + payload.insert_id + "` posted successfully");
						$("#jQoutput").prepend(
							"<tr>" +
							"<td>" + payload.insert_id + "</td>" +
							"<td align=\"right\">" + App.Instance.numberFormat(formData.get("value"), 2) + "</td>" +
							"<td>" + formData.get("beneficiary[0]") + "</td>" +
							"</tr>"
						);
						this.clearFields();
						this.uploadController.clean();
						this.slo_objects.getElementById("beneficiary").slo.focus();

						this.panelNavigator.prependItem({
							"attachements": formData.getAll("attachments[]").length,
							"beneficial": formData.get("beneficiary[0]"),
							"category": formData.get("category[0]"),
							"date": formData.get("date[0]"),
							"details": formData.get("description"),
							"id": payload.insert_id,
							"positive": payload.type == "receipt",
							"value": (payload.type == "payment" ? "(" : "") + App.Instance.numberFormat(formData.get("value"), 2) + (payload.type == "payment" ? ")" : "")
						})
					} else if (payload.type == "update") {
						messagesys.success("Transaction `" + payload.insert_id + "` modified successfully");
					}

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
			this.busy = false;
			messagesys.failure(error);
		}
	}
}
