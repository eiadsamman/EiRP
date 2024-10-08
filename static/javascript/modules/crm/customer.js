import { Popup } from '../gui/popup.js';
import { default as App, View, Search, List } from '../app.js';

//import MathEvaluator from '../math-evaluator.js';

export class Entry extends View {
	pana = null;
	formMain = null;
	constructor(pana) {
		super();
		this.pana = pana;
		this.id = this.pana.navigator.url;
	}

	splashscreen(target, url, title, data) {
		return `
		<div class="gremium"><div class="content">
			<header style="position:sticky;">
				<h1>${title}</h1><cite></cite>
			</header>
			<menu class="btn-set">
				<span>&nbsp;</span>
			</menu>
			<h2>The list of companies</h2>
			<article>
				<span class="loadingScreen-placeholderBody"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>
			</article>
		</div></div>`;
	}

	run() {
		this.frmType = $("#tlType").slo();
		this.frmRemindDate = $("#remindDate").slo();
		this.frmMessage = document.getElementById("message");

		this.formMain = document.getElementById("newAactionForm");
		if (this.formMain) {
			this.formMain.addEventListener("submit", (e) => {
				e.preventDefault();
				this.post();

				return false;
			});
		}

		this.uploadController = $.Upload({
			objectHandler: $("#js_upload_list"),
			domselector: $("#js_uploader_btn"),
			dombutton: $("#js_upload_trigger"),
			list_button: $("#js_upload_count"),
			emptymessage: "[No files uploaded]",
			delete_method: 'permanent',
			upload_url: 'upload',
			relatedpagefile: 268,
			multiple: true,
			inputname: "attachments",
			domhandler: $("#UploadDOMHandler"),
			popupview: new Popup()
		});

		this.uploadController.update();

		document.getElementById("attachementsList")?.childNodes.forEach(elm => {
			elm.addEventListener("click", function (e) {
				if (this.dataset.attachment && this.dataset.attachment == "force") {
					/* default link behaviour */
				} else {
					e.preventDefault();
					let popAtt = new Popup();
					popAtt.addEventListener("close", function (p) {
						this.destroy();
					});
					popAtt.contentForm({ title: "Attachement preview" }, "<div style=\"text-align: center;\"><img style=\"max-width:600px;width:100%\" src=\"" + elm.href + "\" /></div>");
					popAtt.show();
				}
			});
		});

		let caller = document.getElementById("pana-Window").querySelector("[data-crmlistItem=\"" + this.pana.navigator.state.id + "\"]");
		if (caller) {
			let badge = caller.querySelector(".badge")
			if (badge) {
				setTimeout(() => {
					badge.classList.add("hide");
				}, 1000);
			}
		}

	}

	validate(form) {
		let result = true;
		document.querySelectorAll("h1.required").forEach(e => {
			e.classList.remove("required");
		});

		form.forEach((v, k) => {
			if (k == "message" && "" === v.trim()) {
				document.getElementById("for-message").classList.add("required")
				result = false;
			}
			if (k == "action[1]" && isNaN(parseInt(v))) {
				document.getElementById("for-action").classList.add("required")
				result = false;
			}
		});

		return result;
	}

	async post() {
		try {
			const formData = new FormData(this.formMain);
			if (!this.validate(formData)) {
				return;
			}


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

			if (response.ok) {
				const payload = await response.json();
				if (payload.result == true) {
					let tlCont = document.getElementById("timelineContainer");
					let tlNew = document.createElement("div");

					this.frmMessage.value = ""
					this.frmType.clear();
					this.frmRemindDate.clear();
					this.uploadController.clean();

					tlNew.innerHTML = `
						<span>${payload.timestamp}</span>
						<h1>${payload.action}</h1>
						<div>
							${formData.get("message")}
						</div>
						<cite>${payload.issuer}</cite>
					`;
					tlNew.classList.add("flash");
					tlCont.prepend(tlNew);

				} else {
					messagesys.failure(payload.error);
				}
			}
		} catch (error) {
			messagesys.failure(error);
		}
	}
}


export class CustomSearch extends Search {
	run() {
		this.searchFrom = document.getElementById("searchForm");
		this.postUrl = this.searchFrom.getAttribute("action");
		this.searchFrom.addEventListener("submit", (e) => {
			e.preventDefault();
			this.post();
			return false;
		});
		document.getElementById("js-input_submit")?.addEventListener("click", () => {
			this.post();
		});

		try {
			let company = $(this.searchFrom.querySelector("[name=\"company\"]")).slo();
			if (this.pana.navigator.state['company']) {
				company.set(this.pana.navigator.state['company'], "")
			}
		} catch (e) { }
	}
}

