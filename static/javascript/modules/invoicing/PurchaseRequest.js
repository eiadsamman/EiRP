import { default as App, Application, View, Search, List } from '../app.js';
import { Popup } from '../gui/popup.js';

export class CustomList extends List {
}

export class Entry extends View {
	pana = null;
	constructor(pana) {
		super();
		this.pana = pana;
		this.id = this.pana.navigator.url;
	}

	splashscreen(target, url, title, data) {
		target.innerHTML = this.splashscreenTemplate(title);
	}

	run() {

	}
}

export class Post extends View {
	pana = null;
	popupBomSelection = null;
	busy = false;
	materialQuantity = null;
	materialSelection = null;
	materialsList = null;
	formCostcenter = null;
	materialAddButton = null;
	formMaterialAdd = null;

	constructor(pana) {
		super();
		this.pana = pana;
		this.id = this.pana.navigator.url;
	}

	splashscreen(target, url, title, data) {
		target.innerHTML = this.splashscreenTemplate(title);
	}

	run() {
		this.popup = new Popup();
		this.init();
	}

	readDataset = function (payload, dataset) {
		payload.id = dataset.id;
		payload.longId = dataset.longid;
		payload.name = dataset.name;
		payload.unit = dataset.unit;
		payload.type = dataset.type;
		payload.unitsystemid = dataset.unitsystemid;
		payload.unitdefaultid = dataset.unitdefaultid;
		payload.unitdefaultsymbol = dataset.unitdefaultsymbol;
	}

	init() {
		this.materialsList = document.getElementById("materialsList");
		this.materialQuantity = document.getElementById("materialQuantity");
		this.materialAddButton = document.getElementById("materialAddButton");
		this.formMaterialAdd = document.getElementById("formMaterialAdd");
		this.formMaterialList = document.getElementById("formMaterialsList");
		this.buttonPost = document.getElementById("appApplicationPost");
		this.popupBomSelection = new Popup();
		this.formDepartement = $("#po_departement").slo({ names: { 'id': true, 'value': false } });
		this.formCostcenter = $("#formCostcenter").slo();
		this.materialSelection = $("#materialSelection").slo({ onselect: (e) => { /* this.materialQuantity.focus(); */ } });
		this.materialAddButton.addEventListener("click", () => {
			this.materialAdd();
		});
		this.materialQuantity.addEventListener("keydown", (e) => {
			if (e.key == "Enter") {
				this.materialAdd();
			}
		});
		this.buttonPost.addEventListener("click", (event) => {
			event.preventDefault();
			this.post();
		});

		this.formMaterialList.addEventListener("submit", (event) => {
			event.preventDefault();
			return false;
		});

		this.popupBomSelection.addEventListener("submit", (event) => {
			let importStyle = event.target.controlContainer.querySelectorAll("[name=\"importStyle\"]:checked");
			if (importStyle.length > 0)
				importStyle = importStyle[0].value;
			importStyle = isNaN(importStyle) ? 0 : parseInt(importStyle);
			if (importStyle == 1) {
				let mainmat = event.target.controlContainer.querySelector(".mainMaterial");
				if (mainmat != undefined) {
					let payload = {};
					this.readDataset(payload, mainmat.dataset);
					payload.qty = mainmat.dataset.qty;
					this.appendMaterialItem(payload);
				}
				event.target.close();
				this.clearMaterialSelection();
			} else if (importStyle == 2) {
				let mainmat = event.target.controlContainer.querySelector(".mainMaterial");
				if (mainmat != undefined) {
					let matlist = event.target.controlContainer.querySelectorAll(".subMaterial");
					matlist.forEach(element => {
						let payload = {};
						this.readDataset(payload, element.dataset);
						payload.qty = element.dataset.portion * mainmat.dataset.qty;
						this.appendMaterialItem(payload);

					});
				}
				event.target.close();
				this.clearMaterialSelection();
			} else if (importStyle == 3) {
				let mainmat = event.target.controlContainer.querySelector(".mainMaterial");
				let identifier = 0;
				if (mainmat != undefined) {
					let payload = {};
					identifier = Math.floor(Math.random() * 100000);
					this.readDataset(payload, mainmat.dataset);
					payload.qty = mainmat.dataset.qty;
					payload.identifier = identifier;
					/* Append parent material */
					this.appendMaterialItem(payload, identifier);
					let matlist = event.target.controlContainer.querySelectorAll(".subMaterial");
					/* Append children materials */
					matlist.forEach(element => {
						let payload = {};
						this.readDataset(payload, element.dataset);
						payload.qty = 0;
						payload.portion = parseFloat(element.dataset.portion);
						this.appendMaterialItem(payload,
							null,
							{
								identifier: identifier,
								quantity: parseFloat(mainmat.dataset.qty)
							});
					});
				}
				event.target.close();
				this.clearMaterialSelection();
			}
		});
	}

