import { Navigator } from "./app.js";
import Route from "../../../route";
import App from "./app.js";
export class PaNa {
	constructor() {
		this.itemPerRequest = 20;
		this.navigator = new Navigator({}, '');
		this.panelVisible = true;
		this.classList = [];
		this.panelBuilderModule = null;
		this.loadedModule = Array();
		this.runningModule = {
			"name": null,
			"module": null,
			"instance": null,
		};

		this.runtime = new Object();
		this.runtime.isLoading = false;
		this.runtime.isFinished = false;
		this.runtime.busy = false;
		this.runtime.totalPages = 1;
		this.runtime.currentPage = 1;
		this.runtime.activeItem = null;
		this.runtime.scrollArea = document.getElementById("pana-Scroll");
		this.runtime.container = document.getElementById("pana-Window");
		this.runtime.outputSide = document.getElementById("pana-Side");
		this.runtime.outputScreen = document.getElementById("pana-Body");
		this.runtime.informative = document.getElementById("pana-Informative").querySelector("div");

		this.latencyTimer = null;
		this.latency = 200;
		this.runtime.scrollArea.addEventListener("scroll", () => {
			if (this.checkAvailability() &&
				this.runtime.scrollArea.scrollHeight - this.runtime.scrollArea.scrollTop <= this.runtime.scrollArea.clientHeight * 1.2
			) {
				this.runtime.currentPage += 1;
				this.sidePanelLoader();
			}
		});
		window.addEventListener("keydown", (e) => {
			if (e.key == "F8") {
				this.fetchNewItems();
			}
		});

		return this;
	}

	onclick = function (event) {
		this.register(event.dataset.href, { "id": event.dataset.listitem_id });
		this.navigator.pushState();
		this.run();
	}

	highlightItemById = function (id) {
		if (id != undefined) {
			let listitem = this.runtime.scrollArea.querySelector(`.panel-item[data-listitem_id="${id}"]`);
			if (listitem) {
				this.clearActiveItem();
				this.setActiveItem(listitem);
			}
		}
	}

	init = function (url, getRequest) {
		this.register(url, getRequest);
		this.navigator.stampState();

		this.navigator.onPopState((event) => {
			this.navigator.url = event.state[':url'];
			this.navigator.state = event.state;
			this.navigator.scroll = parseFloat(event.state[':scroll']);

			this.highlightItemById(event.state.id);
			if (this.runningModule.instance && this.runningModule.instance.id == event.state[':url'] && typeof this.runningModule.instance['onPopState'] == "function") {
				if (typeof this.runningModule.instance.onPopState == "function") {
					this.runningModule.instance.onPopState();
				}
			} else {
				this.run();
			}
		});

		this.runtime.scrollArea.tabIndex = 0;
		this.runtime.scrollArea.autofocus = true;
		this.runtime.scrollArea.addEventListener("keydown", (e) => {
			if (e.key == "ArrowDown") {
				e.preventDefault();
				if (this.runtime.activeItem != null && !this.runtime.busy) {
					let listitem = this.runtime.scrollArea.querySelector(`.panel-item[data-listitem_id="${this.runtime.activeItem.dataset.listitem_id}"]`);
					if (listitem) {
						let nextElementSibling = listitem.nextElementSibling;
						if (nextElementSibling && nextElementSibling.dataset != undefined && nextElementSibling.dataset.listitem_id != undefined) {
							this.clearActiveItem()
							this.setActiveItem(nextElementSibling);
							this.onclick(nextElementSibling);
							nextElementSibling.scrollIntoView({
								behavior: "smooth",
								block: "nearest"
							});
						}
					}
				}
				return false;
			} else if (e.key == "ArrowUp") {
				e.preventDefault();
				if (this.runtime.activeItem != null && !this.runtime.busy) {
					let listitem = this.runtime.scrollArea.querySelector(`.panel-item[data-listitem_id="${this.runtime.activeItem.dataset.listitem_id}"]`);
					if (listitem) {
						let previousElementSibling = listitem.previousElementSibling;
						if (previousElementSibling && previousElementSibling.dataset != undefined && previousElementSibling.dataset.listitem_id != undefined) {
							this.clearActiveItem()
							this.setActiveItem(previousElementSibling);
							this.onclick(previousElementSibling);
							previousElementSibling.scrollIntoView({
								behavior: "smooth",
								block: "nearest"
							});

						}
					}
				}
				return false;
			} else if (e.key == "Enter") {
				e.preventDefault()
				let activeElement = document.activeElement;
				if (activeElement.classList.contains("panel-item")) {
					this.clearActiveItem()
					this.setActiveItem(activeElement);
					this.onclick(activeElement);
					activeElement.scrollIntoView({
						behavior: "smooth",
						block: "nearest"
					});
				}
			}
		});
	}