export class CustomList extends List {
	searchFields = ["company"];

	splashscreen(target, url, title, data) {
		target.innerHTML = `
			<div class="gremium"><div class="content">
				<header style="position:sticky;">
				<h1>${title}</h1>
				</header>
				<menu class="btn-set">
					<button id="searchButton" class="edge-left search"><span class="small-media-hide"> Search</span></button>
					<span class="small-media-hide flex"></span>
					<input type="button" class="pagination prev edge-left" disabled value="&#xe91a;" />
					<input type="text" placeholder="#" style="width:80px;text-align:center" value="0" />
					<input type="button" class="pagination next" disabled value="&#xe91d;" />
					<input type="button" class="edge-right" style="min-width:50px;text-align:center" value="0" />
				</menu>
				<article>
				</article>
			</div></div>`;
	}
}


export class Post extends View {
	splashscreen(target, url, title, data) {
		target.innerHTML = `
		<div class="gremium"><div class="content">
			<header style="position:sticky;">
				<a style="pointer-events: none;" class="previous" data-role="previous"></a>
				<h1>${title}</h1><cite></cite>
			</header>
			<article>
				<span class="loadingScreen-placeholderBody"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>
			</article>
		</div></div>`
	}


	busy = null;
	addwin = null;
	formMain = null;
	inputFields = null;
	slo_input = null;
	slo_objects = null;
	value_field = null;
	uploadController = null;
	inputFieldsSorted = null;
	slo_defines = null;
	description_field = null;
	pana = null;
	exchangeObjects = {}

	constructor(pana) {
		super();
		this.pana = pana;
		this.id = this.pana.navigator.url;
	}



	run() {
		let _instance = this;

		/* Main application form */
		this.formMain = document.getElementById("js-ref_form-main");


		this.uploadController = $.Upload({
			objectHandler: $("#js_upload_list"),
			domselector: $("#js_uploader_btn"),
			dombutton: $("#js_upload_trigger"),
			list_button: $("#js_upload_count"),
			emptymessage: "[No files uploaded]",
			delete_method: 'permanent',
			upload_url: 'upload',
			relatedpagefile: 188,
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
							if (field.tagName !== "TEXTAREA") {
								e.preventDefault();

							} else {
								return
							}

							let nextField = _instance.findNextInputField(this);
							if (nextField) {
								nextField.focus({ focusVisible: true, data: "" });
								if (nextField.selectionStart)
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


	}


	findNextInputField(target) {
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

	disableForm(state) {
		if (document.getElementById("js-input_submit"))
			document.getElementById("js-input_submit").disabled = state;
		this.formMain.querySelectorAll("input, textarea, button").forEach(function (elm) {
			if (elm != undefined)
				elm.disabled = state;
		});
	}

	clearFields() {

	}

	validateFields() {
		let thrownerror = {
			occured: false,
			message: "",
			object: undefined
		};
		return true;

		for (const reqmark of document.querySelectorAll("label>h1")) {
			reqmark.classList.remove("required");
		}

		/* jQuery Loop */
		this.slo_objects.each(function () {
			if (this.dataset.required != undefined && !this.slo.stamped || this.dataset.mandatory != undefined && this.slo.get().value.trim() == "") {
				this.slo.object.parentNode.parentNode.parentNode.querySelector("h1").classList.add("required");
				if (!thrownerror.occured) {
					thrownerror.occured = true;
					thrownerror.message = this.getAttribute("title") + " is required";
					thrownerror.object = this;
				}
			}

		});

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

	async post() {
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
			overlay.show();
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
			overlay.hide();
			this.disableForm(false);

			if (response.ok) {
				const payload = await response.json();
				console.log(payload)
				this.busy = false;
				if (payload.result == true) {
					messagesys.success("Customer added successfully");

					this.pana.navigator.state = {};
					this.pana.navigator.url = "crm/customers/view";
					this.pana.navigator.state['id'] = payload.insert_id;
					this.pana.navigator.pushState();
					this.pana.run();

					App.Instance.pageDir = this.pana.navigator.url;

				} else {
					messagesys.failure(payload.error);
				}
			}
		} catch (error) {
			this.busy = false;
			console.log(error)
			messagesys.failure(error);
		}
	}
}