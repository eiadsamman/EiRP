import { Navigator } from "./app.js";
import App from "./app.js";
export class PaNa {
	constructor(id) {
		this.sidePanelUrl = "";
		this.onClickUrl = "";
		this.itemPerRequest = 20;
		this.navigator = new Navigator({}, '');
		this.module = null;
		this.panelVisible = true;
		this.scope = {};
		this.classList = [];


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
		this.latency = 0;
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

	onclick = function () {
	}

	listitemHandler = function () {
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

	init = function () {
		this.sidePanelLoader();
		this.navigator.stampState();
		this.navigator.onPopState((event) => {
			this.register(event.state[':url'], event.state);
			this.highlightItemById(event.state.id);


			if (this.module && this.module.id == event.state[':url'] && typeof this.module['onPopState'] == "function") {
				if (typeof this.module.onPopState == "function") {
					this.module.onPopState();
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
						let nextSibling = listitem.nextSibling;
						if (nextSibling && nextSibling.dataset != undefined && nextSibling.dataset.listitem_id != undefined) {
							this.clearActiveItem()
							this.setActiveItem(nextSibling);
							this.onclick(nextSibling);
							nextSibling.scrollIntoView({
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
						let previousSibling = listitem.previousSibling;
						if (previousSibling && previousSibling.dataset != undefined && previousSibling.dataset.listitem_id != undefined) {
							this.clearActiveItem()
							this.setActiveItem(previousSibling);
							this.onclick(previousSibling);
							previousSibling.scrollIntoView({
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

	sidePanelLoader = async () => {
		if (this.runtime.isFinished) {
			this.clearEmptyPlaceholders();
			return;
		}
		this.runtime.informative.innerText = "Loading...";
		this.generatePlaceholders();

		let response = await fetch(this.sidePanelUrl, {
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
		});

		if (response.ok) {
			const payload = await response.json();
			this.runtime.totalPages = parseInt(payload.headers.pages);

			document.getElementById("pana-TotalRecords").innerText = payload.headers.count + " records";

			payload.contents.forEach(element => {
				let freePlaceholder = this.getFreePlaceholder();
				if (freePlaceholder) {
					freePlaceholder.classList.remove("place-holder");
					this.buildItem(freePlaceholder, element);//payload.headers.landing_uri
					this.assigneEvents(freePlaceholder);
				}
			});
			this.runtime.isFinished = !this.checkAvailability() && this.clearEmptyPlaceholders() > 0;
			if (this.runtime.isFinished) {
				this.runtime.informative.innerText = "No more records";
				return;
			}
			if (this.checkAvailability() &&
				this.runtime.scrollArea.scrollHeight > 0 &&
				this.runtime.scrollArea.clientHeight > 0 &&
				(this.runtime.scrollArea.scrollHeight <= this.runtime.scrollArea.clientHeight)) {
				this.runtime.currentPage += 1;
				this.sidePanelLoader();
			}
			this.runtime.isLoading = false;
		}
	}

	generatePlaceholders = function () {
		if (this.runtime.isLoading) return;
		this.runtime.isLoading = true;
		let content;
		for (let i = 0; i < this.itemPerRequest; i++) {
			content = document.createElement("a");
			content.classList.add("panel-item");
			content.classList.add("place-holder");
			content.classList.add(...this.classList);
			this.runtime.container.append(content);
		}
	}

	prependItem = function (content) {
		let item = document.createElement("a");
		item.classList.add("panel-item");
		item.classList.add("flash");
		item.classList.add(...this.classList);
		this.runtime.container.prepend(item);
		this.buildItem(item, content);
		this.assigneEvents(item);
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

	assigneEvents = function (element) {
		let instance = this;
		element.addEventListener("click", function (e) {
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

	buildItem = function (obj, data = {}) {
		obj.dataset.listitem_id = data.id;
		obj.innerHTML = (this.listitemHandler(data));
		obj.href = this.onClickUrl + "/?id=" + data.id;
	}

	checkAvailability = function () {
		return (this.runtime.currentPage < this.runtime.totalPages);
	}

	fetchNewItems = function () {
		let fisrtNode = this.runtime.container.firstElementChild;
		if (fisrtNode && fisrtNode.dataset.listitem_id) {

		}
	}

	register = function (url, data) {
		this.navigator.url = url;
		this.navigator.state = data;
		App.Instance.pageDir = this.navigator.url;
	}

	run = function () {

		let url = this.navigator.url;
		this.module = null;
		this.runtime.busy = true;
		this.runtime.outputScreen.classList.add("busy");

		/* Import page custome module file */
		if (this.scope[url] !== undefined) {
			document.title = App.Title + " - " + this.scope[url].title;
			this.sidePanelVisibility(this.scope[url].side);
			if (this.scope[url].module != undefined && this.scope[url].import != undefined && this.scope[url].module != null) {
				import(this.scope[url].import).then((m) => {
					this.module = new (m[this.scope[url].module])(this);
					if (typeof this.module['splashscreen'] == "function") {
						this.latencyTimer = window.setTimeout(() => {
							this.module.splashscreen(this.runtime.outputScreen, url, this.scope[url].title, this.navigator.state);
						}, this.latency);
					}
					this.fetch();
				}).catch(e => {
					messagesys.failure('Loading application modules failed');
				});
			}
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
				});
			} catch (e) {
			}
		});
	}

	fetch = function () {
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
			if (this.module) this.module.run();
		}).catch(response => {
			this.runtime.busy = false;
			this.runtime.outputScreen.classList.remove("busy");
			messagesys.failure("Server response `" + response.statusText + "`");
		});
	}
}
