const slomerge = function (a, b) {
	var out = {};
	for (var attname in b) { out[attname] = b[attname]; }
	for (var attname in a) { out[attname] = a[attname]; }
	return out;
};
class SmartListObjectHandler {
	constructor(initial = null, start = null, end = null) {
		this.initial = initial;
		this.output = false;
		this.range = { start: null, end: null };
		this.items_limit = 5;
	}
	clear() {
		this.output = false;
	}
	setItemsLimit(items_limit) {
		this.items_limit = items_limit ;
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
		let buffer = []
		let selected_buffer = false;
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

		const chunks = input.split(" ");
		let chunk_found = true;
		let chunks_found_count = 0;
		this.output = [];

		for (let listitem of this.dataset) {
			if (chunks_found_count > this.items_limit)
				break;
			chunk_found = true;
			for (let chunk of chunks) {
				if (chunk.trim() == "") continue;
				if ((listitem.id + listitem.value + listitem.keywords + listitem.height).toLocaleLowerCase().includes(chunk.toLocaleLowerCase())) {
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


(function ($) {
	$.fn.slo = function (options) {
		const slosettings = jQuery.extend({
			onselect: function () { },
			onblur: function () { },
			ondeselect: function () { },
			align: "left",
			limit: 5
		}, options);
		const $jq = this;
		let safeClearTrigger = false;
		const state = { 'idle': 0, 'up': 1, 'busy': 2 }

		const output = {
			//#region - Controlers
			'prop': function (property, value) {
				$jq.each(function () {
					this.object_htmlinput_id.prop(property, value);
					this.object_htmlinput_text.prop(property, value);
				});
			},
			'change': function (role) {
				$jq.each(function () {
					this.object_role = role;
				});
			},
			'disable': function () {
				$jq.each(function () {
					this.disabled = true;
					this.object_htmlinput_id.prop("disabled", true);
					this.object_htmlinput_text.prop("disabled", true);
					this.output_window.css({ "visibility": "hidden", "display": "none" });
					this.object_htmlinput_text.removeClass("listvisible", "listvisibletop");

				});
			},
			'enable': function () {
				$jq.each(function () {
					this.disabled = false;
					this.object_htmlinput_id.prop("disabled", false);
					this.object_htmlinput_text.prop("disabled", false);

				});
			},
			'clear': function (deselectEvent = true) {
				safeClearTrigger = true;
				$jq.each(function () {
					this.output_window.css("display", "none");
					this._status = state.idle;
					var pass = {
						object: this.object_htmlinput_text,
						value: this.object_htmlinput_text.val(),
						hidden: this.object_htmlinput_id.val(),
					};
					this.object_htmlinput_id.val("");
					this.object_htmlinput_text.val("");
					this.object_htmlinput_text.parent().removeClass("valid", "unvalid");
					if (typeof (slosettings.ondeselect) == "function" && deselectEvent) {
						slosettings.ondeselect.call(this, pass);
					}
				});
				safeClearTrigger = false;
			},
			'set': function (id, value) {
				safeClearTrigger = true;
				$jq.each(function () {
					this.output_window.css("display", "none");
					this._status = state.idle;
					this.object_htmlinput_id.val(id);
					this.object_htmlinput_text.val(value);
					if (id == 0) {
						this.object_htmlinput_text.parent().removeClass("unvalid", "valid");
					} else {
						this.object_htmlinput_text.parent().removeClass("unvalid").addClass("valid");
					}
				});
				safeClearTrigger = false;
			},
			'setparam': function (param) {
				$jq.each(function () {
					this.object_parameters = param;
				});
			},
			'getparam': function () {
				const output = []
				$jq.each(function () {
					output.push(this.object_parameters);
				});
				return output;
			},
			'get': function () {
				const output = []
				$jq.each(function () {
					output.push({
						"id": this.object_htmlinput_id.val(),
						"value": this.object_htmlinput_text.val()
					})
				});
				return output;
			},
			'input': [],
			'hidden': [],
			'focus': function () {
				$jq.each(function () {
					this.focusfix = true;
					this.object_htmlinput_text.focus().select();
				});
			},
			//#endregion
			'init': function () {
				const _parent = this;
				$jq.each(function () {
					//#region - Initialize
					const me = this;
					this.object_htmlinput_text = $(this);
					this.object_role = this.object_htmlinput_text.attr('data-slo');
					this.object_list_limit = parseInt(slosettings.limit);
					this.object_default_id = this.object_htmlinput_text.attr('data-slodefaultid');
					this.object_parameters = this.object_htmlinput_text.attr('data-sloparam');
					this.object_htmlinput_id = $("<input type=\"hidden\" />");
					this.object_container = $("<span />");
					this.output_window = $("<div />");
					this.http_request = $.ajax({ url: "slo" });
					this.current_selectin = null;
					this.state = state.idle;
					this.stamped = this.focusfix = this.enter_key_event = this.disabled = this.datalist = false;
					this.handler = false;

					_parent.hidden.push(me.object_htmlinput_id);
					_parent.input.push(me.object_htmlinput_text);
					this.output_window.addClass("cssSLO_output");
					this.object_container.addClass("cssSLO_wrap");
					this.object_htmlinput_id.val(me.object_default_id);
					this.object_htmlinput_text.attr("autocomplete", "off");
					this.object_htmlinput_text.wrap(this.object_container);
					this.object_htmlinput_text.parent().append(this.output_window);
					this.object_htmlinput_text.parent().append(this.object_htmlinput_id);

					this.state = state.idle;

					if (isNaN(this.object_list_limit)) {
						this.object_list_limit = 5;
					}

					if (me.object_htmlinput_text.attr("name") != undefined) {
						this.object_htmlinput_id.attr("name", me.object_htmlinput_text.attr("name") + "[1]");
						this.object_htmlinput_text.attr("name", me.object_htmlinput_text.attr("name") + "[0]");
					}

					if (me.object_htmlinput_text.attr("id") != undefined)
						this.object_htmlinput_id.attr("id", me.object_htmlinput_text.attr("id") + "_1");
					if (this.object_htmlinput_text.attr('class') != undefined)
						$.each(this.object_htmlinput_text.attr('class').split(/\s+/), function (index, class_name) { me.object_htmlinput_text.parent().addClass(class_name); });
					if (slosettings.align != undefined) {
						this.output_window.css("left", "0px");
						if (slosettings.align != "left")
							this.output_window.css("right", "0px");
					}
					if (this.object_default_id != undefined && this.object_default_id != "") {
						me.object_htmlinput_text.parent().removeClass("unvalid").addClass("valid");
						me.stamped = true;
					}
					this.object_container.css({
						'display': me.object_htmlinput_text.css('display'),
						'width': me.object_htmlinput_text[0].style.width,
						'max-width': me.object_htmlinput_text[0].style.maxWidth,
						'min-width': me.object_htmlinput_text[0].style.minWidth,
						'flex': me.object_htmlinput_text[0].style.flex,
						'flex-grow': me.object_htmlinput_text[0].style.flexGrow,
					});
					//#endregion


					/* Set handler */
					if (this.object_role == ":NUMBER") {
						this.handler = new NumberHandler(this.object_htmlinput_text.val() ?? null, this.object_htmlinput_text.attr("data-rangestart") ?? null, this.object_htmlinput_text.attr("data-rangeend") ?? null);
					} else if (this.object_role == ":DATE") {
						this.handler = new DateHandler(this.object_htmlinput_text.val() ?? null, this.object_htmlinput_text.attr("data-rangestart") ?? null, this.object_htmlinput_text.attr("data-rangeend") ?? null);
					} else if (this.object_role == ":LIST") {
						this.handler = new ListHandler($("#" + me.object_htmlinput_text.attr("data-list")));

					}

					/* Initilize handlers */
					if (this.handler !== false) {
						this.handler.setItemsLimit(me.object_list_limit);
						if (this.handler.validate(this.object_htmlinput_text.val(), true)) {
							_parent.set(this.handler.toString(true)[0], this.handler.toString(true)[1])
						} else
							_parent.clear();
					}

					const listpopulate = function () {
						const query = me.object_htmlinput_text.val();

						if (me.handler !== false) {
							let validate = me.handler.validate(query);
							if (validate) {
								me.state = state.up;
								me.output_window.html(me.handler.generate());
								me.output_window.find(">div:first-child").addClass("active");
								sloshow();
							} else {
								slohide();
							}
							return;
						}


						me.http_request.abort();
						me.http_request = $.ajax({
							type: 'POST',
							url: 'slo',
							data: slomerge({ 'role': me.object_role, 'query': query, 'limit': me.object_list_limit }, me.object_parameters)
						}).done(function (data) {
							if (data != "") {
								me.state = state.up;
								me.output_window.css({ "visibility": "hidden", "display": "block", "position": "fixed" });
								me.output_window.html(data);
								me.output_window.find(">div:first-child").addClass("active");
								sloshow();
							} else {
								me.state = state.idle;
								slohide();
							}
						}).fail(function (a, b, c) {
							me.state = state.idle;
						});
					}
					const slohide = function () {
						me.output_window.css({ "visibility": "hidden", "display": "none" });
						me.object_htmlinput_text.removeClass("listvisible", "listvisibletop");
						if (me.datetemplate) {
							me.datetemplate.clear();
						}
						me.http_request.abort();
					}
					const sloshow = function () {
						if (me.disabled) {
							return;
						}
						var _dh = $(document).height();
						me.output_window.css({ "visibility": "hidden", "display": "block", "position": "fixed" });
						if (_dh < me.output_window.height() + me.object_htmlinput_text.offset().top + me.object_htmlinput_text.height()) {
							me.object_htmlinput_text.removeClass("listvisible").addClass("listvisibletop");
							me.output_window.css({
								'bottom': '100%',
								'top': 'auto',
							});
						} else {
							me.object_htmlinput_text.removeClass("listvisibletop").addClass("listvisible");
							me.output_window.css({
								'top': '100%',
								'bottom': 'auto',
							});
						}
						me.output_window.css({ "visibility": "visible", "position": "absolute" });
					}
					const slofocus = function (displaylist) {
						if ((displaylist != undefined && displaylist != true) || me.disabled) {
							return false;
						}
						listpopulate();
					}
					const execut = function (obj) {
						if (me.disabled) { return }
						if (me.current_selectin == obj.val()) {
							me.state = state.idle;
							return;
						} else {
							if (me.stamped == true) {
								me.state = state.idle;
								me.object_htmlinput_id.val("");
								me.object_htmlinput_text.parent().removeClass("valid").addClass("unvalid");
								if (typeof (slosettings.ondeselect) == "function") {
									slosettings.ondeselect.call(this, {
										object: me.object_htmlinput_text,
										value: me.object_htmlinput_text.val(),
										hidden: me.object_htmlinput_id.val(),
									});
								}
							}
							me.stamped = false;
						}
						listpopulate();
					}

					this.object_htmlinput_text.on('input propertychange paste contextmenu drop', function (e) {
						if (!safeClearTrigger)
							execut($(this));
					}).on('focus', function (e) {
						if (me.focusfix == true) { me.focusfix = false; return false; }
						//self.object_htmlinput_text[0].scrollIntoView();
						/* const y = self.object_htmlinput_text[0].getBoundingClientRect().top + window.scrollY;
						window.scroll({
							top: y-180,
							behavior: 'smooth'
						  }); */
						slofocus();
						return;
					}).on('keydown', function (e) {
						var keycode = (e.keyCode ? e.keyCode : e.which);
						if (keycode == 27) {
							if (me.object_htmlinput_text.val() == "") {
								slohide();
								return;
							}
							var pass = {
								object: me.object_htmlinput_text,
								value: me.object_htmlinput_text.val(),
								hidden: me.object_htmlinput_id.val(),
							};
							if (me.datetemplate) {
								me.datetemplate.clear();
							}
							me.object_htmlinput_id.val("0");
							me.object_htmlinput_text.val("");
							me.stamped = false;
							me.object_htmlinput_text.parent().removeClass("valid").addClass("unvalid");
							if (typeof (slosettings.ondeselect) == "function") {
								slosettings.ondeselect.call(this, pass);
							}
							execut($(this));
							return;
						}
						if (keycode == 27 || keycode == 9) {
							me.state = state.idle;
							slohide();
							return;
						}
						if (keycode == 27) {
							e.preventDefault();
							return false;
						}
						if (keycode == 13 && me.state == state.up) {
							e.preventDefault();
							var $current = me.output_window.find(">div.active");
							me.enter_key_event = true;
							$current.trigger("click");
							me.focusfix = false;
							me.object_htmlinput_text.select();
							return false;
						}
						if (keycode == 40 && me.state == state.up) {
							e.preventDefault();
							var $current = me.output_window.find(">div.active");
							if ($current.next().length > 0) {
								$current.next().addClass("active");
								$current.removeClass("active");
							} else {

							}

							return false;
						} else if (keycode == 40 && me.state == state.idle) {
							me.object_htmlinput_text.trigger("click");
						}
						if (keycode == 38 && me.state == state.up) {
							e.preventDefault();
							var $current = me.output_window.find(">div.active");
							if ($current.prev().length > 0) {
								$current.prev().addClass("active");
								$current.removeClass("active");
							} else {

							}
							return false;
						} else if (keycode == 38 && me.state == state.idle) {
							me.object_htmlinput_text.trigger("click");
						}
					}).on('click', function (e) {
						if ($(this).is(":focus")) {
							me.focusfix == true;
							slofocus(true);
						} else {
							return false;
						}
					}).on('keyup', function (e) {
						var keycode = (e.keyCode ? e.keyCode : e.which);
						if (keycode == 13 && me.enter_key_event) {
							var $clicked = me.output_window.find(">div.active");
							if (typeof (slosettings.onselect) == "function") {

								var pass = {
									this: _parent,
									object: me.object_htmlinput_text,
									value: me.object_htmlinput_text.val(),
									hidden: me.object_htmlinput_id.val(),
									text: $clicked.find("span").html(),
								};
								slosettings.onselect.call(this, pass);
							}

							me.enter_key_event = false;
						}
					}).on('blur', function (e) {
						/*Wont work because setting focus on list objects will make the input loose its focus*/
					});

					this.output_window.on('click', " > div", function () {
						var $clicked = $(this);
						me.object_htmlinput_text.parent().addClass("valid").removeClass("unvalid");
						me.object_htmlinput_text.val($clicked.find("p").html());
						me.object_htmlinput_id.val($clicked.attr("data-return_id"));
						me.current_selectin = me.object_htmlinput_text.val();
						me.state = state.idle;
						me.stamped = true;


						slohide();
						me.focusfix = true;
						slofocus(false);
						me.object_htmlinput_text.select();
						if (me.enter_key_event) { return; }
						if (typeof (slosettings.onselect) == "function") {


							slosettings.onselect.call(this, {
								this: _parent,
								object: me.object_htmlinput_text,
								value: me.object_htmlinput_text.val(),
								hidden: me.object_htmlinput_id.val(),
								text: $clicked.find("span").html()
							});
						}
					});
					$(document).mousedown(function (e) {
						var container = me.object_htmlinput_text.parent();
						if (!container.is(e.target) && container.has(e.target).length === 0) {
							slohide();
						}
					});
				});
			}
		}
		output.init();
		return output;
	};

})(jQuery);