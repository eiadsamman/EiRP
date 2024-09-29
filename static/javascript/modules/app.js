import Forex from "./finance/forex.js";
import Account from "./finance/account.js";
import Currency from "./finance/currency.js";

const App = {
	ID: 0,
	User: {
		id: null,
		photo: null,
		initials: null,
	},
	Instance: null,
	Account: null,
	Title: null,
	BaseURL: null
}

export class Application {
	id = null;
	forex = null;
	page = {}
	assosiatedAccounts = [];

	constructor(id, apptitle, appbaseurl, pageid, pagedir) {
		App.ID = id;
		App.BaseURL = appbaseurl;
		App.Title = apptitle;

		this.id = id;
		this.page.id = pageid;
		this.page.dir = pagedir;

		document.addEventListener("DOMContentLoaded", async () => {
			await this.chunkLoaders();
			this.dispatchEvents();
		});
		this.forex = new Forex(this.id);
		this.loadAssosiatedAccount();
	}

	userColorCode(userId) {
		return "hsl(" + (userId * 10 % 360) + ", 75%, 50%)";
	}

	set pageId(id) {
		this.page['id'] = id;
	}
	set pageDir(dir) {
		this.page['dir'] = dir;
		if (document.getElementById("jqroot_sec")) {
			document.getElementById("jqroot_sec").href = dir + "/?--sys_sel-change=account";
		}
		document.getElementById("company-menu-slo").dataset.url = dir;
		document.getElementById("account-menu-slo").dataset.url = dir;

		let accItem = document.getElementById("menu-account-selection").querySelectorAll("a[data-account_id]");
		accItem.forEach(element => { element.href = dir + "/?--sys_sel-change=account_commit&i=" + element.dataset.account_id; });

		accItem = document.getElementById("menu-company-selection").querySelectorAll("a[data-company_id]");
		accItem.forEach(element => { element.href = dir + "/?--sys_sel-change=company_commit&i=" + element.dataset.company_id; });

		accItem = document.querySelectorAll(".toggleLightMode");
		accItem.forEach(element => { element.href = dir; });

	}

	loadAssosiatedAccount() {
		fetch('_/UserAssosiatedAccounts/json/' + this.id + '/json_UserAssosiatedAccounts.a', {
			method: 'GET',
			cache: "default", /* force-cache, reload, default */
			mode: "same-origin",
			headers: {
				'Accept': "application/json",
				"Content-Type": "application/json",
			},
		})
			.then(response => response.json())
			.then((payload) => {
				payload.forEach(account => {
					this.assosiatedAccounts.push(new Account(
						account.company,
						account.id,
						account.name,
						new Currency(account.currency.id, account.currency.name, account.currency.shortname, account.currency.symbol)
					));
				});
			}).catch((err) => {
			});
	}
	async chunkLoaders() {
		let chunkElements = document.querySelectorAll("[data-chunk_source]");
		await Promise.all([...chunkElements].map(async (el) => {
			let url = (el.dataset.chunk_source);
			let content_type = (el.dataset.content_type);
			await this.chunkFetch(el, url, content_type);
		}));
	}
	chunkFetch(sourceObject, url, content_type) {
		let isTypeJson = content_type == "json" ? true : false;
		return new Promise((resolve) => {
			fetch(url, {
				method: 'GET',
				cache: "default", /* force-cache, reload, default */
				mode: "same-origin",
				headers: {
					'Accept': isTypeJson ? "application/json" : "text/html",
					"Content-Type": isTypeJson ? "application/json" : "text/html",
				},
			})
				.then(response => response.text())
				.then((payload) => {
					if (isTypeJson) {
					} else {
						sourceObject.innerHTML = (payload);
					}
					resolve(url)
				});
		});
	}
	static numberFormat(number, decimals, dec_point, thousands_sep) {
		dec_point = typeof dec_point !== 'undefined' ? dec_point : '.';
		thousands_sep = typeof thousands_sep !== 'undefined' ? thousands_sep : ',';
		var parts = Number(number).toFixed(decimals).split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);
		return parts.join(dec_point);
	};
	dispatchEvents() {
		document.getElementById("header-menu")?.addEventListener("click", (e) => {
			if (e.target.tagName === "A")
				return true;
			if (e.target.tagName === "B") {
				let menuContainer = e.target.nextElementSibling;
				if (menuContainer.tagName === "DIV") {
					menuContainer.style.display = menuContainer.style.display == "block" ? "none" : "block";
				}
			}
		});

		Array.from(document.getElementsByClassName("toggleLightMode")).forEach((elem) => {
			elem.addEventListener("click", (e) => {
				e.preventDefault();
				toggleThemeMode();
				return false;
			});
		});
	}
}

export class Navigator {
	constructor(state, url) {
		this.state = state;
		this.url = url;
		return this;
	}

	onPopState(callable) {
		let self = this;
		window.onpopstate = function (e) {
			this.state = e.state;
			callable.call(self, e);
		};
	}

	setProperty(property, value) {
		this.state[property] = value;
	}

	getProperty(property) {
		return this.state[property];
	}

	pushState() {
		window.history.pushState({ ...this.state, ":url": this.url }, "", this.url + this.uriBuild());
	}

	replaceState() {
		window.history.replaceState({ ...this.state, ":url": this.url }, "", this.url + this.uriBuild());
	}

	stampState() {
		window.history.replaceState({ ...this.state, ":url": this.url }, "");
	}

	uriBuild() {
		let uri = "";
		let delm = "";
		let served = false;
		for (const [key, value] of Object.entries(this.state)) {
			if (key.substring(0, 1) != "_" && key.substring(0, 1) != ":" && value != null) {
				uri += delm + key + "=" + (value == null ? "" : value);
				delm = "&";
				served = true;
			}
		}
		return (served ? "/?" : "") + uri;
	}

};


