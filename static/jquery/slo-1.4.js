
class SmartListObjectHandler {
	constructor(initial = null, start = null, end = null) {
		this.initial = initial;
		this.output = false;
		this.range = { start: null, end: null };
		this.items_limit = 5;
	}
	set() { }
	clear() {
		this.output = false;
	}
	setItemsLimit(items_limit) {
		this.items_limit = items_limit;
	}
	generate(limit = 0) {
		return [];
	}
	validate(input, exact = false) {
	}
	toString() {
		return (this.output === false) ? "" : this.output;
	}
	itemGenerator(title = "", return_id = "", return_value = "", highlight = "") {
		return `<div data-return_id="${return_id}">
			<div>${title}</div>
			<span>${highlight}</span>
			<p>${return_value}</p>
		</div>`;
	}
}

class ListHandler extends SmartListObjectHandler {
	constructor(initial = null, start = null, end = null) {
		super(initial, start, end);
		let buffer = [];
		let selected_buffer = false;
		this.dropdown = false;
		initial.children('option').each(function () {
			buffer.push({
				id: this.dataset.id ?? "",
				value: this.value,
				keywords: this.dataset.keywords ?? "",
				highlight: this.dataset.highlight ?? "",
				selected: this.getAttribute("selected") == null ? false : true
			});
			if (this.getAttribute("selected") != null) {
				selected_buffer = buffer[buffer.length - 1]
			}

		});
		this.current = selected_buffer;
		this.dataset = buffer;
	}
	specialChars(str) {
		let output = str;
		output = output.replace(/[\]\[\)\(\.\*\&\\\/]*/, "");
		output = output.replace(/[أإاآ]/, "[أإاآ]");
		output = output.replace(/[ةه]/, "[ةه]");
		output = output.replace(/[يى]/, "[يى]");
		return output;

	}
	itemGenerator() {
		return "";
	}
	validate(input, exact = false) {
		if (exact)
			if (this.current !== false) {
				return true;
			} else {
				return false
			}

		this.output = [];
		let chunk_found = true;
		let chunks_found_count = 0;

		if (this.dropdown) {
			for (let listitem of this.dataset) {
				if (chunks_found_count > this.items_limit)
					break;
				this.output.push(listitem);
				chunks_found_count++
			}
		} else {
			let regeexp = null;
			const chunks = input.split(" ");
			for (let listitem of this.dataset) {
				if (chunks_found_count > this.items_limit)
					break;
				chunk_found = true;
				for (let chunk of chunks) {
					if (chunk.trim() == "") continue;
					regeexp = new RegExp('.*' + this.specialChars(chunk) + '.*', 'gi');
					if (regeexp.test(listitem.id + " " + listitem.value + " " + listitem.keywords)) {
						chunk_found &= true;
					} else {
						chunk_found &= false;
						continue;
					}
				}
				if (chunk_found) {
					chunks_found_count++;
					this.output.push(listitem);
				}
			}
		}
		if (this.output.length == 0) {
			return false;
		} else {
			return true;
		}
	}
	generate() {
		let buffer = "";
		if (this.output !== false) {
			this.output.forEach(listitem => {
				buffer += super.itemGenerator(listitem.value, listitem.id, listitem.value, listitem.highlight);
			});
		}
		return buffer;
	}
	toString(leading_zero = true) {
		if (this.current === false) return "";
		return [this.current.id, this.current.value];
	}
}

class NumberHandler extends SmartListObjectHandler {
	constructor(initial = null, start = null, end = null) {
		super(initial, start, end);

		this.initial = false;
		if (start != undefined) {
			this.range.start = parseInt(start);
			if (isNaN(this.range.start)) {
				this.range.start = 0;
			}
		}
		if (end != undefined) {
			this.range.end = parseInt(end);
			if (isNaN(this.range.end)) {
				this.range.end = Number.MAX_SAFE_INTEGER;
			}
		}
		if (this.range.start > this.range.end) {
			this.range.start = this.range.end;
		}

		let parser = parseInt(initial);
		if (!isNaN(parser) && (parser >= this.range.start && parser <= this.range.end)) {
			this.initial = parser;
		}
	}
	itemGenerator() {
		return "";
	}
	validate(input, exact = false) {
		if (input.trim() == "") {
			if (exact) return false;
			this.output = this.range.start;
		} else {
			this.output = parseInt(input);
			if (isNaN(this.output)) {
				if (exact) return false;
				this.output = this.range.start;
			}
			if (this.output < this.range.start) {
				if (exact) return false;
				this.output = this.range.start;
			}
			if (this.output > this.range.end) {
				if (exact) return false;
				this.output = this.range.end;
			}
		}
		return this.output;
	}
	generate() {
		let buffer = "";
		let increment = 0;
		while (increment <= this.items_limit - 1 && increment + this.output <= this.range.end) {
			buffer += super.itemGenerator(increment + this.output, increment + this.output, increment + this.output);
			increment++;
		}
		return buffer;
	}
	rangeEnd(val = null) {
		if (val != null) {
			this.range.end = val;
		}
		return this.range.end;
	}
	rangeStart(val = null) {
		if (val != null) {
			this.range.start = val;
		}
		return this.range.start;
	}
	toString(leading_zero = true) {
		if (this.output === false) return "";
		return [this.output, this.output];
	}
}