	sidePanelVisibility(visible) {
		this.panelVisible = visible;
		if (visible) {
			this.runtime.outputSide.classList.remove("hide")
		} else {
			this.runtime.outputSide.classList.add("hide")
		}
	}

	getFreePlaceholder = function () {
		let freePlaceholder = this.runtime.scrollArea.querySelector(".panel-item.place-holder");
		if (freePlaceholder == null) {
			return false;
		} else {
			return freePlaceholder;
		}
	}

	clearEmptyPlaceholders = function () {
		let emptyPlaceholders = this.runtime.scrollArea.querySelectorAll(".panel-item.place-holder");
		let emptyPlaceholdersCount = emptyPlaceholders.length;
		emptyPlaceholders.forEach(element => {
			element.remove();
		});
		return emptyPlaceholdersCount;
	}

	getFile = function (url) {
		let output = false;
		for (const [grpkey, grpvalue] of Object.entries(Route)) {
			for (const [key, value] of Object.entries(grpvalue.modules)) {
				if (key == url) {
					output = {
						"key": grpkey,
						"id": value[0],
						"paneltitle": grpvalue.title,
						"panelurl": grpvalue.url,
						"title": value[1],
						"visible": value[2],
						"module": {
							"js": grpvalue.js,
							"class": value[3],
						},
						"assets": grpvalue.assets
					};
				}
			}
		}
		return output;
	}

	sidePanelLoader = function () {
		if (this.runtime.isFinished) {
			this.clearEmptyPlaceholders();
			return;
		}
		//console.log((new Error()).stack?.split("\n")[1]?.trim().split(" ")[1]) 

		this.runtime.informative.innerText = "Loading...";
		this.generatePlaceholders();
		let file = this.getFile(this.navigator.url);
		fetch(file.panelurl, {
			method: 'POST',
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			referrerPolicy: "no-referrer",
			headers: {
				"X-Requested-With": "fetch",
				"Application-From": "same",
				'Accept': 'application/json',
				"Content-Type": "application/json",
			},
			body: JSON.stringify({ ...{ "method": "fetch", "page": this.runtime.currentPage } })
		}).then(response => {
			this.runtime.totalPages = response.headers.has("res_pages") ? response.headers.get("res_pages") : 0;
			document.getElementById("pana-TotalRecords").innerText = (response.headers.has("res_count") ? response.headers.get("res_count") : 0) + " records";

			this.runtime.isLoading = false;
			if (response.ok) return response.text();
			return Promise.reject(response);
		}).then(body => {
			this.clearEmptyPlaceholders();
			this.runtime.container.insertAdjacentHTML('beforeend', body);
			this.assigneEvents();
			this.runtime.isFinished = !this.checkAvailability();
			if (this.runtime.isFinished) {
				this.runtime.informative.innerText = "No more records";
				return;
			}
			if (this.checkAvailability() &&
				this.runtime.scrollArea.scrollHeight > 0 &&
				this.runtime.scrollArea.clientHeight > 0 &&
				this.runtime.scrollArea.scrollHeight <= this.runtime.scrollArea.clientHeight
			) {
				this.runtime.currentPage += 1;
				this.sidePanelLoader();
			}
		}).catch(response => {
			console.log(response);
			messagesys.failure("Application failed to load properly");
		});
	}

	generatePlaceholders = function () {
		if (this.runtime.isLoading) return;
		this.runtime.isLoading = true;
		let content;
		for (let i = 0; i < this.itemPerRequest; i++) {
			content = document.createElement("a");
			content.classList.add("panel-item");
			content.classList.add("place-holder");
			this.runtime.container.append(content);
		}
	}

	prependItem = function (content) {
		let item = document.createElement("a");
		item.classList.add("panel-item");
		item.classList.add("flash");
		if (this.classList instanceof Array) {
			this.classList.forEach(e => {
				item.classList.add(e);
			});
		} else if (this.classList instanceof String) {
			item.classList.add(this.classList);
		}
		item.innerHTML = content;
		this.runtime.container.prepend(item);
		this.assigneEvents();
	}

	setActiveItem = function (item) {
		item.classList.add("active");
		this.runtime.activeItem = item;
	}

	clearActiveItem = function () {
		if (this.runtime.activeItem != null) {
			this.runtime.scrollArea.querySelectorAll(".panel-item.active").forEach(element => {
				element.classList.remove("active");
			});
			this.runtime.activeItem = null;
		}
	}

