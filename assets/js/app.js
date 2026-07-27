/* =====================================================================
   app.js - Online Billing System
   ---------------------------------------------------------------------
   Ito ang JavaScript na kumokontrol sa mga button:
   Find, Total, Bill, E-Mail, Print, at Clear.

   Ang totoong komputasyon ay ginagawa ng PHP (OOP) sa /api folder.
   Ang trabaho lang ng file na ito ay:
     1. kunin ang inilagay ng user
     2. ipadala sa PHP
     3. ipakita ang sagot sa screen
   ===================================================================== */


/* ---------------------------------------------------------------------
   BAHAGI 1: Mga tulong na function
   --------------------------------------------------------------------- */

/**
 * Gawing pera ang numero.
 * Halimbawa: 515.5  =>  "₱515.56"
 */
function peso(amount) {
    let number = Number(amount);
    if (isNaN(number)) {
        number = 0;
    }
    return "₱" + number.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Ipadala ang datos sa PHP at hintayin ang sagot.
 * Ang sagot ay nasa JSON format.
 */
async function sendToServer(url, data) {
    const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    });

    try {
        return await response.json();
    } catch (error) {
        return { ok: false, error: "May problema sa server." };
    }
}


/* ---------------------------------------------------------------------
   BAHAGI 2: Pagkuha ng inilagay ng user
   --------------------------------------------------------------------- */

/**
 * Kunin ang detalye ng customer mula sa tatlong text box.
 */
function getCustomer() {
    return {
        name:         document.getElementById("customerName").value.trim(),
        contact:      document.getElementById("contactNumber").value.trim(),
        order_number: document.getElementById("orderNumber").value.trim()
    };
}

/**
 * Kunin ang lahat ng produktong may quantity na mas malaki sa 0.
 */
function getCart() {
    const cart     = [];
    const qtyBoxes = document.querySelectorAll(".qty");

    for (let i = 0; i < qtyBoxes.length; i++) {
        const box      = qtyBoxes[i];
        const quantity = parseInt(box.value, 10);

        // Isama lang kung may binili talaga.
        if (quantity > 0) {
            cart.push({
                product_id: Number(box.dataset.productId),
                category:   box.dataset.category,
                quantity:   quantity
            });
        }
    }

    return cart;
}


/* ---------------------------------------------------------------------
   BAHAGI 3: Pagpapakita ng mensahe at pag-highlight ng field
   --------------------------------------------------------------------- */

// Ang tatlong required na text box.
const CUSTOMER_BOXES = ["customerName", "contactNumber", "orderNumber"];

/**
 * Gawing pula ang isang text box (o alisin ang pula).
 */
function markInvalid(elementId, isInvalid) {
    const box = document.getElementById(elementId);
    if (isInvalid) {
        box.classList.add("invalid");
    } else {
        box.classList.remove("invalid");
    }
}

/**
 * Alisin ang pula sa lahat ng customer text box.
 */
function clearHighlights() {
    for (let i = 0; i < CUSTOMER_BOXES.length; i++) {
        markInvalid(CUSTOMER_BOXES[i], false);
    }
}

/**
 * Magpakita ng mensahe sa ibabaw ng mga button.
 * type = "err" (pula) o "ok" (berde)
 */
function showMessage(text, type) {
    const notice = document.getElementById("formNotice");

    notice.textContent = text;
    notice.className   = "form-notice";

    if (text != "") {
        notice.classList.add(type);

        // Para umuga nang bahagya ang mensahe at mapansin.
        notice.classList.remove("shake");
        void notice.offsetWidth;
        notice.classList.add("shake");
    }
}

/** Alisin ang mensahe. */
function clearMessage() {
    showMessage("", "err");
}


/* ---------------------------------------------------------------------
   BAHAGI 4: Validation ng quantity (numero lang, walang negatibo)
   --------------------------------------------------------------------- */

/**
 * Linisin ang laman ng quantity box.
 * Tinatanggal ang letra, minus sign, at tuldok.
 */
function cleanQuantity(box) {
    let value = box.value;

    // Alisin ang lahat maliban sa numero 0-9.
    value = value.replace(/[^0-9]/g, "");

    // Alisin ang sobrang zero sa unahan (halimbawa: 007 => 7).
    value = value.replace(/^0+(?=[0-9])/, "");

    // Huwag hayaang lumampas sa 9999.
    if (value != "" && Number(value) > 9999) {
        value = "9999";
    }

    box.value = value;
}

