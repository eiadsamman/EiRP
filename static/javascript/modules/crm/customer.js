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
		<div class="gremium limit-width">
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
		</div>`;
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
			<div class="gremium limit-width">
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
			</div>`;
	}
}


export class Post extends View {
	splashscreen(target, url, title, data) {
		target.innerHTML = `
		<div class="gremium limit-width">
			<header style="position:sticky;">
				<a style="pointer-events: none;" class="previous" data-role="previous"></a>
				<h1>${title}</h1><cite></cite>
			</header>
			<article>
				<span class="loadingScreen-placeholderBody"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>
			</article>
		</div>`
	}
	run() {

	}
}