	assigneEvents = function () {
		let instance = this;
		this.runtime.container.querySelectorAll("a").forEach(e => {
			if (e.dataset.rasied == undefined) {
				e.dataset.rasied = 1;
				e.addEventListener("click", function (e) {
					e.preventDefault();
					if (instance.runtime.activeItem != null) {
						if (instance.runtime.activeItem.dataset.listitem_id === this.dataset.listitem_id)
							return;
						instance.clearActiveItem();
					}
					instance.setActiveItem(this);
					instance.onclick(this);
					return false;
				});
			}
		});
	}

	checkAvailability = function () {
		return (this.runtime.currentPage < this.runtime.totalPages);
	}

	fetchNewItems = function () {
		console.log("F8")
	}

	register = function (url, data) {
		this.navigator.scroll = window.scrollY;
		this.navigator.stampState();
		this.navigator.url = url;
		this.navigator.state = data;
		App.Instance.pageDir = this.navigator.url;
	}

	loadAssets = function (assets) {
		for (let asset of assets.css) {
			var styles = document.createElement('link');
			styles.rel = 'stylesheet';
			styles.type = 'text/css';
			styles.media = 'screen';
			styles.href = "static/" + asset;
			document.getElementsByTagName('head')[0].appendChild(styles);
		}
	}

	run = async (directAccess = false) => {
		window.scrollTo({ top: 0, behavior: 'smooth' });
		this.runtime.busy = true;
		this.runtime.outputScreen.classList.add("busy");

		let file = this.getFile(this.navigator.url);
		if (file) {
			if (!this.loadedModule.includes(file.key)) {
				this.loadedModule.push(file.key);
				this.loadAssets(file.assets);
			}

			document.title = App.Title + " - " + file.title;
			this.sidePanelVisibility(file.visible);
			if (this.runningModule.name != file.key) {
				this.runningModule.name = file.key;
				const m = await import(file.module.js);
				this.runningModule.module = m;
				this.runtime.totalPages = 1;
				this.runtime.currentPage = 1;
				this.runtime.isFinished = false;
				this.runtime.scrollArea.scrollTo({ top: 0, behavior: 'smooth' });
				this.runtime.container.innerHTML = "";
				document.getElementById("pana-PanelTitle").innerText = file.paneltitle;
				document.getElementById("pana-TotalRecords").innerText = "";
				this.runtime.isLoading = false;
				this.sidePanelLoader();
			}
			this.runningModule.instance = new (this.runningModule.module[file.module.class])(this);
			if (typeof this.runningModule.instance['splashscreen'] == "function") {
				this.latencyTimer = window.setTimeout(() => {
					this.runningModule.instance.splashscreen(this.runtime.outputScreen, this.navigator.url, file.title, this.navigator.state);
				}, this.latency);
			}
			this.fetch(directAccess);
		}
	}

	praseEvents = function (target) {
		target.querySelectorAll("[data-href]").forEach(el => {
			try {
				el.addEventListener("click", (e) => {
					e.preventDefault();

					let urlparts = el.dataset.href.split("?");
					this.navigator.state = {};
					this.navigator.url = urlparts[0].replace(/^\/+|\/+$/g, '');
					App.Instance.pageDir = this.navigator.url;
					if (urlparts.length > 1) {
						urlparts[1] = urlparts[1].replace(/^\/+/g, '');
						let search = new URLSearchParams(urlparts[1]);
						for (const [key, value] of search) {
							this.navigator.state[key] = value;
						}
					}

					this.run();
					this.navigator.pushState();
					return false;
				}, { once: true });
			} catch (e) {
				console.log(e)
			}
		});
	}

	fetch = function (directAccess = false) {
		let formData = new FormData();
		for (var key in this.navigator.state)
			formData.append(key, this.navigator.state[key]);
		fetch(this.navigator.url, {
			method: 'POST',
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			referrerPolicy: "no-referrer",
			headers: { "X-Requested-With": "fetch" },
			body: formData
		}).then(response => {
			this.runtime.busy = false;
			if (this.latencyTimer) clearTimeout(this.latencyTimer);
			if (response.ok) return response.text();
			return Promise.reject(response);
		}).then(body => {
			this.runtime.outputScreen.innerHTML = body;
			this.runtime.outputScreen.classList.remove("busy");
			this.praseEvents(this.runtime.outputScreen);
			if (this.runningModule.instance) this.runningModule.instance.run(directAccess);
		}).catch(response => {
			this.runtime.busy = false;
			this.runtime.outputScreen.classList.remove("busy");
			messagesys.failure("Server response `" + response.statusText + "`");
		});
	}
}