/**
 * Ikabit ang validation sa lahat ng quantity box.
 */
function setupQuantityBoxes() {
    const qtyBoxes = document.querySelectorAll(".qty");

    for (let i = 0; i < qtyBoxes.length; i++) {
        const box = qtyBoxes[i];

        // 1) Harangin agad ang bawal na pindot (letra, minus, tuldok).
        box.addEventListener("keydown", function (event) {
            const allowedKeys = [
                "Backspace", "Delete", "Tab", "Enter", "Home", "End",
                "ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown"
            ];

            // Payagan ang mga special key at ang Ctrl+C / Ctrl+V.
            if (allowedKeys.includes(event.key) || event.ctrlKey) {
                return;
            }

            // Payagan lang ang numero 0 hanggang 9.
            if (event.key < "0" || event.key > "9") {
                event.preventDefault();
            }
        });

        // 2) Linisin ang laman tuwing may binabago.
        box.addEventListener("input", function () {
            cleanQuantity(box);
        });

        // 3) Kapag walang laman pagkaalis ng cursor, gawing 0.
        box.addEventListener("blur", function () {
            if (box.value == "") {
                box.value = "0";
            }
        });
    }
}


/* ---------------------------------------------------------------------
   BAHAGI 5: Pagpapakita ng resulta sa Bill Transactions
   --------------------------------------------------------------------- */

/**
 * Ilagay sa screen ang resulta ng komputasyon.
 */
function showSummary(summary) {
    const totals = summary.category_totals;
    const taxes  = summary.category_taxes;

    // Total ng bawat kategorya
    document.getElementById("totBeauty").textContent   = peso(totals["Beauty & Personal Care"]);
    document.getElementById("totGrocery").textContent  = peso(totals["Grocery"]);
    document.getElementById("totBeverage").textContent = peso(totals["Beverages"]);

    // Buwis ng bawat kategorya
    document.getElementById("taxBeauty").textContent   = peso(taxes["Beauty & Personal Care"]);
    document.getElementById("taxGrocery").textContent  = peso(taxes["Grocery"]);
    document.getElementById("taxBeverage").textContent = peso(taxes["Beverages"]);

    // Kabuuang komputasyon
    document.getElementById("subtotal").textContent   = peso(summary.subtotal);
    document.getElementById("totalTax").textContent   = peso(summary.total_tax);
    document.getElementById("grandTotal").textContent = peso(summary.grand_total);
}


/* ---------------------------------------------------------------------
   BAHAGI 6: Order History (mga lumang order na nakita ng FIND)
   --------------------------------------------------------------------- */

/**
 * Gawing madaling basahin ang petsa mula sa database.
 * Halimbawa: "2026-07-24 15:08:00"  =>  "Jul 24, 2026 3:08 PM"
 */
function readableDate(text) {
    if (!text) {
        return "";
    }

    // Ang Safari ay ayaw sa espasyo, kaya gagawin nating "T".
    const date = new Date(String(text).replace(" ", "T"));

    if (isNaN(date.getTime())) {
        return text;   // hindi mabasa - ipakita na lang ang orihinal
    }

    return date.toLocaleString("en-PH", {
        year:   "numeric",
        month:  "short",
        day:    "numeric",
        hour:   "numeric",
        minute: "2-digit"
    });
}

/** Itago ang Order History card. */
function hideHistory() {
    document.getElementById("orderHistory").classList.add("hidden");
    document.getElementById("historyList").innerHTML = "";
    document.getElementById("historyFor").textContent = "";
}

/**
 * Gumawa ng isang kahon para sa ISANG lumang order.
 */