class DateHandler extends SmartListObjectHandler {
	constructor(initial = null, start = null, end = null) {
		super(initial, start, end);
		this.current = new Date();
		this.initial = initial == null ? null : new Date(initial);
		this.initial = this.initial != null && isNaN(this.initial.valueOf()) ? null : this.initial;

		if (start != null) {
			this.range.start = new Date(start) ?? null;
			this.range.start = isNaN(this.range.start.valueOf()) ? null : this.range.start;
		}
		if (end != null) {
			this.range.end = new Date(end) ?? null;
			this.range.end = isNaN(this.range.end.valueOf()) ? null : this.range.end;
			if (this.range.end != null) {
				this.range.end.setHours(23, 59, 59);
			}
		}
		this.clear();
	}
	itemGenerator() {
		return "";
	}
	generate() {
		let buffer = "";
		if (this.output == false) { return false; }
		const tempdate = new Date(this.output.valueOf());
		for (let i = 0; i < this.items_limit; i++) {
			buffer += super.itemGenerator(this.toLocalString(tempdate), this.toLocalString(tempdate), this.toLocalString(tempdate));
			tempdate.setDate(tempdate.getDate() + 1);
			if (this.range.end != null && tempdate.getTime() > this.range.end.getTime())
				break;
		}
		return buffer;
	}
	clear() {
		this.output = false;
		if (this.initial != null) {
			this.year = this.initial.getUTCFullYear();
			this.month = this.initial.getMonth() + 1;
			this.day = this.initial.getDate();
			this.current = this.initial;
		} else if (this.range.start != null) {
			this.year = this.range.start.getUTCFullYear();
			this.month = this.range.start.getMonth() + 1;
			this.day = this.range.start.getDate();
			this.current = this.range.start;
		} else {
			this.year = this.current.getUTCFullYear();
			this.month = this.current.getMonth() + 1;
			this.day = this.current.getDate();
			this.current = new Date();
		}
	}
	validate(input, exact = false) {
		let sg = [];
		for (let n of input.split(/[ \-\:\/\\]/g)) {
			let parsed = parseInt(n);
			if (parsed == n && parsed >= 0) {
				sg.push(parsed)
			} else if (n !== "") {
				/* Might be used to search month by name */
				this.output = this.current;
			}
		}
		if (exact && sg.length != 3) {
			this.output = false;
			return this.output;
		}

		if (sg.length == 0) {
			/* Default date */
		} else if (sg.length == 1) {
			let yord = sg[0];
			if (yord <= 31) {
				/* A day */
				this.year = this.current.getFullYear();
				this.month = this.current.getMonth() + 1;
				this.day = yord;
			} else {
				/* Must by a year */
				let yearCompleteFromCurrent = yord.toString();
				if (yearCompleteFromCurrent.length < 4) {
					if (this.current.getFullYear().toString().slice(0, yearCompleteFromCurrent.length) == yearCompleteFromCurrent) {
						this.year = yearCompleteFromCurrent + this.current.getFullYear().toString()[yearCompleteFromCurrent.length]
						this.year = this.year;
					}
				} else if (yearCompleteFromCurrent.length == 4) {
					this.year = yearCompleteFromCurrent;
					this.year = this.year;
				} else {
					this.output = this.current;
				}
				this.month = this.current.getMonth() + 1;
				this.day = this.current.getDate();
			}
		} else if (sg.length == 2) {
			let yord = sg[0];
			if (yord > 31) {
				/* First segment isn't a month or a day, treat it as a year */
				this.year = sg[0];
				this.month = sg[1] == 0 ? 1 : sg[1];
				this.day = 1
			} else {
				/* month & day segments */
				this.year = this.current.getFullYear();
				this.month = sg[0] == 0 ? 1 : sg[0];
				this.day = sg[1] == 0 ? 1 : sg[1];
			}
		} else if (sg.length == 3) {
			/* all three segments given */
			this.year = sg[0];
			this.month = sg[1] == 0 ? 1 : sg[1];
			this.day = sg[2] == 0 ? 1 : sg[2];
			/* convert 2 digits year to 4 based on current year mask */
			this.year = this.year < 100 ? (this.current.getFullYear().toString().slice(0, 2) + this.year.toString()) : this.year;
		} else {
			/* More than 3 segments */
			this.output = this.current;
		}

		/* Invalid date input */
		if ((this.day <= 0 || this.day > 31) || (this.month <= 0 || this.month > 12) || (this.year <= 0 || this.year > 3000)) {
			this.output = this.current;
			if (exact) {
				this.output = false;
				return false
			}
		}
		this.output = new Date();
		this.output.setYear(this.year)
		this.output.setMonth(this.month - 1)
		this.output.setDate(this.day)
		/* parse date doesn't match given date */
		if (this.output.getDate() != this.day) {
			this.output = this.current;
			if (exact) {
				this.output = false;
				return false
			}
		} else {
			if ((this.range.start != null && this.output.getTime() < this.range.start.getTime()) || (this.range.end != null && this.output.getTime() > this.range.end.getTime())) {
				this.output = this.current;
			}
		}
		return this.output;
	}
	toString(leading_zero = true) {
		if (this.output === false) return "";
		return [this.toLocalString(this.output, leading_zero), this.toLocalString(this.output, leading_zero)];
	}
	toLocalString(date, leading_zero = true) {
		if (leading_zero) {
			return date.getFullYear() + "-" + (date.getMonth() + 1).toString().padStart(2, '0') + "-" + date.getDate().toString().padStart(2, '0');
		} else {
			return date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
		}
	}
}

