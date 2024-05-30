export class Application {
	id = "129-j1f2";

	constructor() {
		document.addEventListener("DOMContentLoaded", async () => {
			await this.chunkLoaders();
			this.dispatchEvents();
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
				cache: "default",/* force-cache, reload, default */
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

		Array.from(document.getElementsByClassName("js-input_darkmode-toggle")).forEach((elem) => {
			elem.addEventListener("click", (e) => {
				e.preventDefault();
				toggleThemeMode();
				return false;
			});
		});
	}
}

export class Navigator {
	constructor(init_state, url) {
		this.history_state = init_state;
		this.history_vars = { ...init_state };
		this.url = url;
		/* this.replaceState(); */
		return this;
	}

	onPopState(callable) {
		let self = this;
		window.onpopstate = function (e) {
			if (e.state != null)
				for (const [key, value] of Object.entries(self.history_vars)) {
					if (Object.hasOwn(e.state, key)) {
						self.history_vars[key] = e.state[key];
					}
				}
			callable.call(self, e);
		};
	}

	setProperty(property, value) {
		this.history_state[property] = value;
		this.history_vars[property] = value;
	}

	getProperty(property) {
		return this.history_state[property];
	}

	getVariable(property) {
		return this.history_vars[property];
	}

	pushState() {
		window.history.pushState(this.history_vars, "", this.url + this.uriBuild());
	}

	replaceState() {
		window.history.replaceState(this.history_vars, "", this.url + this.uriBuild());
	}

	replaceVariableState() {
		window.history.replaceState(this.history_vars, "");
	}

	uriBuild() {
		let uri = "";
		let delm = "";
		let served = false;
		for (const [key, value] of Object.entries(this.history_state)) {
			if (value != null) {
				uri += delm + key + "=" + (value == null ? "" : value);
				delm = "&";
				served = true;
			}
		}
		return (served ? "/?" : "") + uri;
	}

}


const App = new Application();
export default App;