function buildHistoryCard(order) {
    const box = document.createElement("div");
    box.className = "history-item";

    // ---- Ulo: Order # at petsa ----
    const head = document.createElement("div");
    head.className = "history-head";

    const label = document.createElement("strong");
    label.textContent = "Order #" + order.order_id;

    const when = document.createElement("span");
    when.className   = "history-date";
    when.textContent = readableDate(order.created_at);

    head.appendChild(label);
    head.appendChild(when);
    box.appendChild(head);

    // ---- Talahanayan ng mga binili ----
    const table = document.createElement("table");
    table.className = "history-table";
    table.innerHTML =
        "<thead><tr><th>Product</th><th>Category</th><th>Qty</th><th>Amount</th></tr></thead>";

    const body = document.createElement("tbody");

    for (let i = 0; i < order.items.length; i++) {
        const item = order.items[i];
        const row  = document.createElement("tr");

        const name = document.createElement("td");
        name.textContent = item.product_name;

        const category = document.createElement("td");
        category.textContent = item.category;

        const qty = document.createElement("td");
        qty.className   = "num";
        qty.textContent = item.quantity;

        const amount = document.createElement("td");
        amount.className   = "num";
        amount.textContent = peso(item.total_price);

        row.appendChild(name);
        row.appendChild(category);
        row.appendChild(qty);
        row.appendChild(amount);
        body.appendChild(row);
    }

    table.appendChild(body);
    box.appendChild(table);

    // ---- Buod ng order ----
    const totals = document.createElement("div");
    totals.className = "history-totals";
    totals.innerHTML =
        "<span>Subtotal: <b>" + peso(order.subtotal) + "</b></span>" +
        "<span>Tax: <b>" + peso(order.total_tax) + "</b></span>" +
        '<span class="grand">Grand Total: <b>' + peso(order.grand_total) + "</b></span>";

    box.appendChild(totals);

    return box;
}

/**
 * Ipakita ang lahat ng lumang order ng nakitang customer.
 */
function showHistory(customer, orders) {
    const card = document.getElementById("orderHistory");
    const list = document.getElementById("historyList");
    const info = document.getElementById("historyFor");

    list.innerHTML = "";

    if (!orders) {
        orders = [];
    }

    // Sino ang customer at ilan ang order niya.
    if (orders.length == 0) {
        info.textContent = customer.customer_name + " — no past orders yet.";
    } else if (orders.length == 1) {
        info.textContent = customer.customer_name + " — 1 past order.";
    } else {
        info.textContent = customer.customer_name + " — " + orders.length + " past orders.";
    }

    for (let i = 0; i < orders.length; i++) {
        list.appendChild(buildHistoryCard(orders[i]));
    }

    card.classList.remove("hidden");
}


/* ---------------------------------------------------------------------
   BAHAGI 7: Ang mga BUTTON
   --------------------------------------------------------------------- */

/**
 * FIND BUTTON
 * Naghahanap ng customer gamit ang Contact Number o Order Number,
 * tapos ipinapakita rin ang lahat ng lumang order niya.
 */
async function clickFind() {
    const contactBox = document.getElementById("contactNumber");
    const orderBox   = document.getElementById("orderNumber");

    // Alin man ang may laman, iyon ang hahanapin.
    let keyword = contactBox.value.trim();
    if (keyword == "") {
        keyword = orderBox.value.trim();
    }

    // Walang inilagay.
    if (keyword == "") {
        markInvalid("contactNumber", true);
        markInvalid("orderNumber", true);
        showMessage("Enter a Contact Number or Order Number to search.", "err");
        contactBox.focus();
        return;
    }

    // Ipadala sa PHP.
    const result = await sendToServer("api/find_customer.php", { keyword: keyword });

    // Walang nakita.
    if (!result.ok) {
        markInvalid("contactNumber", true);
        markInvalid("orderNumber", true);
        showMessage(result.error, "err");
        hideHistory();
        return;
    }

    // May nakita - punuin ang tatlong text box.
    document.getElementById("customerName").value  = result.customer.customer_name;
    document.getElementById("contactNumber").value = result.customer.contact_number;
    document.getElementById("orderNumber").value   = result.customer.order_number;

    // Ipakita ang mga lumang order niya.
    const orders = result.orders || [];
    showHistory(result.customer, orders);

    clearHighlights();

    if (orders.length == 0) {
        showMessage("✔ Customer found — details loaded. No past orders yet.", "ok");
    } else {
        showMessage("✔ Customer found — details loaded. " + orders.length +
                    " past order" + (orders.length == 1 ? "" : "s") + " below.", "ok");
        document.getElementById("orderHistory").scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    }
}

/**
 * ADD CUSTOMER BUTTON
 * Nagreregister ng bagong customer sa database (kahit wala pang bill).
 */
async function clickAddCustomer() {
    const customer = getCustomer();

    // Tingnan kung kompleto ang tatlong field.
    markInvalid("customerName",  customer.name == "");
    markInvalid("contactNumber", customer.contact == "");
    markInvalid("orderNumber",   customer.order_number == "");

    if (customer.name == "" || customer.contact == "" || customer.order_number == "") {
        showMessage("Please complete Customer Name, Contact Number, and Order Number to add a customer.", "err");
        return;
    }

    const result = await sendToServer("api/add_customer.php", {
        name:         customer.name,
        contact:      customer.contact,
        order_number: customer.order_number
    });

    if (!result.ok) {
        showMessage(result.error, "err");
        return;
    }

    clearHighlights();
    showMessage("✔ " + result.message + " (Customer #" + result.customer.customer_id + ")", "ok");
}

