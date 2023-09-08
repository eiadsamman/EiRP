var TemplateScrollBar = function (elementId, scrollbarId) {
	let domContainer = document.getElementById(elementId);
	let domScrollbar = document.getElementById(scrollbarId);
	let scrollbarPadding = 5;
	let dragstate = false;
	let pos2 = 0, pos4 = 0;
	if (
		domContainer == null || domContainer == undefined ||
		domScrollbar == null || domScrollbar == undefined
	) {
		return false;
	}
	let __TSBscrollBarHeight = domScrollbar.offsetHeight;
	let __TSBfnAgent = function () {
		const rg = [/Tablet/i, /Mobile/i];
		return rg.some((m) => { return navigator.userAgent.match(m); });
	}
	let __TSBMoveHandler = function (e) {
		e = e || window.event;
		e.preventDefault();
		pos2 = pos4 - e.pageY;
		pos4 = e.pageY;
		let cord = domScrollbar.offsetTop - pos2;
		if (cord <= scrollbarPadding) {
			cord = scrollbarPadding;
		} else if (cord >= (domContainer.clientHeight - domScrollbar.offsetHeight - scrollbarPadding)) {
			cord = (domContainer.clientHeight - domScrollbar.offsetHeight - scrollbarPadding);
		}
		domScrollbar.style.top = cord + "px";
		domContainer.scrollTo({
			top: (((cord - scrollbarPadding) / (domContainer.clientHeight - domScrollbar.offsetHeight - scrollbarPadding * 2) * (domContainer.scrollHeight - domContainer.clientHeight))),
		});
	};

	let Init = function () {
		domContainer.onmouseenter = function (e) {
			domScrollbar.style.display = (this.scrollHeight > this.offsetHeight) ? "block" : "none";
		};
		if (!__TSBfnAgent()) {
			domContainer.addEventListener("scroll", function (e) {
				if (!dragstate) {
					domScrollbar.style.top = ((((e.target.scrollTop) / (e.target.scrollHeight - e.target.offsetHeight) * (e.target.clientHeight - 10 - domScrollbar.offsetHeight))) + scrollbarPadding) + "px";
				}
			});
		};
		domScrollbar.onmousedown = function (e) {
			dragstate = true;
			domScrollbar.classList.add("drag-active");
			e = e || window.event;
			e.preventDefault();
			pos4 = e.pageY;
			document.onmouseup = function (e) {
				dragstate = false;
				document.onmouseup = null;
				document.onmousemove = null;
				domScrollbar.classList.remove("drag-active");
			};
			document.onmousemove = __TSBMoveHandler;
		};
	}
	return Init();
};
window.addEventListener('load', (event) => {
	TemplateScrollBar("template-sidePanelContentScrollable", "template-sideScrollbar");
});

class Template {
	static _initialized = false;
	static PageRedirect(url, title, historyPushState) {
		let domContent = $("#body-content");
		overlay.show();
		$.ajax({
			'url': url,
			'type': 'POST'
		}).done(function (o) {
			if (title)
				document.title = title;
			if (historyPushState) {
				history.pushState({ 'url': url, 'title': title }, title, url);
			}
			domContent.html(o);
			overlay.hide();
			window.scrollTo(0, 0);
		}).always(function () {
			overlay.hide();
		}).fail(function (a, b, c) {
			if (a.status == 403 || a.status == 404) {
				messagesys.failure("Access denied or session has timed out");
			}
		});
	}
	static ReloadSidePanel() {
		let _bodyDOM = $("#template-sidePanelGroupItems"),
			_templateURL = $("#template-sidePanel").attr("data-template_url");
		$.ajax({
			'url': _templateURL,
			'type': 'POST',
			'data': "TemplateCallback=1"
		}).done(function (o) {
			_bodyDOM.html(o);
		});
	}
	static Initialize() {
		let _this = this;
		window.onpopstate = function (e) {
			_this.PageRedirect(e.state.url, e.state.title, false);
		};
	}
	static HistoryEntry(url, title) {
		if (!this._initialized) {
			history.replaceState({ 'url': url, 'title': title }, '', url);
			this._initialized = true;
		}
	}
}

(function ($) {
	$.fn.Template = function (options) {
		var settings = jQuery.extend({
			onclick: function () { }
		}, options);
		var _this = this;

		var init = function () {
			_this.each(function () {
				$(this).on("click", function (e) {
					e.preventDefault;
					let url = $(this).attr("href")
					title = $(this).attr("data-role_title");
					Template.PageRedirect(url, title, true)
					return false;
				});
			});
		}
		init();
		return _this;
	}
})(jQuery);

Template.Initialize();
