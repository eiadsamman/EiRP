import { Popup } from '../gui/popup.js';
import App from '../app.js';
//import MathEvaluator from '../math-evaluator.js';
class AppModule {
	id = null;
	pana = null;
	constructor() {

	}
	splashscreenTemplate(title) {
		return `
		<div class="gremium limit-width">
			<header style="position:sticky;">
				<a style="pointer-events: none;" class="previous" data-role="previous"></a>
				<h1>${title}</h1><cite></cite>
			</header>
			<menu class="btn-set">
				<span>&nbsp;</span>
			</menu>
			<h2>Statement details</h2>
			<article>
				<span class="loadingScreen-placeholderBody"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>
			</article>
		</div>`;
	}
	splashscreen(target, url, title, data) {
		target.innerHTML = this.splashscreenTemplate(title);
	}
}

export class Search extends AppModule {
	pana = null;
	postUrl = "";

	constructor(pana) {
		super();
		this.pana = pana;
		this.id = this.pana.navigator.url;
		this.searchFrom = null;
	}

	static get AcceptedFields() {
		return ["statement-id", "beneficiary", "description", "date-start", "date-end", "category"];
	}
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
			this.searchFrom.querySelector("[name=\"statement-id\"]").value = this.pana.navigator.state['statement-id'] ?? "";
			this.searchFrom.querySelector("[name=\"beneficiary\"]").value = this.pana.navigator.state.beneficiary ?? "";
			this.searchFrom.querySelector("[name=\"description\"]").value = this.pana.navigator.state.description ?? "";

			let dateStart = $(this.searchFrom.querySelector("[name=\"date-start\"]")).slo();
			let dateEnd = $(this.searchFrom.querySelector("[name=\"date-end\"]")).slo();
			let category = $(this.searchFrom.querySelector("[name=\"category\"]")).slo();
			let party = $(this.searchFrom.querySelector("[name=\"party\"]")).slo();

			if (this.pana.navigator.state['date-start']) {
				dateStart.set(this.pana.navigator.state['date-start'], this.pana.navigator.state['date-start'])
			}
			if (this.pana.navigator.state['date-end']) {
				dateEnd.set(this.pana.navigator.state['date-end'], this.pana.navigator.state['date-end'])
			}
			if (this.pana.navigator.state['category']) {
				category.set(this.pana.navigator.state['category'], "")
			}
			if (this.pana.navigator.state['party']) {
				category.set(this.pana.navigator.state['party'], "")
			}
		} catch (e) { }

	}

	post() {
		const data = {};
		var elements = this.searchFrom.elements;
		// TODO: SOME
		/**
		 * Needs a lot of enhancments
		 * convert names to object and arrays
		 */
		for (var i = 0, element; element = elements[i++];) {
			if ((element.type === "text" || element.type === "hidden" || element.type === "number") && element.name.slice(-3) != "[0]" && element.value.trim() !== "") {
				if (element.name.slice(-3) == "[1]") {
					data[element.name.slice(0, -3)] = element.value;
				} else {
					data[element.name] = element.value;
				}
			}
		}
		this.pana.navigator.state = data;
		this.pana.navigator.replaceState();
		this.pana.register(this.postUrl, data);
		this.pana.navigator.pushState();
		this.pana.run();
	}


	splashscreenTemplate(title) {
		return `
		<div class="gremium limit-width">
			<header style="position:sticky;">
				<a style="pointer-events: none;" class="previous" data-role="previous"></a>
				<h1>${title}</h1><cite></cite>
			</header>
			
			<h2>Search criteria</h2>
			<article>
				<span class="loadingScreen-placeholderBody"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>
			</article>
		</div>`;
	}
}

export class Ledger extends AppModule {
	pana = null;
	busy = false;
	totalPages = 1;
	currentPage = 1;
	totalPages = 0;
	recordsCount = 0;
	recordsSum = 0;
	latency = null;
	js_container_output = null;
	js_input_cmd_next = null;
	js_input_cmd_prev = null;
	js_output_total = null;
	js_output_page_total = null;


	constructor(pana) {
		super();
		this.currentPage = 1;
		this.totalPages = 1;
		this.pana = pana;
		this.id = this.pana.navigator.url;
	}