	post() {
		const formData = new FormData(this.formMaterialList);
		formData.append("method", "post");
		formData.append("departement", this.formDepartement.get()[0].id);
		if (this.formCostcenter.get().length > 0)
			formData.append("costcenter", this.formCostcenter.get()[0].id);
		formData.append("title", document.getElementsByName("po_title")[0].value);
		formData.append("comments", document.getElementsByName("po_comments")[0].value);

		fetch(this.formMaterialList.action, {
			method: 'POST',
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			referrerPolicy: "no-referrer",
			headers: {
				"X-Requested-With": "fetch",
				"Application-From": "same",
				'Accept': 'application/json',
			},
			body: formData,
		}).then(response => {
			if (response.ok) return response.json();
			//if (response.ok) return response.text();
			return Promise.reject(response);
		}).then(res => {
			if (res.result) {
				messagesys.success("Material request posted successfully");
				this.pana.register(res.forward, { "id": res.insert_id });
				this.pana.navigator.pushState();
				this.pana.run();
			} else {
				if (res.errno == 901120) {
					this.formCostcenter.focus()
				} else if (res.errno == 230110) {
					this.formDepartement.focus()
				} else if (res.errno == 901110) {
					this.materialSelection.focus();
				}
				messagesys.failure(res.error);
			}
		}).catch(response => {
			console.log(response);
		});
	}


	clearMaterialSelection = function () {
		this.materialSelection.clear();
		this.materialSelection.focus();
		this.materialQuantity.value = "";
	}

