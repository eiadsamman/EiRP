
(function ($) {
	$.msgsys = function (options) {
		var settings = jQuery.extend({
			fadeuration: 300,
			timeoutperletter: 120,
			onshow: function () { },
			onhide: function () { }

		}, options);
		var container = $("<span />"),
			messagetype = $("<div />"),
			message = $("<div />"),
			icon = $("<span />"),
			status = 'idle',
			timeouthandler = null,
			timeouttime = 0;

		var show = function (type, value) {
			timeouttime = value.length * settings.timeoutperletter;
			timeouttime = timeouttime < 1000 ? 1000 : timeouttime;
			status = 'up';
			messagetype.removeClass(["success", "failure"]).addClass(type);
			message.html(value);

			if (typeof (settings.onshow) == "function") {
				settings.onshow.call(this);
			}
			container.css({ 'display': 'block', 'opacity': 1 });
			timeouthandler = setTimeout(function () { hide(); }, timeouttime);
		}
		var hide = function () {
			clearTimeout(timeouthandler);
			if (typeof (settings.onhide) == "function") {
				settings.onhide.call(this);
			}
			status = 'idle';
			container.css({ 'display': 'none', 'opacity': 0 });
		}
		var hideshow = function (type, value) {
			clearTimeout(timeouthandler);
			container.css({ 'opacity': 0 });
			show(type, value);
			return;
		}
		var output = {
			'success': function (value) {
				if (status == 'show' || status == 'up') {
					hideshow("success", value);
					return output;
				}
				show("success", value);
				return output;
			},
			'failure': function (value) {
				if (status == 'show' || status == 'up') {
					hideshow("failure", value);
					return output;
				}
				show("failure", value);
				return output;
			},
			'init': function () {
				container.addClass("messagesys");
				messagetype.append(icon);
				messagetype.append(message);
				container.append(messagetype);
				container.css({ 'opacity': 0, 'display': 'none' })
				messagetype.on('click', function () {
					hide();
				});
				$("body").prepend(container);

			}
		}
		output.init();
		return output;
	};

	$.overlay = function (options) {
		var settings = jQuery.extend({
			message: "Loading...",
			className: "loading_overlay",
			backgroundObject: $("#body-content, #template-sidePanel")
		}, options);
		var container = $("<span />"),
			content = $("<div />"),
			timer = null,
			hideTrigger = false;
		var output = {
			'show': function () {
				hideTrigger = false;
				timer = setTimeout(function () {
					if (!hideTrigger) {
						settings.backgroundObject.addClass("blur");
						container.css("display", "flex");
					}
				}, 300);
				return output;
			},
			'hide': function () {
				hideTrigger = true;
				clearTimeout(timer);
				container.css("display", "none");
				settings.backgroundObject.removeClass("blur");
				return output;
			},
			'state': function (state) {
				if (state) {
					this.show();
				} else {
					this.hide();
				}
			},
			'init': function () {
				container.addClass(settings.className);
				container.append(content);
				content.html("\
					<span style=\"display:inline-block;vertical-align:middle\">"+ settings.message + "</span>\
					<div class=\"css-progress-bar\"><span></span></div>\
				");
				$("body").prepend(container);
			}
		}
		output.init();
		return output;
	};
})(jQuery);


var messagesys = null;
var overlay = null;

$(document).ready(function () {
	messagesys = $.msgsys();
	overlay = $.overlay();
});