/**
 * TOTAL BUTTON
 * Kinakalkula ang total ng bawat kategorya (hindi pa nagse-save).
 */
async function clickTotal() {
    const cart = getCart();

    if (cart.length == 0) {
        showMessage("Enter at least one quantity first.", "err");
        return null;
    }

    const result = await sendToServer("api/calculate.php", { cart: cart });

    if (!result.ok) {
        showMessage(result.error, "err");
        return null;
    }

    showSummary(result.summary);
    clearMessage();
    return result.summary;
}

/**
 * BILL BUTTON
 * Kinakalkula ang bill AT sini-save sa database.
 */
async function clickBill() {
    const cart = getCart();

    if (cart.length == 0) {
        showMessage("Enter at least one quantity first.", "err");
        return;
    }

    // Tingnan kung kompleto ang detalye ng customer.
    const customer = getCustomer();
    let hasMissing = false;

    markInvalid("customerName", customer.name == "");
    markInvalid("contactNumber", customer.contact == "");
    markInvalid("orderNumber", customer.order_number == "");

    if (customer.name == "" || customer.contact == "" || customer.order_number == "") {
        hasMissing = true;
    }

    if (hasMissing) {
        showMessage("Please complete the highlighted customer fields before billing.", "err");
        return;
    }

    // Ipadala sa PHP para i-save.
    const result = await sendToServer("api/save_order.php", {
        customer: customer,
        cart: cart
    });

    if (!result.ok) {
        showMessage(result.error, "err");
        return;
    }

    showSummary(result.summary);
    clearHighlights();
    showMessage("✔ Bill saved — Order #" + result.order_id, "ok");

    // Kung nakabukas ang Order History, i-refresh para kasama ang bagong order.
    if (!document.getElementById("orderHistory").classList.contains("hidden")) {
        const fresh = await sendToServer("api/find_customer.php", {
            keyword: customer.order_number
        });
        if (fresh.ok) {
            showHistory(fresh.customer, fresh.orders || []);
        }
    }

    // I-scroll pababa para makita ang resulta.
    document.getElementById("billTransactions").scrollIntoView({
        behavior: "smooth",
        block: "center"
    });
}

/**
 * Kunin sa PHP ang teksto ng resibo.
 */
async function getReceiptText() {
    const cart = getCart();

    if (cart.length == 0) {
        showMessage("Enter at least one quantity first.", "err");
        return null;
    }

    const result = await sendToServer("api/email.php", {
        to: "",
        customer: getCustomer(),
        cart: cart
    });

    if (!result.ok) {
        showMessage(result.error, "err");
        return null;
    }

    clearMessage();
    return result.body;
}

/**
 * PRINT BUTTON
 * Nagpapakita ng resibo sa pop-up bago i-print.
 */
async function clickPrint() {
    // I-update muna ang Bill Transactions.
    const summary = await clickTotal();
    if (summary == null) {
        return;
    }

    const receipt = await getReceiptText();
    if (receipt == null) {
        return;
    }

    openModal("Print Receipt", receipt, false);
}

/**
 * E-MAIL BUTTON
 * Nagpapakita ng resibo at ng kahon para sa e-mail address.
 */
async function clickEmail() {
    const receipt = await getReceiptText();
    if (receipt == null) {
        return;
    }

    openModal("E-Mail Receipt", receipt, true);
}

/**
 * SEND BUTTON (nasa loob ng pop-up)
 */
async function clickSendEmail() {
    const emailBox = document.getElementById("emailTo");
    const status   = document.getElementById("emailStatus");
    const sendBtn  = document.getElementById("btnSendEmail");
    const address  = emailBox.value.trim();

    // Tingnan kung tama ang porma ng e-mail address.
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (address == "" || !emailPattern.test(address)) {
        status.textContent = "Please enter a valid e-mail address.";
        status.className   = "email-status err";
        return;
    }

    // ---- Ipakita ang loader habang ipinapadala ----
    sendBtn.disabled  = true;
    sendBtn.textContent = "Sending...";
    status.className  = "email-status load";
    status.innerHTML  = '<span class="email-spinner"></span> Sending receipt to ' + address + " ...";

    let result;
    try {
        result = await sendToServer("api/email.php", {
            to: address,
            customer: getCustomer(),
            cart: getCart()
        });
    } finally {
        // Ibalik ang button kahit anong mangyari.
        sendBtn.disabled    = false;
        sendBtn.textContent = "Send";
    }

    if (result.ok && result.sent) {
        status.textContent = "✔ Receipt sent to " + result.to;
        status.className   = "email-status ok";
    } else {
        // Ipakita ang tunay na dahilan mula sa SMTP kung meron.
        status.textContent = result.error
            ? "Failed to send: " + result.error
            : "Failed to send the receipt. Please try again.";
        status.className   = "email-status err";
    }
}