const state = { 'idle': 0, 'up': 1, 'busy': 2 };
const stamp = { 'valid': true, 'unvalid': false, 'empty': null };
const slomerge = function (a, b) {
	var out = {};
	for (var attname in b) { out[attname] = b[attname]; }
	for (var attname in a) { out[attname] = a[attname]; }
	return out;
};

class SmartListObject {
	constructor(object) {
		this.id = null;
		this.object = object;
		this.htmltext = $(object);
		this.role = this.htmltext.attr('data-slo');
		this.htmlhidden = $("<input type=\"hidden\" />");
		this.selection_win = $("<div />");
		this.htmltext.wrap($("<span />"));
		this.container = this.htmltext.parent();
		this.htmlhidden.insertAfter(this.htmltext);
		this.selection_win.insertAfter(this.htmltext);
		this.selection = false;
		this.items_limit = 5;
		this.stamped = null;
		this.disabled = false;
		this.xhttp = null;
		this.is_selectobject = false;
		this.state = state.idle;
		this.hadfocus = false;
		this.handler = this.objectHandler();
		this.events = { onselect: function () { }, ondeselect: function () { }, onkeydown: function (e) { }, onkeyup: function () { } }
		this.parameters = this.htmltext.attr('data-sloparam');
		this.init();


		this.populate = function () { };
		if (this.handler instanceof SmartListObjectHandler) {
			this.populate = this.populateByHandler;
		} else {
			this.populate = this.populateByAJAX;
		}
	}
	populateByHandler() {
		if (this.handler !== null) {
			let validate = this.handler.validate(this.htmltext.val());
			if (validate) {
				this.selection_win.html(this.handler.generate());
				this.selection = this.selection_win.find(">div:first-child");
				this.selection.addClass("active");
				this.show();
			} else {
				this.hide();
			}
		}
	}
	populateByAJAX() {
		if (this.xhttp != null)
			this.xhttp.abort();
		this.xhttp = $.ajax({
			type: 'POST',
			url: 'slo',
			data: slomerge({ 'role': this.role, 'query': this.htmltext.val(), 'limit': this.items_limit }, this.parameters)
		}).done((data) => {
			if (data != "") {
				this.selection_win.css({ "visibility": "hidden", "display": "block", "position": "fixed" });
				this.selection_win.html(data);
				this.selection = this.selection_win.find(">div:first-child");
				this.selection.addClass("active");
				this.show();
			} else {
				this.hide();
			}
		}).fail((a, b, c) => {
			this.state = state.idle;
		});
	}
	init() {
		this.htmltext.attr("autocomplete", "off");
		this.selection_win.addClass("slo-container");
		this.container.addClass("slo-wrap");
		this.container.css({
			'display': this.htmltext[0].style.display,
			'width': this.htmltext[0].style.width,
			'max-width': this.htmltext[0].style.maxWidth,
			'min-width': this.htmltext[0].style.minWidth,
			'flex': this.htmltext[0].style.flex,
			'flex-grow': this.htmltext[0].style.flexGrow,
		});

		if (this.htmltext.attr("name") != undefined) {
			this.htmlhidden.attr("name", this.htmltext.attr("name") + "[1]");
			this.htmltext.attr("name", this.htmltext.attr("name") + "[0]");
		}

		if (this.htmltext.attr("id") != undefined) {
			this.id = this.htmltext.attr("id");
			this.htmlhidden.attr("id", this.htmltext.attr("id") + "_1");
		}
		if (this.htmltext.attr('class') != undefined) {
			$.each(this.htmltext.attr('class').split(/\s+/), (index, class_name) => {
				this.container.addClass(class_name);
			});
		}
		if (this.htmltext.attr('data-slodefaultid') != undefined && this.htmltext.attr('data-slodefaultid') != "") {
			this.htmlhidden.val(this.htmltext.attr('data-slodefaultid'));
			this.stamp(stamp.valid)
		}
	}
	objectHandler() {
		let handler = null;
		if (this.role == ":NUMBER") {
			handler = new NumberHandler(this.htmltext.val() ?? null, this.htmltext.attr("data-rangestart") ?? null, this.htmltext.attr("data-rangeend") ?? null);
		} else if (this.role == ":DATE") {
			handler = new DateHandler(this.htmltext.val() ?? null, this.htmltext.attr("data-rangestart") ?? null, this.htmltext.attr("data-rangeend") ?? null);
		} else if (this.role == ":LIST") {
			handler = new ListHandler($("#" + this.htmltext.attr("data-list")));
		} else if (this.role == ":SELECT") {
			this.container.addClass("slo-select");
			this.htmltext.prop("readonly", true);
			handler = new ListHandler($("#" + this.htmltext.attr("data-list")));
			this.is_selectobject = true;
			handler.dropdown = true;
		}
		/* Initilize handlers */
		if (handler !== null) {
			handler.setItemsLimit(this.items_limit);
			if (handler.validate(this.htmltext.val(), true)) {
				this.set(handler.toString(true)[0], handler.toString(true)[1]);
				this.stamped = true;
			}
		}
		return handler;
	}
	disable() {
		this.disabled = true;
		this.htmlhidden.prop("disabled", true);
		this.htmltext.prop("disabled", true);
		this.selection_win.css({ "visibility": "hidden", "display": "none" });
		this.htmltext.removeClass("listvisible", "listvisibletop");
	}
	enable() {
		this.disabled = false;
		this.htmlhidden.prop("disabled", false);
		this.htmltext.prop("disabled", false);
	}
	stamp(stamp) {
		switch (stamp) {
			case true:
				this.container.removeClass("unvalid").addClass("valid");
				this.stamped = true;
				break;
			case false:
				this.container.removeClass("valid").addClass("unvalid");
				this.stamped = false;
				break;
			default:
				this.container.removeClass("valid").removeClass("unvalid");
				this.stamped = null;
				break;
		}
	}
	show() {
		if (this.disabled) {
			return;
		}
		this.state = state.up;
		var _dh = $(document).height();
		this.selection_win.css({ "visibility": "hidden", "display": "block", "position": "fixed" });
		if (_dh < this.selection_win.height() + this.htmltext.offset().top + this.htmltext.height()) {
			this.htmltext.removeClass("listvisible").addClass("listvisibletop");
			this.selection_win.css({
				'bottom': '100%',
				'top': 'auto',
			});
		} else {
			this.htmltext.removeClass("listvisibletop").addClass("listvisible");
			this.selection_win.css({
				'top': '100%',
				'bottom': 'auto',
			});
		}
		this.selection_win.css({ "visibility": "visible", "position": "absolute" });
	}
	hide() {
		this.state = state.idle;
		this.selection_win.css({ "visibility": "hidden", "display": "none" });
		this.htmltext.removeClass("listvisible", "listvisibletop");
		if (this.xhttp != null)
			this.xhttp.abort();
	}
	focus(display_win = false) {
		if (this.disabled) return;
		this.hadfocus = true;
		this.htmltext.focus();
		if (!this.is_selectobject) {
			this.htmltext.select();
		}
		if (display_win) {
			this.populate();
		}
	}
	set(id, value = null) {
		this.selection_win.css("display", "none");
		this.state = state.idle;

		if (this.role == ":LIST" || this.role == ":SELECT") {
			if (value === null) {
				var result = this.handler.dataset.filter(option => {
					return option.id == id;
				});
				if (result.length > 0) {
					this.htmlhidden.val(result[0].id);
					this.htmltext.val(result[0].value);
					this.stamp(stamp.valid)
					this.stamped = true;
				} else {
					this.stamp(stamp.unvalid)
				}
			} else {
				this.htmlhidden.val(id);
				this.htmltext.val(value);
				this.stamp(stamp.valid)
				this.stamped = true;
			}
		} else {
			this.htmlhidden.val(id);
			this.htmltext.val(value);
			if (id == false) {
				this.stamp(stamp.empty)
			} else {
				this.stamped = true;
				this.stamp(stamp.valid)
			}
		}
	}
	get() {
		return {
			"id": this.htmlhidden.val(),
			"value": this.htmltext.val()
		}
	}
	clear(raise_events) {
		this.hide();
		this.state = state.idle;
		this.stamp(stamp.idle);
		this.htmlhidden.removeAttr('value');
		this.htmltext.val('');

		if (raise_events) {
			this.call_ondeselect();
		}
	}
	call_ondeselect() {
		if (typeof (this.events.ondeselect) == "function") {
			this.events.ondeselect.call(this, {
				object: this.htmltext,
				value: this.htmltext.val(),
				hidden: this.htmlhidden.val(),
			});
		}
	}
	call_onselect() {
		if (typeof (this.events.onselect) == "function") {
			this.events.onselect.call(this, {
				/* this: _parent, */
				object: this.htmltext,
				value: this.htmltext.val(),
				hidden: this.htmlhidden.val(),
				text: $(this.selection).find("span").html(),
			});
		}
	}
	commit(enter_key_event) {
		if (this.selection == null) { return }
		this.htmltext.val(this.selection.find("p").html());
		this.htmlhidden.val(this.selection.attr("data-return_id"));
		this.state = state.idle;
		this.stamp(stamp.valid);
		this.hide();
		this.focusfix = true;
		this.focus(false);
		this.htmltext.focus();
		if (!this.is_selectobject) {
			this.htmltext.select();
		}
		if (enter_key_event) { return; }
		this.call_onselect();
	}
}