	onPopState() {
		this.currentPage = this.pana.navigator.state.page ? parseInt(this.pana.navigator.state.page) : 1;
		this.slo_page_current.set(this.currentPage, this.currentPage);
		this.fetch();
	}

	run() {
		this.slo_page_current = $("#js-input_page-current").slo({
			onselect: (e) => {
				this.currentPage = parseInt(e.key);
				this.currentPage = this.currentPage <= 0 ? 1 : this.currentPage;
				this.pana.navigator.setProperty("page", this.currentPage);
				this.pana.navigator.pushState();
				this.fetch();
			}
		});
		this.js_container_output = document.getElementById("js-container-output");
		this.js_input_cmd_next = document.getElementById("js-input_page-next");
		this.js_input_cmd_prev = document.getElementById("js-input_page-prev");
		this.js_output_total = document.getElementById("js-output-total");
		this.js_output_totalrecords = document.getElementById("js-output_total-records");
		this.js_output_page_total = document.getElementById("js-output_page-total");

		this.pana.clearActiveItem();
		this.currentPage = this.pana.navigator.state.page ? parseInt(this.pana.navigator.state.page) : 1;
		this.slo_page_current.set(this.currentPage, this.currentPage);

		this.js_output_page_total?.addEventListener("click", () => {
			if (this.totalPages > 0) {
				this.currentPage = parseInt(this.totalPages);
				this.pana.navigator.setProperty("page", this.currentPage);
				this.pana.navigator.pushState();
				this.fetch();
			}
		});

		this.js_input_cmd_next?.addEventListener("click", () => {
			if (this.currentPage >= this.total_pages) { return; };
			this.currentPage += 1;
			this.pana.navigator.setProperty("page", this.currentPage);
			this.pana.navigator.pushState();
			this.js_input_cmd_prev.disabled = false;
			this.slo_page_current.set(this.currentPage, this.currentPage);
			this.fetch()
		});


		this.js_input_cmd_prev?.addEventListener("click", () => {
			if (this.currentPage <= 1) { return; };
			this.currentPage -= 1;
			this.pana.navigator.setProperty("page", this.currentPage);
			this.pana.navigator.pushState();
			this.slo_page_current.set(this.currentPage, this.currentPage)
			this.fetch();
		});
		this.fetch();
	}

	splashscreen(target, url, title, data) {
		target.innerHTML = `
			<div class="gremium limit-width">
				<header style="position:sticky;">
				<h1><span class=\"small-media-hide\">${App.Account.term}: </span>${App.Account.name}</h1><cite>0.00</cite>
				</header>
				<menu class="btn-set">
					<button class="edge-right edge-left search"><span class="small-media-hide"> Search</span></button>
					<span class="small-media-hide flex"></span>
					<input type="button" class="pagination prev edge-left" disabled value="&#xe91a;" />
					<input type="text" placeholder="#" style="width:80px;text-align:center" value="0" />
					<input type="button" class="pagination next" disabled value="&#xe91d;" />
					<input type="button" class="edge-right" style="min-width:50px;text-align:center" value="0" />
				</menu>
				<article>
					
				</article>
			</div>`;
	}

	paginationUpdate(currentPage, totalPages, recordsCount, recordsSum) {
		if (currentPage && totalPages && recordsCount && recordsSum) {
			this.currentPage = parseInt(currentPage);
			this.totalPages = parseInt(totalPages);
			this.recordsCount = recordsCount;
			this.recordsSum = recordsSum;
			this.slo_page_current.set(this.currentPage, this.currentPage);

			try {
				if (this.slo_page_current[0].slo.handler instanceof NumberHandler) {
					this.slo_page_current[0].slo.handler.rangeEnd(parseInt(this.totalPages));
				}
			} catch (e) {
				this.slo_page_current.clear();
			}

			if (this.totalPages == 0) {
				this.js_input_cmd_next.disabled = true;
				this.js_input_cmd_prev.disabled = true;
				this.js_output_page_total.disabled = true;
				this.slo_page_current.disable();
			} else if (this.totalPages == 1) {
				this.js_input_cmd_next.disabled = true;
				this.js_input_cmd_prev.disabled = true;
				this.js_output_page_total.disabled = false;
				this.slo_page_current.disable();
			} else if (this.totalPages > 1) {
				this.slo_page_current.enable()
				if (this.pana.navigator.getProperty("page") == 0) {
					this.js_input_cmd_next.disabled = true;
				} else if (this.pana.navigator.getProperty("page") >= this.totalPages) {
					this.js_input_cmd_next.disabled = true;
				} else {
					this.js_input_cmd_next.disabled = false;
				}
				this.js_output_page_total.disabled = false;
			}

			this.js_output_total.innerText = this.recordsSum;
			this.js_output_totalrecords.innerText = App.Instance.numberFormat(parseInt(this.recordsCount), 0, "", ",") + " records";
			this.js_output_page_total.value = App.Instance.numberFormat(parseInt(this.totalPages), 0, "", ",");

			window.scroll({
				top: 0,
				behavior: 'smooth'
			});
		}
	}

