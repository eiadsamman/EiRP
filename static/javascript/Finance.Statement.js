$(document).ready(function (e) {
	let slo_date_start = $("#js-input_date-start").slo({
		onselect: function (e) { nav.setProperty("from", e.hidden); },
		ondeselect: function (e) { nav.setProperty("from", ""); }
	});
	let slo_date_end = $("#js-input_date-end").slo({
		onselect: function (e) { nav.setProperty("to", e.hidden); },
		ondeselect: function (e) { nav.setProperty("to", ""); }
	});
	let slo_page_current = $("#js-input_page-current").slo({
		onselect: function (e) {
			nav.setProperty("page", e.hidden);
			nav.pushState();
			xhttp_request(nav);
		}
	});
	let js_container_output = $("#js-container-output");
	let js_input_cmd_update = $("#js-input_cmd-update");
	let js_input_cmd_next = $("#js-input_page-next");
	let js_input_cmd_prev = $("#js-input_page-prev");
	let js_output_total = $("#js-output-total");
	let js_output_page_total = $("#js-output_page-total");
	let js_output_statements_count = $("#js-output_statements-count");
	let js_input_cmd_export = $("#js-input_cmd-export");
	let js_form_export = $("#js-form_export");
	let total_pages = 1;

	nav.setProperty("from", slo_date_start.get()[0].id);
	nav.setProperty("to", slo_date_end.get()[0].id);

	xhttp_request = function (nav, isloaded = false) {
		js_input_cmd_prev.attr("disabled", nav.getProperty("page") == 1);
		js_input_cmd_next.attr("disabled", parseInt(nav.getProperty("page")) >= total_pages);
		overlay.show();
		$.ajax({
			data: { ...nav.history_state, ...{ "method": "statement_report" } },
			url: drainurl,
			type: 'POST'
		}).done(function (output, textStatus, request) {
			let response = request.getResponseHeader('VENDOR_RESULT');
			let fn_sum = request.getResponseHeader('VENDOR_FN_SUM');
			let fn_count = parseInt(request.getResponseHeader('VENDOR_FN_COUNT'));
			let fn_current = parseInt(parseInt(request.getResponseHeader('VENDOR_FN_CURRENT')));
			total_pages = parseInt(request.getResponseHeader('VENDOR_FN_PAGES'));
			if (response == 'DATE_CONFLICT') {
				messagesys.failure("Date range is not valid");
				js_input_cmd_next.attr("disabled", true);
				js_input_cmd_prev.attr("disabled", true);
				slo_page_current.disable()
				return;
			}

			nav.setProperty("page", fn_current);
			slo_page_current.set(fn_current, fn_current);
			try {
				if (slo_page_current[0].slo.handler instanceof NumberHandler) {
					slo_page_current[0].slo.handler.rangeEnd(parseInt(total_pages));
				}
			} catch (e) {
				slo_page_current.clear();
			}
			if (total_pages == 0) {
				js_input_cmd_next.attr("disabled", true);
				js_input_cmd_prev.attr("disabled", true);
				js_output_page_total.attr("disabled", true);
				slo_page_current.disable();
			} else if (total_pages == 1) {
				js_input_cmd_next.attr("disabled", true);
				js_input_cmd_prev.attr("disabled", true);
				js_output_page_total.attr("disabled", false);
				slo_page_current.disable();
			} else if (total_pages > 1) {
				slo_page_current.enable()
				if (nav.getProperty("page") == 0) {
					js_input_cmd_next.attr("disabled", true);
				} else if (nav.getProperty("page") >= total_pages) {
					js_input_cmd_next.attr("disabled", true);
				} else {
					js_input_cmd_next.attr("disabled", false);
				}
				js_output_page_total.attr("disabled", false);
			}

			js_output_statements_count.html(fn_count);
			js_output_total.html(fn_sum);
			js_output_page_total.val(total_pages);
			js_container_output.html(output);

			if (isloaded) {
				const y = js_output_total[0].getBoundingClientRect().top;
				window.scroll({
					top: y - 25,
					behavior: 'smooth'
				});
			}
		}).always(function () {
			overlay.hide();
		});
	};

	js_input_cmd_export.on('click', function (e) {
		overlay.show();
		js_form_export.find("[name=page]").val(nav.getProperty('page'));
		js_form_export.find("[name=from]").val(nav.getProperty('from'));
		js_form_export.find("[name=to]").val(nav.getProperty('to'));
		js_form_export.attr("method", "post");
		js_form_export.attr("action", exporturl + "/?");
		js_form_export.submit();

		setTimeout(() => {
			overlay.hide();
		}, 1000);

	});


	nav.onPopState(function () {
		slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));
		slo_date_start.set(nav.getProperty("from"), nav.getProperty("from"));
		slo_date_end.set(nav.getProperty("to"), nav.getProperty("to"));
		xhttp_request(nav, true);
	});

	js_output_page_total.on("click", function () {
		nav.setProperty("page", 0);
		nav.pushState();
		xhttp_request(nav, true)
	});
	/* Events binding */
	js_input_cmd_next.on("click", function () {
		if (parseInt(nav.getProperty("page")) >= total_pages) { return; };
		nav.setProperty("page", parseInt(nav.getProperty("page")) + 1);
		nav.pushState();
		js_input_cmd_prev.attr("disabled", false);
		slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));
		xhttp_request(nav, true);
	});
	js_input_cmd_prev.on("click", function () {
		if (parseInt(nav.getProperty("page")) <= 1) { return; };
		nav.setProperty("page", parseInt(nav.getProperty("page")) - 1);
		nav.pushState();
		slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"))
		xhttp_request(nav, true);
	});

	js_input_cmd_update.on('click', function () {
		nav.setProperty("page", 0);
		js_input_cmd_prev.attr("disabled", true);
		nav.pushState();
		try {
			if (slo_page_current[0].slo.handler instanceof NumberHandler) {
				slo_page_current[0].slo.handler.rangeEnd(1);
			}
		} catch (e) { }
		xhttp_request(nav, false);
	});
	xhttp_request(nav, false);
});