(function ($) {
	$.fn.slo = function (options) {
		const $jq = this;
		const slosettings = jQuery.extend({
			onselect: function () { },
			onblur: function () { },
			ondeselect: function () { },
			onkeydown: function (e) { },
			onkeyup: function (e) { },
			align: "left",
			limit: 5,
			dropdown: false
		}, options);

		let safeClearTrigger = false;
		this.input = [];
		this.hidden = [];

		//#region - Controlers
		this.change = function (role) {
			$jq.each(function () { this.slo.role = role; });
			return $jq;
		};
		this.disable = function () {
			$jq.each(function () { this.slo.disable(); });
			return $jq;
		};
		this.getElementById = function (id) {
			let output = false;
			$jq.each(function () {
				if (this.id == id) {
					output = this;
				}
			});
			return output;
		};
		this.enable = function () {
			$jq.each(function () { this.slo.enable(); });
			return $jq;
		};
		this.clear = function (raise_events = true) {
			safeClearTrigger = true;
			$jq.each(function () { this.slo.clear(raise_events); });
			safeClearTrigger = false;
			return $jq;
		};
		this.get = function () {
			const output = []
			$jq.each(function () { output.push(this.slo.get()); });
			return output;
		};
		this.set = function (id, value = null) {
			safeClearTrigger = true;
			$jq.each(function () { this.slo.set(id, value); });
			safeClearTrigger = false;
			return $jq;
		};
		this.setparam = function (param) {
			$jq.each(function () { this.object_parameters = param; });
			return $jq;
		};
		this.getparam = function () {
			const output = []
			$jq.each(function () { output.push(this.object_parameters); });
			return output;
		};
		this.handlersInit = function () {
			$jq.each(function () { this.slo.handler = this.slo.objectHandler(); });
			return $jq;
		};
		this.focus = function () {
			$jq.each(function () { this.slo.hadfocus = true; this.slo.htmltext.focus(); if (!this.slo.is_selectobject) { this.slo.htmltext.select(); } });
			return $jq;
		};
		//#endregion


		this.init = function () {
			$jq.each(function () {
				//#region - Initialize
				this.slo = new SmartListObject(this);

				this.enter_key_event = false;
				this.slo.events.ondeselect = slosettings.ondeselect;
				this.slo.events.onselect = slosettings.onselect;
				this.slo.items_limit = slosettings.limit != undefined && !isNaN(slosettings.limit) ? parseInt(slosettings.limit) : 5;

				const slo = this.slo;

				if (slosettings.align != undefined) {
					this.slo.selection_win.css("left", "0px");
					if (slosettings.align != "left")
						this.slo.selection_win.css("right", "0px");
				}
				//#endregion

				this.slo.htmltext.on('input propertychange paste drop', (e) => {
					if (!safeClearTrigger && !this.slo.disabled) {
						this.slo.state = state.idle;
						this.slo.htmlhidden.val("");
						this.slo.stamp(stamp.unvalid)
						this.slo.call_ondeselect();
						this.slo.populate();
					}
				}).on('blur', (e) => {
					slo.hadfocus = false;
				}).on('focus', (e) => {
					if (slo.hadfocus) return true;
					this.slo.focus(this.slo.stamped != stamp.valid ? true : false);
				}).on('keydown', (e) => {
					slosettings.onkeydown.call(slo, e);
					if (e.code === "Escape") {
						if (this.slo.htmltext.val() == "") {
							this.slo.stamp(stamp.empty);
							this.slo.hide();

							return true;
						}
						e.preventDefault();
						this.slo.clear(true);
						this.slo.populate();
						return false;
					}
					if (e.code === "Tab") {
						this.slo.state = state.idle;
						this.slo.hide();
						return;
					}
					if ((e.code === "Enter" || e.code === "NumpadEnter") && this.slo.state === state.up) {
						e.preventDefault();
						this.enter_key_event = true;
						this.slo.commit(true);
						return false;
					}
					if (e.code === "ArrowDown" || e.code === "ArrowUp") {
						e.preventDefault();
						if (this.slo.state == state.up) {
							e.preventDefault();
							this.slo.selection = this.slo.selection_win.find(">div.active");
							if (e.code === "ArrowDown" && this.slo.selection.next().length > 0) {
								this.slo.selection.removeClass("active")
								this.slo.selection = this.slo.selection.next();
								this.slo.selection.addClass("active");
							} else if (e.code === "ArrowUp" && this.slo.selection.prev().length > 0) {
								this.slo.selection.removeClass("active")
								this.slo.selection = this.slo.selection.prev();
								this.slo.selection.addClass("active");
							}
							return false;
						} else {
							this.slo.focusfix == true;
							this.slo.focus(true);
						}
						return false;
					}


				}).on('click', function (e) {
					if (slo.is_selectobject) {
						if (slo.state == state.idle) {
							slo.focus(true);
						} else if (slo.state == state.up && !slo.hadfocus) {
							slo.hide();
						}
					} else {
						if (slo.state == state.idle) {
							slo.focus(true);
						}
					}
					slo.hadfocus = false;
					return;
				}).on('keyup', (e) => {
					if ((e.code === "Enter" || e.code === "NumpadEnter") && this.enter_key_event) {
						this.slo.stamp(stamp.valid);
						this.slo.call_onselect();
						this.enter_key_event = false;
					}
				});

				this.slo.selection_win.on('click', " > div", function (e) {
					e.stopPropagation();
					/*e.preventDefault(); */
					slo.selection = $(this);
					slo.commit(this.enter_key_event);
					return false;
				});
				$(document).mousedown(function (e) {
					var container = slo.htmltext.parent();
					if (!container.is(e.target) && container.has(e.target).length === 0) {
						slo.hide();
					}
				});
			});
		};
		this.init();
		return $jq;
	};

})(jQuery);