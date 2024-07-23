import Forex from "./finance/forex.js";
import Account from "./finance/account.js";
import Currency from "./finance/currency.js";

export class Application {
	id = null;
	forex = null;
	page = {}
	assosiatedAccounts = [];

	constructor(id) {
		this.id = id;
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
	numberFormat(number, decimals, dec_point, thousands_sep) {
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


export default {
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
};