import { default as App, Application, View, Search, List } from '../app.js';
import { Popup } from '../gui/popup.js';

export class CustomList extends List {

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
	vendorSelection = null;
	vendorAttention = null;
	currencySelection = null;
	inputItemValueList = null;
	inputDiscountRate = null;
	inputAddtionalAmount = null;
	inputVatRate = null;

	vendorTicket = null;
	constructor(pana) {
		super();
		this.pana = pana;
		this.id = this.pana.navigator.url;
	}

	splashscreen(target, url, title, data) {
		target.innerHTML = this.splashscreenTemplate(title);
	}

	run() {
		this.vendorTicket = document.getElementById("vendorTicket");
		this.vendorSelection = $("#vendor").slo({
			onselect: (e) => {
				if (e.embeds.legalName) {
					this.vendorTicket.innerHTML = `
						<div><span>Legal Name:</span><span>${e.embeds.legalName}</span></div>
						<div><span>Commercial ID:</span><span>${e.embeds.regNo}</span></div>
						<div><span>Tax ID Number:</span><span>${e.embeds.taxNo}</span></div>
						<div><span>VAT ID Number:</span><span>${e.embeds.vatNo}</span></div>
					`;
				} else {
					this.vendorTicket.innerHTML = ``;
				}
			},
			ondeselect: () => {
				this.vendorTicket.innerHTML = ``;
			}
		});
		this.vendorAttention = $("#attention").slo();
		this.paymentTerm = $("#paymentTerm").slo();
		this.ShippingTerm = $("#ShippingTerm").slo();
		this.currencySelection = $("#currency").slo({
			onselect: (e) => { console.log(e); }
		});
		this.formMaterialList = document.getElementById("formMaterialsList");
		this.buttonPost = document.getElementById("appApplicationPost");

		if (this.buttonPost === undefined || this.buttonPost === null) return;
		this.buttonPost.addEventListener("click", () => {
			this.post();
		});
		this.inputItemValueList = document.querySelectorAll(".itemValue");
		this.inputItemValueList.forEach(element => {
			element.addEventListener("input", () => { this.calculate() });
			element.addEventListener("change", () => { this.calculate() });
		});

		this.inputDiscountRate = document.getElementById("appDiscount");
		this.inputDiscountRate.addEventListener("input", () => { this.calculate() });
		this.inputDiscountRate.addEventListener("change", () => { this.calculate() });

		this.inputAddtionalAmount = document.getElementById("appAdditionalAmount");
		this.inputAddtionalAmount.addEventListener("input", () => { this.calculate() });
		this.inputAddtionalAmount.addEventListener("change", () => { this.calculate() });

		this.inputVatRate = document.getElementById("appVat");
	}

	parseFloatCustom(number) {
		if (number.trim() == "") {
			return 0;
		}
		if (!isNaN(parseFloat(number))) {
			return parseFloat(number);
		}
		return false;
	}

	calculate() {
		let total = 0;
		this.inputItemValueList.forEach(e => {
			let quantity = e.dataset.quantity;

			if (!isNaN(parseFloat(e.value)) && !isNaN(parseFloat(quantity))) {
				total += parseFloat(e.value) * parseFloat(quantity);
			}
		});
		document.getElementById("appSubtotal").value = Application.numberFormat(total, 2, ".", ",");
		let discountRate = this.parseFloatCustom(this.inputDiscountRate.value);
		total = discountRate !== false && discountRate >= 0 && discountRate < 100 ? total * (1 - discountRate / 100) : 0;

		let addtionalAmount = this.parseFloatCustom(this.inputAddtionalAmount.value);
		total = addtionalAmount !== false && addtionalAmount >= 0 ? total + addtionalAmount : 0;

		document.getElementById("appTotal").value = Application.numberFormat(total, 2, ".", ",");
		let vatRate = this.parseFloatCustom(this.inputVatRate.dataset.value ? this.inputVatRate.dataset.value : 0);
		total = vatRate !== false && vatRate >= 0 ? total * (1 + vatRate / 100) : 0;

		document.getElementById("appGrand").value = Application.numberFormat(total, 2, ".", ",");
	}

	post() {

		const formData = new FormData(this.formMaterialList);
		formData.append("method", "post");
		formData.append("vendor", this.vendorSelection.get()[0].id);
		formData.append("currency", this.currencySelection.get()[0].id);
		formData.append("paymentTerm", this.paymentTerm.get()[0].id);
		formData.append("shippingTerm", this.ShippingTerm.get()[0].id);
		formData.append("comments", document.getElementsByName("comments")[0].value);
		formData.append("docId", document.getElementById("docId").value);
		formData.append("docHash", document.getElementById("docHash").value);

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
			return Promise.reject(response);
		}).then(res => {
			if (res.result) {
				messagesys.success("Quotation posted successfully");
				console.log(res.forward)
				this.pana.register(res.forward, { "id": res.insert_id });
				this.pana.navigator.pushState();
				this.pana.run();
			} else {
				if (res.errno == 233003) {
					this.vendorSelection.focus()
				} else if (res.errno == 233004) {
					this.currencySelection.focus()
				} else if (res.errno == 233006) {
					this.paymentTerm.focus();
				} else if (res.errno == 233007) {
					this.ShippingTerm.focus();
				}
				messagesys.failure(res.error + " " + res.errno);
			}
		}).catch(response => {
			console.log(response);
		});
	}
}