	generatePlaceholders(count = 20, colspan = 1) {
		this.js_container_output.innerHTML = "";
		let tr = null;
		let td = null;
		for (let i = 0; i < count; i++) {
			tr = document.createElement("TR");
			td = document.createElement("TD");
			td.classList.add("placeholder");
			td.setAttribute("colspan", colspan);
			tr.appendChild(td);
			this.js_container_output.appendChild(tr);
		}
	}

	fetch() {
		this.latency = setTimeout(() => {
			this.generatePlaceholders(20, 6);
		}, 500);

		this.js_input_cmd_prev.disabled = this.currentPage == 1;
		this.js_input_cmd_next.disabled = parseInt(this.currentPage) >= this.totalPages;
		this.busy = true;
		fetch(this.pana.navigator.url, {
			method: "POST",
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			referrerPolicy: "no-referrer",
			headers: {
				"Accept": "text/plain, */*",
				"Content-type": "application/json; charset=UTF-8",
				"X-Requested-With": "fetch"
			},
			body: JSON.stringify({ ...this.pana.navigator.state, "objective": "list", "page": this.currentPage })
		}).then(response => {
			this.busy = false;
			if (this.latency) clearTimeout(this.latency);
			if (response.ok) {
				this.paginationUpdate(
					response.headers.get("Vendor-Ouput-Current"),
					response.headers.get("Vendor-Ouput-Pages"),
					response.headers.get("Vendor-Ouput-Count"),
					response.headers.get("Vendor-Ouput-Sum"),
				);

				/**
				 * Update search buttons
				 */
				let stringifyQurey = "";
				for (let element in this.pana.navigator.state) {
					if (Search.AcceptedFields.includes(element))
						stringifyQurey += (stringifyQurey == "" ? "" : "&") + element + "=" + this.pana.navigator.state[element]
				};

				let searchButton = document.getElementById("searchButton");
				let cancelSearchButton = document.getElementById("cancelSearchButton");
				if (searchButton) {
					searchButton.dataset.href = searchButton.dataset.target + "/?" + stringifyQurey;
				}
				cancelSearchButton.style.display = "none";
				if (cancelSearchButton && stringifyQurey != "") {
					cancelSearchButton.style.display = "block";
				}


				return response.text();

			}
			return Promise.reject(response);
		}).then(body => {
			if (this.recordsCount == 0) {
				this.js_container_output.innerHTML = "";
				let tr = document.createElement("TR");
				let td = document.createElement("TD");
				td.setAttribute("colspan", 6);
				td.innerText = "No statements found...";
				tr.appendChild(td);
				this.js_container_output.appendChild(tr);
			} else {
				this.js_container_output.innerHTML = body;
				this.pana.praseEvents(this.js_container_output);

			}
		});
	}
}

export class StatementView extends AppModule {
	pana = null;
	constructor(pana) {
		super();
		this.pana = pana;
		this.id = this.pana.navigator.url;
	}

	splashscreen(target, url, title, data) {
		target.innerHTML = this.splashscreenTemplate(title);
	}

	run() {
		if (this.pana.navigator.state.id) {
			const panelItem = document.querySelector('a[data-listitem_id="' + this.pana.navigator.state.id + '"]');
			if (panelItem) {
				this.pana.setActiveItem(panelItem);
				panelItem.scrollIntoView({
					behavior: "smooth",
					block: "nearest"
				});
			}
		}

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
	}
}