/**
 * CLEAR BUTTON
 * Binubura ang lahat ng inilagay at ibinabalik sa ₱0.00.
 */
function clickClear() {
    // Burahin ang customer details.
    document.getElementById("customerName").value  = "";
    document.getElementById("contactNumber").value = "";
    document.getElementById("orderNumber").value   = "";

    // Ibalik sa 0 ang lahat ng quantity.
    const qtyBoxes = document.querySelectorAll(".qty");
    for (let i = 0; i < qtyBoxes.length; i++) {
        qtyBoxes[i].value = "0";
    }

    // Ibalik sa ₱0.00 ang Bill Transactions.
    const outputs = document.querySelectorAll("output");
    for (let i = 0; i < outputs.length; i++) {
        outputs[i].textContent = "₱0.00";
    }

    clearHighlights();
    clearMessage();
    hideHistory();
    closeModal();
}


/* ---------------------------------------------------------------------
   BAHAGI 8: Ang pop-up (modal) ng resibo
   --------------------------------------------------------------------- */

/**
 * Buksan ang pop-up.
 * showEmailBox = true kung may kahon para sa e-mail address.
 */
function openModal(title, receiptText, showEmailBox) {
    document.getElementById("modalTitle").textContent = title;
    document.getElementById("modalBody").textContent  = receiptText;

    const emailControls = document.getElementById("emailControls");
    if (showEmailBox) {
        emailControls.classList.remove("hidden");
    } else {
        emailControls.classList.add("hidden");
    }

    // Linisin ang dating laman ng e-mail box.
    document.getElementById("emailStatus").textContent = "";
    document.getElementById("emailTo").value = "";

    document.getElementById("receiptModal").classList.remove("hidden");
    document.body.classList.add("modal-open");
}

/** Isara ang pop-up. */
function closeModal() {
    document.getElementById("receiptModal").classList.add("hidden");
    document.body.classList.remove("modal-open");
}


/* ---------------------------------------------------------------------
   BAHAGI 9: Ikabit ang lahat ng button
   Tumatakbo ito kapag handa na ang buong webpage.
   --------------------------------------------------------------------- */
document.addEventListener("DOMContentLoaded", function () {

    // Validation ng quantity.
    setupQuantityBoxes();

    // Alisin ang pulang highlight kapag nag-type na ang user.
    for (let i = 0; i < CUSTOMER_BOXES.length; i++) {
        const boxId = CUSTOMER_BOXES[i];
        document.getElementById(boxId).addEventListener("input", function () {
            markInvalid(boxId, false);
            clearMessage();
        });
    }

    // Ang anim na pangunahing button.
    document.getElementById("btnFind").addEventListener("click", clickFind);
    document.getElementById("btnAddCustomer").addEventListener("click", clickAddCustomer);
    document.getElementById("btnTotal").addEventListener("click", clickTotal);
    document.getElementById("btnBill").addEventListener("click", clickBill);
    document.getElementById("btnEmail").addEventListener("click", clickEmail);
    document.getElementById("btnPrint").addEventListener("click", clickPrint);
    document.getElementById("btnClear").addEventListener("click", clickClear);

    // Mga button sa loob ng pop-up.
    document.getElementById("modalClose").addEventListener("click", closeModal);
    document.getElementById("modalDone").addEventListener("click", closeModal);
    document.getElementById("btnSendEmail").addEventListener("click", clickSendEmail);
    document.getElementById("modalPrint").addEventListener("click", function () {
        window.print();
    });

    // Isara ang pop-up kapag pinindot ang labas nito.
    document.getElementById("receiptModal").addEventListener("click", function (event) {
        if (event.target.id == "receiptModal") {
            closeModal();
        }
    });

    // Isara ang pop-up kapag pinindot ang Escape key.
    document.addEventListener("keydown", function (event) {
        if (event.key == "Escape") {
            closeModal();
        }
    });
});