	async showMaterialBom(mat, qty) {
		if (this.busy) return;

		this.busy = true;
		const formData = new FormData();
		formData.append("method", "displaybom");
		formData.append("id", mat);
		formData.append("qty", qty);

		let response = await fetch(this.formMaterialAdd.action, {
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
			const payload = await response.text();
			this.popupBomSelection.contentForm({ "title": "Material selection", "submitButton": true }, payload);
			this.popupBomSelection.show();
			let radioItems = this.popupBomSelection.controlContainer.querySelectorAll("[name=\"importStyle\"]");
			radioItems[0].focus();
			this.busy = false;
		}
	}

	updateBomQunatity = function (identifier, inputField) {

		let updated_value = parseFloat(inputField.value);
		let sub_items = this.materialsList.querySelectorAll(`[data-parent="${identifier}"]`);
		sub_items.forEach(element => {
			if (element.dataset.portion != undefined) {
				let portion = parseFloat(element.dataset.portion);
				let quantity = !isNaN(updated_value) && !isNaN(portion) ? Application.numberFormat((updated_value * portion), 2) : "0";
				element.querySelector("span.boundedPartQty").innerText = quantity;
			}
		});
	}

	updateListCounter = function () {
		let counters = this.materialsList.querySelectorAll(".counter>i");
		let sequence = 1;
		counters.forEach(e => {
			e.innerText = sequence;
			sequence++;
		});
	}

	removeMaterialParts = function (identifier) {
		let parts = this.materialsList.querySelectorAll(`[data-parent="${identifier}"]`);
		parts.forEach(e => {
			e.remove();
		});
	}

	appendMaterialItem(payload, root = null, bounded = false) {
		let html = ``;

		let materialRow = document.createElement("MAIN");
		let uri = "_/UnitMeasurment/slo/unique/slo_UnitMeasurment.a?unit=" + payload.unitsystemid;

		materialRow.classList.add("cssc");

		if (bounded != false && bounded.identifier != undefined) {
			materialRow.dataset.parent = bounded.identifier;
			materialRow.dataset.portion = payload.portion;
			materialRow.classList.add("partsElement");
		}



		if (bounded == false) {
			html += root ? `<div></div>` : `<div class="counter"><i></i></div>`;
			html += `<div>
						<input type="hidden" name="material[id][]" value="${payload.id}" />
						<input class="number-field" style="width:90px;" type="text" inputmode="decimal" name="material[qty][]" value="${payload.qty}" />
						<input class="number-field" style="width:60px;text-align:left" name="material[unit][]" data-slo=":LIST" type="text" data-slodefaultid="${payload.unitdefaultid}" value="${payload.unitdefaultsymbol}" data-source="${uri}" />
						<input type="hidden" name="material[extract][]" value="` + (payload.identifier != undefined ? `1` : ``) + `" />
					</div>`;
		} else {
			materialRow.classList.add("partOf");
			let portionOfMain = Application.numberFormat(bounded.quantity * payload.portion, 2);
			html += `<div class="counter"><i></i></div>`;
			html += `<div style="text-align:right;">
						<span class="boundedPartQty">${portionOfMain}</span> <span>${payload.unitdefaultsymbol}</span>
					</div>`;
		}

		html += `<div>
				${payload.longId} ${payload.type}<br />
				${payload.name}
				</div>`;


		materialRow.innerHTML = html;
		$(materialRow.querySelector(`[data-slo=":LIST"]`)).slo({ names: { 'id': true, 'value': false } });

		if (root !== null) {
			materialRow.classList.add("partsRoot");

			let idnListender = materialRow.querySelector('input[type="text"][name="material\\[qty\\]\\[\\]"]');
			if (idnListender !== undefined && idnListender !== null && parseInt(idnListender.value) > 0) {
				idnListender.addEventListener("change", (e) => { this.updateBomQunatity(root, e.target); });
				idnListender.addEventListener("input", (e) => { this.updateBomQunatity(root, e.target); });
			}
		}




		let removeButton = document.createElement("DIV");
		if (bounded == false) {
			removeButton.classList.add("control");
			removeButton.classList.add("noselect");
			if (root) {
				removeButton.dataset.identifier = root;
			}
			removeButton.innerHTML = `<button type="button" class="delete" />`;
			removeButton.addEventListener("click", (e) => {
				if (e.currentTarget.dataset.identifier != undefined) {
					this.removeMaterialParts(e.currentTarget.dataset.identifier);
				}
				materialRow.remove();
				this.updateListCounter();
			});
		}
		materialRow.append(removeButton);

		this.materialsList.append(materialRow);
		this.updateListCounter();
	}

	async materialAdd() {
		if (this.busy) return;

		let materialId = parseInt(materialSelection.slo.get().id);
		let materialQty = parseInt(materialQuantity.value);
		let response = null;
		if (isNaN(materialId) || isNaN(materialQty)) {
			messagesys.failure("Material item and Quantity are required");
			return;
		}

		this.busy = true;
		const formData = new FormData(this.formMaterialAdd);
		formData.append("method", "checkitem");
		response = await fetch(this.formMaterialAdd.action, {
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

		this.busy = false;
		if (response.ok) {
			const payload = await response.json();

			this.busy = false;
			if (payload.result == 2) {
				this.appendMaterialItem(payload)
				this.clearMaterialSelection();
			} else if (payload.result == 1) {
				this.showMaterialBom(this.materialSelection.get()[0].id, this.materialQuantity.value);
			} else {
				messagesys.failure("Adding material failed, material not found");
			}
		}
	}
}