export class Transaction extends AppModule {
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

	splashscreen(target, url, title, data) {
		target.innerHTML = `
		<div class="gremium limit-width">
			<header style="position:sticky;">
				<a style="pointer-events: none;" class="previous" data-role="previous"></a>
				<h1>${title}</h1><cite></cite>
			</header>
			<h2>Transaction details</h2>
			<article>
				<span class="loadingScreen-placeholderBody"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>
			</article>
		</div>`
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

	forexSelectionHandler(data, editing, caller) {
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

	run() {
		let _instance = this;
		if (this.pana.navigator.state.id) {
			const panelItem = document.querySelector('a[data-listitem_id="' + this.pana.navigator.state.id + '"]');
			if (panelItem) {
				this.pana.setActiveItem(panelItem);
				panelItem.scrollIntoView({
					behavior: "smooth",
					block: "nearest"
				});
			}
		}

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
		this.slo_defines = $("#js-defines").slo({
			onselect: (e) => {
				this.predefinedSet(e.key)
			},
			ondeselect: function (e) {
				_instance.slo_objects.clear();
				_instance.clearFields();
				document.querySelectorAll(".predefined").forEach(element => {
					element.style.display = "flex";
				});
			}
		});

		if (this.pana.navigator.state['quick']) {
			let prelist = document.getElementById("defines");
			prelist.childNodes.forEach(e => {
				if (e.dataset && e.dataset.id && e.dataset.id == this.pana.navigator.state['quick']) {
					this.slo_defines.set(this.pana.navigator.state['quick'], e.innerText);
					this.predefinedSet(this.pana.navigator.state['quick']);
				}
			});
		}
		
		this.forexVendor();
	}


	predefinedSet(id) {
		const selected_option = document.querySelector(`#defines option[data-id='${id}']`);
		this.slo_objects.getElementById("target-account").slo.set(selected_option.dataset.account_bound, selected_option.dataset.account_bound);
		this.slo_objects.getElementById("category").slo.set(selected_option.dataset.category, selected_option.dataset.category);

		let currentDate = new Date();
		const offset = currentDate.getTimezoneOffset()
		currentDate = new Date(currentDate.getTime() - (offset * 60 * 1000))

		this.slo_objects.getElementById("post-date").slo.set(currentDate.toISOString().split('T')[0], currentDate.toISOString().split('T')[0]);
		document.querySelectorAll(".predefined").forEach(element => {
			element.style.display = "none";
		});
		this.slo_objects.getElementById("beneficiary").slo.focus();
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
		if (this.slo_objects) {
			this.slo_objects.getElementById("beneficiary").slo.clear(false);
			this.slo_objects.getElementById("individual").slo.clear(false);
			$("#beneficiary").prop("readonly", false);
			this.value_field.value = "";
			this.description_field.value = "";
		}
	}

	validateFields() {
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
			
			if (this.dataset.required != undefined && !this.slo.stamped || this.dataset.mandatory != undefined && this.slo.get().value.trim() == "") {
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
				this.busy = false;
				if (payload.result == true) {
					$("#issuer-account-balance").html(payload.balance);
					$("#jqroot_bal").html(payload.balance + " " + payload.currency);

					if (payload.type == "receipt" || payload.type == "payment") {
						messagesys.success("Transaction `" + payload.insert_id + "` posted successfully");

						this.clearFields();
						this.uploadController.clean();
						this.slo_objects.getElementById("beneficiary").slo.focus();

						this.pana.prependItem({
							"attachements": formData.getAll("attachments[]").length,
							"beneficial": formData.get("beneficiary[0]"),
							"category": formData.get("category[0]"),
							"date": formData.get("date[0]"),
							"details": formData.get("description"),
							"id": payload.insert_id,
							"positive": payload.type == "receipt",
							"value": (payload.type == "payment" ? "(" : "") + App.Instance.numberFormat(formData.get("value"), 2) + (payload.type == "payment" ? ")" : ""),
							"padge_id": App.User.photo,
							"padge_initials": App.User.initials,
							"padge_color": App.Instance.userColorCode(App.User.id),
						});
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
