import { Navigator } from "../app.js";

export default class AccountStatmenet {
	constructor(uri) {
		const instance = this;
		this.uri = uri;
		this.export_uri = "";
		this.navigator = new Navigator({
			"page": "",
			"from": "",
			"to": "",
		}, this.uri);


		this.slo_date_start = $("#js-input_date-start").slo({
			onselect: function (e) { instance.navigator.setProperty("from", e.key); },
			ondeselect: function (e) { instance.navigator.setProperty("from", ""); }
		});
		this.slo_date_end = $("#js-input_date-end").slo({
			onselect: function (e) { instance.navigator.setProperty("to", e.key); },
			ondeselect: function (e) { instance.navigator.setProperty("to", ""); }
		});
		this.slo_page_current = $("#js-input_page-current").slo({
			onselect: function (e) {
				instance.navigator.setProperty("page", e.key);
				instance.navigator.pushState();
				instance.xhttp_request();
			}
		});

		this.navOutput = document.getElementById("navOutput");
		this.js_input_cmd_update = document.getElementById("js-input_cmd-update");
		this.navNext = document.getElementById("navNext");
		this.navPrev = document.getElementById("navPrev");
		this.navTotal = document.getElementById("navTotal");
		this.navPages = document.getElementById("navPages");
		this.js_output_statements_count = document.getElementById("js-output_statements-count");
		this.js_input_cmd_export = document.getElementById("js-input_cmd-export");
		this.js_form_export = $("#js-form_export");
		this.total_pages = 1;

		this.navigator.setProperty("from", this.slo_date_start.get()[0].id);
		this.navigator.setProperty("to", this.slo_date_end.get()[0].id);
		this.navigator.onPopState((e) => {
			try {
				this.slo_page_current.set(e.state.page, e.state.page);
				this.slo_date_start.set(e.state.from, e.state.from);
				this.slo_date_end.set(e.state.to, e.state.to);
			} catch (e) {
			}
			this.xhttp_request(true);
		});

		this.registerEvents();
	}

	register(data) {
		this.navigator.state = data;
	}

	run() {
		this.xhttp_request(false);
	}

	registerEvents() {
		this.js_input_cmd_export.addEventListener('click', () => {
			overlay.show();
			this.js_form_export.find("[name=page]").val(this.navigator.getProperty('page'));
			this.js_form_export.find("[name=from]").val(this.navigator.getProperty('from'));
			this.js_form_export.find("[name=to]").val(this.navigator.getProperty('to'));
			this.js_form_export.attr("method", "post");
			this.js_form_export.attr("action", this.export_uri + "/?");
			this.js_form_export.submit();
			setTimeout(() => {
				overlay.hide();
			}, 1000);
		});

		this.navPages.addEventListener("click", () => {
			this.navigator.state['page'] = 0;
			this.navigator.pushState();
			this.xhttp_request(true)
		});

		this.navNext.addEventListener("click", () => {
			if (parseInt(this.navigator.getProperty("page")) >= this.total_pages) { return; };
			this.navigator.state['page'] += 1;
			this.navigator.pushState();
			this.navPrev.disabled = false;
			this.slo_page_current.set(this.navigator.getProperty("page"), this.navigator.getProperty("page"));
			this.xhttp_request(true);
		});

		this.navPrev.addEventListener("click", () => {
			if (parseInt(this.navigator.getProperty("page")) <= 1) { return; };
			this.navigator.state['page'] -= 1;
			this.navigator.pushState();
			this.slo_page_current.set(this.navigator.getProperty("page"), this.navigator.getProperty("page"))
			this.xhttp_request(true);
		});

		this.js_input_cmd_update.addEventListener('click', () => {
			this.navigator.state['page'] = 0;
			this.navPrev.disabled = true;
			this.navigator.pushState();
			try {
				if (this.slo_page_current[0].slo.handler instanceof NumberHandler) {
					this.slo_page_current[0].slo.handler.rangeEnd(1);
				}
			} catch (e) { }
			this.xhttp_request(false);
		});
	}

	xhttp_request(isloaded = false) {
		this.navPrev.disabled = this.navigator.getProperty("page") == 1;
		this.navNext.disabled = parseInt(this.navigator.getProperty("page")) >= this.total_pages;

		$.ajax({
			data: { ...this.navigator.state, ...{ "method": "statement_report" } },
			url: this.uri,
			type: 'POST'
		}).done((output, textStatus, request) => {
			let response = request.getResponseHeader('VENDOR_RESULT');
			let fn_sum = request.getResponseHeader('VENDOR_FN_SUM');
			let fn_count = parseInt(request.getResponseHeader('VENDOR_FN_COUNT'));
			let fn_current = parseInt(parseInt(request.getResponseHeader('VENDOR_FN_CURRENT')));
			this.total_pages = parseInt(request.getResponseHeader('VENDOR_FN_PAGES'));
			if (response == 'DATE_CONFLICT') {
				messagesys.failure("Date range is not valid");
				this.navNext.disabled = true
				this.navPrev.disabled = true;
				this.slo_page_current.disable()
				return;
			}

			this.navigator.setProperty("page", fn_current);
			this.slo_page_current.set(fn_current, fn_current);
			try {
				if (this.slo_page_current[0].slo.handler instanceof NumberHandler) {
					this.slo_page_current[0].slo.handler.rangeEnd(parseInt(this.total_pages));
				}
			} catch (e) {
				this.slo_page_current.clear();
			}
			if (this.total_pages == 0) {
				this.navNext.disabled = true;
				this.navPrev.disabled = true;
				this.navPages.disabled = true;
				this.slo_page_current.disable();
			} else if (this.total_pages == 1) {
				this.navNext.disabled = true;
				this.navPrev.disabled = true;
				this.navPages.disabled = false;
				this.slo_page_current.disable();
			} else if (this.total_pages > 1) {
				this.slo_page_current.enable()
				if (this.navigator.getProperty("page") == 0) {
					this.navNext.disabled = true;
				} else if (this.navigator.getProperty("page") >= this.total_pages) {
					this.navNext.disabled = true;
				} else {
					this.navNext.disabled = false;
				}
				this.navPages.disabled = false;
			}

			this.js_output_statements_count.innerText = fn_count;
			this.navTotal.innerText = fn_sum;
			this.navPages.value = this.total_pages;
			this.navOutput.innerHTML = output;

			if (isloaded) {
				const y = this.navTotal.getBoundingClientRect().top;
				window.scroll({
					top: y - 25,
					behavior: 'smooth'
				});
			}
		}).always(function () {
		});
	};

}