export class View {
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


export class Search extends View {
	pana = null;
	postUrl = "";

	constructor(pana) {
		super();
		this.pana = pana;
		this.id = this.pana.navigator.url;
		this.searchFrom = null;
	}

	run() {

	}

	post() {
		const data = {};
		var elements = this.searchFrom.elements;
		/**
		 * TODO: SOME
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

export class List extends View {
	pana = null;
	busy = false;
	totalPages = 1;
	currentPage = 1;
	totalPages = 0;
	recordsCount = 0;
	recordsSum = 0;
	latency = null;
	navOutput = null;
	navNext = null;
	navPrev = null;
	navTotal = null;
	navPages = null;
	searchFields = ["statement-id", "beneficiary", "description", "date-start", "date-end", "category"];


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

	run(directAccess = false) {

		this.slo_page_current = $("#js-input_page-current").slo({
			onselect: (e) => {
				this.currentPage = parseInt(e.key);
				this.currentPage = this.currentPage <= 0 ? 1 : this.currentPage;
				this.pana.navigator.setProperty("page", this.currentPage);
				this.pana.navigator.pushState();
				this.fetch();
			}
		});

		['Output', 'Next', 'Prev', 'Total', 'Entries', 'Pages'].forEach(e => {
			this['nav' + e] = document.getElementById('nav' + e);
		});

		this.pana.clearActiveItem();
		this.currentPage = this.pana.navigator.state.page ? parseInt(this.pana.navigator.state.page) : 1;
		this.slo_page_current.set(this.currentPage, this.currentPage);

		this.navPages?.addEventListener("click", () => {
			if (this.totalPages > 0) {
				this.currentPage = parseInt(this.totalPages);
				this.pana.navigator.setProperty("page", this.currentPage);
				this.pana.navigator.pushState();
				this.fetch();
			}
		});

		this.navNext?.addEventListener("click", () => {
			if (this.currentPage >= this.total_pages) { return; };
			this.currentPage += 1;
			this.pana.navigator.setProperty("page", this.currentPage);
			this.pana.navigator.pushState();
			this.slo_page_current.set(this.currentPage, this.currentPage);
			this.navPrev.disabled = false;
			this.fetch()
		});

		this.navPrev?.addEventListener("click", () => {
			if (this.currentPage <= 1) { return; };
			this.currentPage -= 1;
			this.pana.navigator.setProperty("page", this.currentPage);
			this.pana.navigator.pushState();
			this.slo_page_current.set(this.currentPage, this.currentPage)
			this.fetch();
		});
		this.fetch(directAccess);
	}

	splashscreen(target, url, title, data) {
		target.innerHTML = `
			<div class="gremium limit-width">
				<header style="position:sticky;">
				<h1>${title}</h1>
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
				this.navNext.disabled = true;
				this.navPrev.disabled = true;
				this.navPages.disabled = true;
				this.slo_page_current.disable();
			} else if (this.totalPages == 1) {
				this.navNext.disabled = true;
				this.navPrev.disabled = true;
				this.navPages.disabled = false;
				this.slo_page_current.disable();
			} else if (this.totalPages > 1) {
				this.slo_page_current.enable()
				if (this.pana.navigator.getProperty("page") == 0) {
					this.navNext.disabled = true;
				} else if (this.pana.navigator.getProperty("page") >= this.totalPages) {
					this.navNext.disabled = true;
				} else {
					this.navNext.disabled = false;
				}
				this.navPages.disabled = false;
			}
			if (this.navTotal)
				this.navTotal.innerText = this.recordsSum;
			this.navEntries.innerText = Application.numberFormat(parseInt(this.recordsCount), 0, "", ",") + " records";
			this.navPages.value = Application.numberFormat(parseInt(this.totalPages), 0, "", ",");

			window.scroll({
				top: 0,
				behavior: 'smooth'
			});
		}
	}

	generatePlaceholders(count = 20, colspan = 1) {
		this.navOutput.innerHTML = "";
		let tr = null;
		let td = null;
		for (let i = 0; i < count; i++) {
			tr = document.createElement("TR");
			td = document.createElement("TD");
			td.classList.add("placeholder");
			td.setAttribute("colspan", colspan);
			tr.appendChild(td);
			this.navOutput.appendChild(tr);
		}
	}

	fetch(directAccess = false) {
		this.latency = setTimeout(() => {
			this.generatePlaceholders(20, 6);
		}, 500);

		this.navPrev.disabled = this.currentPage == 1;
		this.navNext.disabled = parseInt(this.currentPage) >= this.totalPages;
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
				let dilm = "";
				for (let element in this.pana.navigator.state) {
					if (this.searchFields.includes(element)) {
						stringifyQurey += dilm + element + "=" + this.pana.navigator.state[element];
						dilm = "&";
					}
				};
				let searchButton = document.getElementById("searchButton");
				if (searchButton) {
					searchButton.dataset.href = searchButton.dataset.target + "/?" + stringifyQurey;
					let cancelSearchButton = document.getElementById("cancelSearchButton");
					if (cancelSearchButton) {
						cancelSearchButton.style.display = stringifyQurey != "" ? "block" : "none";
					}

				}

				return response.text();

			}
			return Promise.reject(response);
		}).then(body => {

			if (this.recordsCount == 0) {
				this.navOutput.innerHTML = "";
				let tr = document.createElement("TR");
				let td = document.createElement("TD");
				td.setAttribute("colspan", 6);
				td.innerText = "No statements found...";
				tr.appendChild(td);
				this.navOutput.appendChild(tr);
			} else {
				this.navOutput.innerHTML = body;
				this.pana.praseEvents(this.navOutput);

			}
		});
	}
}

export default App;