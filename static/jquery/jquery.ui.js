$(document).ready(function (e) {
	var RootHeaderPFTrigger = $("#PFTrigger"),
		RootHeaderPFSelector = $("#PFSelector"),
		backgroundObject = $("#body-content, #template-sidePanel"),

		RootHeaderMenu = $("#header-menu"),
		AccountHeaderMenu = $("#account-menu"),
		CompanyHeaderMenu = $("#company-menu"),
		UserHeaderMenu = $("#user-menu");
	var MenuVisible = false,
		MenuCurrent = null,
		InitiatorObject = null;


	var ClearInitiators = function () {
		if (InitiatorObject != null) {
			InitiatorObject.classList.remove("active");
			InitiatorObject = null;
		}
	}
	var RootHeaderMenuToggle = function (state, focusGoto = false, menu = null, initiator = null) {
		if (state == true) {
			if (MenuCurrent != null && MenuCurrent != menu) {
				MenuCurrent.removeClass("show");
				backgroundObject.removeClass("blur");
			}
			MenuCurrent = menu;
			MenuVisible = true;
			MenuCurrent.addClass("show");
			backgroundObject.addClass("blur");
			ClearInitiators();
			if (initiator != null) {
				InitiatorObject = initiator;
				initiator.classList.add("active");
			}
			if (focusGoto && menu == RootHeaderMenu) { setTimeout(function () { RootHeaderPFSelector.focus().select(); }, 30); }
		} else {
			MenuVisible = false;
			MenuCurrent.removeClass("show");
			backgroundObject.removeClass("blur");

			ClearInitiators();
			MenuCurrent = null;
		}
	}

	$(document).on('keydown', function (e) {
		if ((e.metaKey || e.ctrlKey) && (String.fromCharCode(e.which).toLowerCase() === 'm')) {
			e.preventDefault();
			RootHeaderMenuToggle(!MenuVisible, true, RootHeaderMenu);
		}
		if (e.which == 27 && MenuVisible) {
			RootHeaderMenuToggle(false);
		}
	});

	$("#header-menu-button").on('click', function (e) {
		e.preventDefault;
		RootHeaderMenuToggle(!MenuVisible || MenuCurrent != RootHeaderMenu, false, RootHeaderMenu, e.currentTarget);
		return false;
	});
	$("#jqroot_sec").on('click', function (e) {
		e.preventDefault;
		RootHeaderMenuToggle(!MenuVisible || MenuCurrent != AccountHeaderMenu, false, AccountHeaderMenu, e.currentTarget);
		return false;
	});
	$("#jqroot_com").on('click', function (e) {
		e.preventDefault;
		RootHeaderMenuToggle(!MenuVisible || MenuCurrent != CompanyHeaderMenu, false, CompanyHeaderMenu, e.currentTarget);
		return false;
	});
	$("#header-menu-useraccount-button").on('click', function (e) {
		e.preventDefault;
		RootHeaderMenuToggle(!MenuVisible || MenuCurrent != UserHeaderMenu, false, UserHeaderMenu, e.currentTarget);
		return false;
	});

	$(document).mouseup(function (e) {
		if ((RootHeaderMenu.is(e.target) || AccountHeaderMenu.is(e.target) || CompanyHeaderMenu.is(e.target) || UserHeaderMenu.is(e.target)) && MenuVisible) {
			RootHeaderMenuToggle(false);
			backgroundObject.removeClass("blur");
		}
	});

	RootHeaderPFSelector.slo({
		onselect: function (data) {
			RootHeaderPFTrigger.attr("href", data.hidden);
			RootHeaderPFTrigger[0].click();
		},
		limit: 5
	});

	$("#account-menu-slo").slo({
		onselect: function (data) {
			RootHeaderPFTrigger.attr("href", data.object.attr("data-url") + "?--sys_sel-change=account_commit&i=" + data.hidden);
			RootHeaderPFTrigger[0].click();
		},
		limit: 7
	});
	$("#company-menu-slo").slo({
		onselect: function (data) {
			RootHeaderPFTrigger.attr("href", data.object.attr("data-url") + "?--sys_sel-change=company_commit&i=" + data.hidden);
			RootHeaderPFTrigger[0].click();
		},
		limit: 7
	});


	$("#header-menu > div > div b").on("click", function (e) {
		if (e.target.tagName != "A") {
			var $nextElem = $(this).next();
			if ($nextElem.is("div")) {
				if ($nextElem.css("display") == "none") {
					$nextElem.css("display", "block");
				} else {
					$nextElem.css("display", "none");
				}
			}
		}
	});

	$("#bookmark-button").on("click", function (e) {
		if (e.target.dataset.target_id == null || e.target.dataset.role == null) { return; };

		if (e.target.dataset.role == "add") {
			$.ajax({
				data: { "add": e.target.dataset.target_id, "deployment": "usermenu" },
				url: "user-account/bookmarks",
				type: "POST"
			}).done(function (data, textStatus, request) {
				let response = request.getResponseHeader('QUERY_RESULT');
				if (parseInt(response) == 1) {
					messagesys.success("Bookmark added successfully");
					
					e.target.textContent = "Remove";
					e.target.dataset.role = "remove";
				} else if (parseInt(response) == 2) {
					messagesys.success("Bookmark already exists");
				} else {
					messagesys.failure("Adding bookmark failed, server error");
				}
			}).fail(function () {
				messagesys.failure("Adding bookmark failed, server error");
			});

			
		}
		if (e.target.dataset.role == "remove") {
			$.ajax({
				data: { "remove": e.target.dataset.target_id, "deployment": "usermenu" },
				url: "user-account/bookmarks",
				type: "POST"
			}).done(function (data) {
				if (parseInt(data) == 1) {
					messagesys.success("Bookmark removed successfully");
					e.target.textContent = "Add";
					e.target.dataset.role = "add";
				} else {
					messagesys.failure("Bookmark removeing failed");
				}
			}).fail(function () {
				messagesys.failure("Removeing bookmark failed, server error");
			});
		}
		e.preventDefault();
	});

	var jqroot_bal = $("#jqroot_bal"),
		jqroot_sec = $("#jqroot_sec"),
		jqroot_com = $("#jqroot_com");
	BALANCE_UPDATE = function () {
		$.ajax({
			data: {},
			url: "acc/report/balupd",
			type: "POST"
		}).done(function (data) {
			try { var json = JSON.parse(data); } catch (err) { return; }
			if (json.currency != undefined && json.currency != undefined && json.currency != false && json.value != false) {
				jqroot_bal.show();
				jqroot_bal.html(json.value + " " + json.currency);
			} else {
				jqroot_bal.hide();
			}
			if (json.company != undefined && json.company != false) {
				jqroot_com.html(json.company);
			} else {
				jqroot_com.html("N/A");
			}
			if (json.group != undefined && json.name != undefined && json.group != false && json.name != false) {
				jqroot_sec.html(json.group + ": " + json.name);
			} else {
				jqroot_sec.html("N/A");
			}
		});
	}
	/*setInterval(function(){BALANCE_UPDATE();},5000);*/


	$(document).on("click", ".template-frameTitle", function (e) {
		if (e.target.nodeName == "SPAN") {
			let domBody = $("#" + $(this).attr("data-templatebody"));
			if (domBody.css('display') == "none") {
				domBody.show();
			} else {
				domBody.hide();
			}
		}
	});



});
var formatter = new Intl.NumberFormat('en-US', {
});
Number.prototype.numberFormat = function (decimals, dec_point, thousands_sep) {
	dec_point = typeof dec_point !== 'undefined' ? dec_point : '.';
	thousands_sep = typeof thousands_sep !== 'undefined' ? thousands_sep : ',';
	var parts = this.toFixed(decimals).split('.');
	parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);
	return parts.join(dec_point);
}

var OnlyFloat = function (obj, uLimit = null, lLimit = null) {
	if (/^-?\d*[.,]?\d*$/.test(obj.value)) {
		if (uLimit != null && obj.value > uLimit) { obj.value = uLimit; }
		if (lLimit != null && parseFloat(obj.value) < lLimit) { obj.value = lLimit; }
		obj.oldValue = obj.value;
		obj.oldSelectionStart = obj.selectionStart;
		obj.oldSelectionEnd = obj.selectionEnd;
	} else if (obj.hasOwnProperty("oldValue")) {
		obj.value = obj.oldValue;
		obj.setSelectionRange(obj.oldSelectionStart, obj.oldSelectionEnd);
	} else {
		obj.value = "";
	}
}