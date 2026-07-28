/* =====================================================================
   app.js - MRA STORE Online Billing System
   ---------------------------------------------------------------------
   Ito ang JavaScript na kumokontrol sa mga button:
   Find, Add Customer, Total, Bill, E-Mail, Print, at Clear.

   Ang totoong komputasyon (discount, 12% VAT, sukli) ay ginagawa ng
   PHP (OOP) sa /api folder. Ang trabaho lang ng file na ito ay:
     1. kunin ang inilagay ng user
     2. ipadala sa PHP
     3. ipakita ang sagot sa screen
   ===================================================================== */


/* ---------------------------------------------------------------------
   BAHAGI 1: Mga tulong na function
   --------------------------------------------------------------------- */

/**
 * Gawing pera ang numero.
 * Halimbawa: 515.5  =>  "₱515.50"
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
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        return await response.json();
    } catch (error) {
        return { ok: false, error: "Cannot reach the server. Is XAMPP running?" };
    }
}


/* ---------------------------------------------------------------------
   BAHAGI 2: Pagkuha ng inilagay ng user
   --------------------------------------------------------------------- */

/**
 * Kunin ang detalye ng customer.
 * Wala nang Order Number dito — automatic na iyon.
 */
function getCustomer() {
    return {
        name:    document.getElementById("customerName").value.trim(),
        contact: document.getElementById("contactNumber").value.trim()
    };
}

/** Sinong cashier ang napili? (0 kapag wala) */
function getCashierId() {
    return Number(document.getElementById("cashierSelect").value || 0);
}

/** Anong paraan ng pagbayad ang napili? */
function getPaymentMethod() {
    const chosen = document.querySelector('input[name="paymentMethod"]:checked');
    return chosen ? chosen.value : "Cash";
}

/** Magkano ang ibinigay na bayad? */
function getAmountReceived() {
    const value = document.getElementById("amountReceived").value.replace(/[^0-9.]/g, "");
    const amount = parseFloat(value);
    return isNaN(amount) ? 0 : amount;
}

/** Anong uri ng discount ang napili? */
function getDiscountType() {
    return document.getElementById("discountType").value;
}

/** Ano ang kasalukuyang Order Number na nakadisplay? */
function getOrderNumber() {
    const text = document.getElementById("orderNumber").textContent.trim();
    return text === "—" ? "" : text;
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
                quantity:   quantity
            });
        }
    }

    return cart;
}

/**
 * Buuin ang buong datos na ipapadala sa PHP.
 * Iisang porma lang ito para sa Total, Bill, E-Mail, at Print —
 * kaya siguradong pareho ang komputasyon sa apat.
 */
function buildPayload(extra) {
    const payload = {
        cart:            getCart(),
        customer:        getCustomer(),
        employee_id:     getCashierId(),
        payment_method:  getPaymentMethod(),
        amount_received: getAmountReceived(),
        discount_type:   getDiscountType()
    };

    if (extra) {
        for (const key in extra) {
            payload[key] = extra[key];
        }
    }

    return payload;
}


/* ---------------------------------------------------------------------
   BAHAGI 3: Pagpapakita ng mensahe at pag-highlight ng field
   --------------------------------------------------------------------- */

// Ang mga required na kahon.
const REQUIRED_BOXES = ["customerName", "contactNumber"];

/**
 * Gawing pula ang isang kahon (o alisin ang pula).
 */
function markInvalid(elementId, isInvalid) {
    const box = document.getElementById(elementId);
    if (!box) {
        return;
    }
    if (isInvalid) {
        box.classList.add("invalid");
    } else {
        box.classList.remove("invalid");
    }
}

/** Alisin ang pula sa lahat ng kahon. */
function clearHighlights() {
    for (let i = 0; i < REQUIRED_BOXES.length; i++) {
        markInvalid(REQUIRED_BOXES[i], false);
    }
    markInvalid("cashierSelect", false);
    markInvalid("amountReceived", false);
}

/**
 * Magpakita ng mensahe sa ibabaw ng mga button.
 * type = "err" (pula) o "ok" (berde)
 */
function showMessage(text, type) {
    const notice = document.getElementById("formNotice");

    notice.textContent = text;
    notice.className   = "form-notice";

    if (text !== "") {
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
   BAHAGI 4: Cashier on Duty
   --------------------------------------------------------------------- */

/**
 * Kapag pumili ng cashier, ipakita ang Employee ID, Position, at Shift.
 * Ito rin ang lumalabas sa resibo.
 */
function showCashierDetails() {
    const select = document.getElementById("cashierSelect");
    const option = select.options[select.selectedIndex];

    const hasCashier = select.value !== "";

    document.getElementById("cashierCode").textContent =
        hasCashier ? option.dataset.code : "—";
    document.getElementById("cashierPosition").textContent =
        hasCashier ? option.dataset.position : "—";
    document.getElementById("cashierShift").textContent =
        hasCashier ? option.dataset.shift : "—";

    if (hasCashier) {
        markInvalid("cashierSelect", false);
    }
}


/* ---------------------------------------------------------------------
   BAHAGI 5: Automatic na Order Number
   --------------------------------------------------------------------- */

/**
 * Kunin sa PHP ang SUSUNOD na order number, tapos ipakita sa screen.
 * Tumatakbo ito pagkabukas ng pahina at pagkatapos ng bawat bill.
 */
async function loadOrderNumber() {
    const box = document.getElementById("orderNumber");

    const result = await sendToServer("api/next_order_number.php", {});

    if (result.ok) {
        box.textContent = result.order_number;
    } else {
        box.textContent = "—";
    }
}

/** Itakda ang ipinapakitang Order Number. */
function setOrderNumber(orderNumber) {
    document.getElementById("orderNumber").textContent = orderNumber;
}


/* ---------------------------------------------------------------------
   BAHAGI 6: Validation ng quantity (numero lang, hindi lalampas sa stock)
   --------------------------------------------------------------------- */

/**
 * Linisin ang laman ng quantity box.
 * Tinatanggal ang letra, minus sign, at tuldok — at hindi pinapayagang
 * lumampas sa natitirang STOCK.
 */
function cleanQuantity(box) {
    let value = box.value;

    // Alisin ang lahat maliban sa numero 0-9.
    value = value.replace(/[^0-9]/g, "");

    // Alisin ang sobrang zero sa unahan (halimbawa: 007 => 7).
    value = value.replace(/^0+(?=[0-9])/, "");

    // Huwag hayaang lumampas sa natitirang stock.
    const stock = Number(box.dataset.stock || 0);

    if (value !== "" && Number(value) > stock) {
        value = String(stock);
        showMessage("Only " + stock + " left in stock for that item.", "err");
    }

    box.value = value;
}

/**
 * Kulayan ang buong row kapag may binili rito, at buksan o isara
 * ang minus/plus na button ayon sa laman.
 */
function refreshQtyRow(box) {
    const quantity = Number(box.value || 0);
    const stock    = Number(box.dataset.stock || 0);
    const row      = box.closest("tr");
    const wrap     = box.closest(".qty-wrap");

    if (row !== null) {
        if (quantity > 0) {
            row.classList.add("has-qty");
        } else {
            row.classList.remove("has-qty");
        }
    }

    // Walang stock - manatiling sarado ang dalawang button.
    if (wrap === null || stock <= 0) {
        return;
    }

    const minus = wrap.querySelector(".qty-minus");
    const plus  = wrap.querySelector(".qty-plus");

    if (minus !== null) { minus.disabled = quantity <= 0; }
    if (plus  !== null) { plus.disabled  = quantity >= stock; }
}

/**
 * Dagdagan o bawasan ng isa ang quantity (ang minus at plus na button).
 */
function stepQuantity(box, change) {
    const stock    = Number(box.dataset.stock || 0);
    let   quantity = Number(box.value || 0) + change;

    if (quantity < 0) {
        quantity = 0;
    }

    if (quantity > stock) {
        quantity = stock;
        showMessage("Only " + stock + " left in stock for that item.", "err");
    }

    box.value = String(quantity);

    refreshQtyRow(box);
    scheduleRefresh();
}

/**
 * Ikabit ang validation sa lahat ng quantity box.
 */
function setupQuantityBoxes() {
    const qtyBoxes = document.querySelectorAll(".qty");

    for (let i = 0; i < qtyBoxes.length; i++) {
        const box = qtyBoxes[i];

        // ---- Ang minus at plus na button sa tabi ng kahon ----
        const wrap = box.closest(".qty-wrap");

        if (wrap !== null) {
            const minus = wrap.querySelector(".qty-minus");
            const plus  = wrap.querySelector(".qty-plus");

            if (minus !== null) {
                minus.addEventListener("click", function () { stepQuantity(box, -1); });
            }
            if (plus !== null) {
                plus.addEventListener("click", function () { stepQuantity(box, 1); });
            }
        }

        // Itakda ang tamang anyo sa simula pa lang.
        refreshQtyRow(box);

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

        // 2) Linisin ang laman tuwing may binabago, tapos kalkulahin ulit.
        box.addEventListener("input", function () {
            cleanQuantity(box);
            refreshQtyRow(box);
            scheduleRefresh();
        });

        // 3) Kapag walang laman pagkaalis ng cursor, gawing 0.
        box.addEventListener("blur", function () {
            if (box.value === "") {
                box.value = "0";
                refreshQtyRow(box);
                scheduleRefresh();
            }
        });
    }
}


/* ---------------------------------------------------------------------
   BAHAGI 7: Payment Method at Discount
   --------------------------------------------------------------------- */

/**
 * Kapag GCash o Card ang napili, EKSAKTO ang bayad — kaya hindi na
 * kailangang mag-type ng Amount Received at walang sukli.
 * Sa Cash lang ito binubuksan.
 */
function updatePaymentMode() {
    const method   = getPaymentMethod();
    const amount   = document.getElementById("amountReceived");
    const note     = document.getElementById("amountNote");
    const isCash   = method === "Cash";

    amount.disabled = !isCash;

    if (isCash) {
        amount.placeholder = "0.00";
        note.textContent   = "Cash payment — enter the amount handed over.";
    } else {
        amount.value       = "";
        amount.placeholder = "Exact amount";
        note.textContent   = method + " payment — the exact amount is charged, so there is no change.";
        markInvalid("amountReceived", false);
    }
}

/**
 * Linisin ang Amount Received: numero at isang tuldok lang.
 */
function cleanAmount(box) {
    let value = box.value.replace(/[^0-9.]/g, "");

    // Isang tuldok lang ang pinapayagan.
    const firstDot = value.indexOf(".");
    if (firstDot !== -1) {
        value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, "");
    }

    box.value = value;
}


/* ---------------------------------------------------------------------
   BAHAGI 8: Pagpapakita ng resulta sa Bill Transactions
   --------------------------------------------------------------------- */

/**
 * Ilagay sa screen ang resulta ng komputasyon.
 */
function showSummary(summary) {
    // ---- Total ng bawat kategorya ----
    const totalBoxes = document.querySelectorAll("[data-cat-total]");

    for (let i = 0; i < totalBoxes.length; i++) {
        const box    = totalBoxes[i];
        const amount = summary.category_totals[box.dataset.catTotal] || 0;
        box.textContent = peso(amount);
    }

    document.getElementById("totalItems").textContent = summary.total_quantity;

    // ---- Komputasyon ----
    document.getElementById("subtotal").textContent     = peso(summary.subtotal);
    document.getElementById("discountOut").textContent  = "- " + peso(summary.discount_amount);
    document.getElementById("vatableSales").textContent = peso(summary.vatable_sales);
    document.getElementById("vatAmount").textContent    = peso(summary.vat);
    document.getElementById("grandTotal").textContent   = peso(summary.grand_total);

    // Isulat kung anong discount ang ginamit.
    const discountLabel = document.getElementById("discountLabel");
    if (summary.discount_type === "None") {
        discountLabel.textContent = "Discount";
    } else {
        discountLabel.textContent =
            "Discount (" + summary.discount_type + " " + Math.round(summary.discount_rate * 100) + "%)";
    }

    // ---- Bayad ----
    document.getElementById("methodOut").textContent   = summary.payment_method;
    document.getElementById("receivedOut").textContent = peso(summary.amount_received);
    document.getElementById("changeDue").textContent   = peso(summary.change_due);

    // ---- Discount Amount sa Payment card ----
    document.getElementById("discountAmount").textContent = peso(summary.discount_amount);

    // ---- Listahan ng item at ang sticky bar sa ilalim ----
    renderCart(summary.items || []);
    updateLiveBar(summary.grand_total, summary.total_quantity);
}

/** Ibalik ang lahat ng resulta sa ₱0.00. */
function resetSummary() {
    const outputs = document.querySelectorAll("#billTransactions output");
    for (let i = 0; i < outputs.length; i++) {
        outputs[i].textContent = "₱0.00";
    }

    document.getElementById("totalItems").textContent     = "0";
    document.getElementById("methodOut").textContent      = getPaymentMethod();
    document.getElementById("discountLabel").textContent  = "Discount";
    document.getElementById("discountAmount").textContent = "₱0.00";

    renderCart([]);
    updateLiveBar(0, 0);
}


/* ---------------------------------------------------------------------
   BAHAGI 8-B: Listahan ng napiling item + ang sticky bar sa ilalim
   --------------------------------------------------------------------- */

/**
 * Isulat ang listahan ng napiling produkto sa Step 4.
 * Kapag walang laman, ipapakita ang paalalang "No items selected yet".
 */
function renderCart(items) {
    const list  = document.getElementById("cartList");
    const empty = document.getElementById("cartEmpty");

    // Walang binili pa.
    if (items.length === 0) {
        list.innerHTML = "";
        list.classList.add("hidden");
        empty.classList.remove("hidden");
        return;
    }

    let rows = "";

    for (let i = 0; i < items.length; i++) {
        const item = items[i];

        rows += '<li>' +
                    '<img src="' + item.image + '" alt="" ' +
                        'onerror="this.src=\'assets/img/products/placeholder.svg\'">' +
                    '<span class="cart-name">' + item.product_name + '</span>' +
                    '<span class="cart-qty">' + item.quantity + ' &times; ' + peso(item.price) + '</span>' +
                    '<span class="cart-amount">' + peso(item.total_price) + '</span>' +
                '</li>';
    }

    list.innerHTML = rows;
    list.classList.remove("hidden");
    empty.classList.add("hidden");
}

/**
 * I-update ang Grand Total at bilang ng item sa sticky bar sa ilalim.
 */
function updateLiveBar(grandTotal, totalQuantity) {
    document.getElementById("liveGrandTotal").textContent = peso(grandTotal);

    const label = document.getElementById("liveItems");

    if (!totalQuantity || totalQuantity === 0) {
        label.textContent = "No items yet";
    } else {
        label.textContent = totalQuantity + " item" + (totalQuantity === 1 ? "" : "s") + " in this order";
    }
}

/**
 * Buksan o isara ang PRINT at E-MAIL na button.
 *
 * Sarado ito hangga't walang naka-save na bill — ito ang dahilan kung
 * bakit nakakalito noon: puwede mong pindutin ang Print bago pa may bill.
 */
function setBilled(isBilled) {
    const printButton = document.getElementById("btnPrint");
    const emailButton = document.getElementById("btnEmail");

    printButton.disabled = !isBilled;
    emailButton.disabled = !isBilled;

    const tip = isBilled ? "" : "Save the bill first";

    printButton.title = tip;
    emailButton.title = tip;
}


/* ---------------------------------------------------------------------
   BAHAGI 8-C: LIVE na pagkalkula
   ---------------------------------------------------------------------
   Hindi na kailangang pindutin ang TOTAL. Tuwing may binabago sa
   quantity, discount, o bayad, tinatawag natin ang api/calculate.php
   para sabihin ang bagong total.

   Ang server pa rin ang nagkukuwenta — hindi natin kinokopya ang
   pormula sa JavaScript, para walang pagkakaiba ang lalabas sa resibo.
   --------------------------------------------------------------------- */

/** Timer para hindi tumawag sa server sa bawat pindot ng daliri. */
let refreshTimer = null;

/**
 * Kalkulahin ulit ang total — TAHIMIK (walang pulang mensahe).
 * Ang babala tungkol sa kulang na bayad ay sa TOTAL at BILL na lang.
 */
async function refreshTotals() {
    const cart = getCart();

    // Walang binili - ibalik sa zero at huwag nang tumawag sa server.
    if (cart.length === 0) {
        resetSummary();
        return;
    }

    let result;

    try {
        result = await sendToServer("api/calculate.php", buildPayload());
    } catch (error) {
        return;   // Tahimik lang - hindi ito hihinto sa pag-bill.
    }

    if (result && result.ok) {
        showSummary(result.summary);
    }
}

/**
 * Maghintay muna ng 350ms bago tumawag sa server, para kahit
 * mabilis mag-type ang cashier ay isang tawag lang ang mangyayari.
 */
function scheduleRefresh() {
    if (refreshTimer !== null) {
        clearTimeout(refreshTimer);
    }

    refreshTimer = setTimeout(function () {
        refreshTimer = null;
        refreshTotals();
    }, 350);
}


/* ---------------------------------------------------------------------
   BAHAGI 9: Order History (mga lumang order na nakita ng FIND)
   --------------------------------------------------------------------- */

/**
 * Gawing madaling basahin ang petsa mula sa database.
 * Halimbawa: "2026-07-28 15:08:00"  =>  "Jul 28, 2026, 3:08 PM"
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
    document.getElementById("historyList").innerHTML  = "";
    document.getElementById("historyFor").textContent = "";
}

/**
 * Gumawa ng isang kahon para sa ISANG lumang order.
 */
function buildHistoryCard(order) {
    const box = document.createElement("div");
    box.className = "history-item";

    // ---- Ulo: Order No. at petsa ----
    const head = document.createElement("div");
    head.className = "history-head";

    const label = document.createElement("strong");
    label.textContent = order.order_number || ("Order #" + order.order_id);

    const when = document.createElement("span");
    when.className   = "history-date";
    when.textContent = readableDate(order.created_at);

    head.appendChild(label);
    head.appendChild(when);
    box.appendChild(head);

    // ---- Sinong cashier at anong bayad ----
    const meta = document.createElement("div");
    meta.className = "history-meta";

    const parts = [];
    if (order.cashier && order.cashier.employee_name) {
        parts.push("Cashier: " + order.cashier.employee_name +
                   " (" + order.cashier.employee_code + ")");
    }
    parts.push("Paid by " + order.payment_method);
    if (order.discount_type && order.discount_type !== "None") {
        parts.push(order.discount_type + " discount");
    }
    meta.textContent = parts.join("  •  ");
    box.appendChild(meta);

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
        "<span>Discount: <b>" + peso(order.discount_amount) + "</b></span>" +
        "<span>VAT: <b>" + peso(order.total_tax) + "</b></span>" +
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
    if (orders.length === 0) {
        info.textContent = customer.customer_name + " — no past orders yet.";
    } else if (orders.length === 1) {
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
   BAHAGI 10: Ang mga BUTTON
   --------------------------------------------------------------------- */

/**
 * Tingnan kung kompleto ang kailangan bago mag-bill.
 * Ibabalik ang true kapag okay na lahat.
 */
function validateBeforeBilling() {
    const customer = getCustomer();
    const cashier  = getCashierId();

    markInvalid("customerName",  customer.name === "");
    markInvalid("contactNumber", customer.contact === "");
    markInvalid("cashierSelect", cashier === 0);

    if (cashier === 0) {
        showMessage("Please select the Cashier on Duty first.", "err");
        return false;
    }

    if (customer.name === "" || customer.contact === "") {
        showMessage("Please complete the highlighted customer fields before billing.", "err");
        return false;
    }

    return true;
}

/**
 * FIND BUTTON
 * Naghahanap ng customer gamit ang Contact Number o Order Number,
 * tapos ipinapakita rin ang lahat ng lumang order niya.
 */
async function clickFind() {
    const contactBox = document.getElementById("contactNumber");
    const keyword    = contactBox.value.trim();

    // Walang inilagay.
    if (keyword === "") {
        markInvalid("contactNumber", true);
        showMessage("Enter a Contact Number (or an Order Number from a receipt) to search.", "err");
        contactBox.focus();
        return;
    }

    // Ipadala sa PHP.
    const result = await sendToServer("api/find_customer.php", { keyword: keyword });

    // Walang nakita.
    if (!result.ok) {
        markInvalid("contactNumber", true);
        showMessage(result.error, "err");
        hideHistory();
        return;
    }

    // May nakita - punuin ang dalawang kahon.
    document.getElementById("customerName").value  = result.customer.customer_name;
    document.getElementById("contactNumber").value = result.customer.contact_number;

    // Ipakita ang mga lumang order niya.
    const orders = result.orders || [];
    showHistory(result.customer, orders);

    clearHighlights();

    if (orders.length === 0) {
        showMessage("✔ Customer found — details loaded. No past orders yet.", "ok");
    } else {
        showMessage("✔ Customer found — details loaded. " + orders.length +
                    " past order" + (orders.length === 1 ? "" : "s") + " below.", "ok");
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

    markInvalid("customerName",  customer.name === "");
    markInvalid("contactNumber", customer.contact === "");

    if (customer.name === "" || customer.contact === "") {
        showMessage("Please complete the Customer Name and Contact Number to add a customer.", "err");
        return;
    }

    const result = await sendToServer("api/add_customer.php", {
        name:    customer.name,
        contact: customer.contact
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
 * Kinakalkula ang bill (hindi pa nagse-save).
 */
async function clickTotal() {
    const cart = getCart();

    if (cart.length === 0) {
        showMessage("Enter at least one quantity first.", "err");
        return null;
    }

    const result = await sendToServer("api/calculate.php", buildPayload());

    if (!result.ok) {
        showMessage(result.error, "err");
        return null;
    }

    showSummary(result.summary);

    // Paalala kapag kulang ang cash na ibinigay.
    if (!result.summary.is_paid) {
        markInvalid("amountReceived", true);
        showMessage("Amount Received is less than the Grand Total of " +
                    peso(result.summary.grand_total) + ".", "err");
    } else {
        markInvalid("amountReceived", false);
        clearMessage();
    }

    return result.summary;
}

/**
 * BILL BUTTON
 * Kinakalkula ang bill, sini-save sa database, kumukuha ng automatic
 * na order number, at ipinapakita ang resibo.
 */
async function clickBill() {
    const cart = getCart();

    if (cart.length === 0) {
        showMessage("Enter at least one quantity first.", "err");
        return;
    }

    if (!validateBeforeBilling()) {
        return;
    }

    // Ipadala sa PHP para i-save.
    const result = await sendToServer("api/save_order.php", buildPayload());

    if (!result.ok) {
        showMessage(result.error, "err");
        return;
    }

    showSummary(result.summary);
    clearHighlights();

    // Ito na ang TOTOONG order number ng transaksyong ito.
    setOrderNumber(result.order_number);
    showMessage("✔ Bill saved — Order No. " + result.order_number, "ok");

    // May bill na - kaya puwede nang i-print at i-e-mail.
    setBilled(true);

    // Ipakita agad ang resibo.
    openModal("Official Receipt — " + result.order_number, result.receipt_html, false);

    // Kung nakabukas ang Order History, i-refresh para kasama ang bagong order.
    const history = document.getElementById("orderHistory");
    if (!history.classList.contains("hidden")) {
        const fresh = await sendToServer("api/find_customer.php", {
            keyword: getCustomer().contact
        });
        if (fresh.ok) {
            showHistory(fresh.customer, fresh.orders || []);
        }
    }
}

/**
 * Kunin sa PHP ang resibo (HTML na may logo at larawan).
 */
async function getReceipt() {
    const cart = getCart();

    if (cart.length === 0) {
        showMessage("Enter at least one quantity first.", "err");
        return null;
    }

    const result = await sendToServer("api/email.php", buildPayload({
        to: "",
        order_number: getOrderNumber()
    }));

    if (!result.ok) {
        showMessage(result.error, "err");
        return null;
    }

    showSummary(result.summary);
    clearMessage();
    return result;
}

/**
 * PRINT BUTTON
 * Nagpapakita ng resibo sa pop-up bago i-print.
 */
async function clickPrint() {
    const receipt = await getReceipt();
    if (receipt === null) {
        return;
    }

    openModal("Print Receipt — " + receipt.order_number, receipt.receipt_html, false);
}

/**
 * E-MAIL BUTTON
 * Nagpapakita ng resibo at ng kahon para sa e-mail address.
 */
async function clickEmail() {
    const receipt = await getReceipt();
    if (receipt === null) {
        return;
    }

    openModal("E-Mail Receipt — " + receipt.order_number, receipt.receipt_html, true);
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

    if (address === "" || !emailPattern.test(address)) {
        status.textContent = "Please enter a valid e-mail address.";
        status.className   = "email-status err";
        return;
    }

    // ---- Ipakita ang loader habang ipinapadala ----
    sendBtn.disabled    = true;
    sendBtn.textContent = "Sending...";
    status.className    = "email-status load";
    status.innerHTML    = '<span class="email-spinner"></span> Sending receipt to ' + address + " ...";

    let result;
    try {
        result = await sendToServer("api/email.php", buildPayload({
            to: address,
            order_number: getOrderNumber()
        }));
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
async function clickClear() {
    // Burahin ang customer details.
    document.getElementById("customerName").value  = "";
    document.getElementById("contactNumber").value = "";

    // Ibalik sa 0 ang lahat ng quantity.
    const qtyBoxes = document.querySelectorAll(".qty");
    for (let i = 0; i < qtyBoxes.length; i++) {
        qtyBoxes[i].value = "0";
    }

    // Ibalik ang payment at discount sa dati.
    document.querySelector('input[name="paymentMethod"][value="Cash"]').checked = true;
    document.getElementById("amountReceived").value = "";
    document.getElementById("discountType").value   = "None";
    updatePaymentMode();

    // Alisin ang highlight at ibalik ang minus/plus na button.
    const qtyBoxes2 = document.querySelectorAll(".qty");
    for (let i = 0; i < qtyBoxes2.length; i++) {
        refreshQtyRow(qtyBoxes2[i]);
    }

    resetSummary();
    clearHighlights();
    clearMessage();
    hideHistory();
    closeModal();

    // Bagong transaksyon - sarado muli ang Print at E-Mail.
    setBilled(false);

    // Kumuha ng bagong order number para sa susunod na customer.
    await loadOrderNumber();
}


/* ---------------------------------------------------------------------
   BAHAGI 11: Ang pop-up (modal) ng resibo
   --------------------------------------------------------------------- */

/**
 * Buksan ang pop-up.
 * showEmailBox = true kung may kahon para sa e-mail address.
 */
function openModal(title, receiptHtml, showEmailBox) {
    document.getElementById("modalTitle").textContent = title;

    // Ang resibo ay HTML na ginawa ng Receipt class sa PHP.
    document.getElementById("modalBody").innerHTML = receiptHtml;

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
   BAHAGI 12: Ikabit ang lahat ng button
   Tumatakbo ito kapag handa na ang buong webpage.
   --------------------------------------------------------------------- */
document.addEventListener("DOMContentLoaded", function () {

    // Validation ng quantity.
    setupQuantityBoxes();

    // Kunin agad ang automatic na Order Number.
    loadOrderNumber();

    // Cashier on Duty.
    document.getElementById("cashierSelect").addEventListener("change", showCashierDetails);

    // Alisin ang pulang highlight kapag nag-type na ang user.
    for (let i = 0; i < REQUIRED_BOXES.length; i++) {
        const boxId = REQUIRED_BOXES[i];
        document.getElementById(boxId).addEventListener("input", function () {
            markInvalid(boxId, false);
            clearMessage();
        });
    }

    // Payment method (Cash / GCash / Card).
    const methods = document.querySelectorAll('input[name="paymentMethod"]');
    for (let i = 0; i < methods.length; i++) {
        methods[i].addEventListener("change", function () {
            updatePaymentMode();
            document.getElementById("methodOut").textContent = getPaymentMethod();
            scheduleRefresh();
        });
    }

    // Amount Received - numero lang.
    const amountBox = document.getElementById("amountReceived");
    amountBox.addEventListener("input", function () {
        cleanAmount(amountBox);
        markInvalid("amountReceived", false);
        scheduleRefresh();
    });

    // Discount Type - agad na makikita ang bagong total.
    document.getElementById("discountType").addEventListener("change", scheduleRefresh);

    // Ang mga pangunahing button.
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
        if (event.target.id === "receiptModal") {
            closeModal();
        }
    });

    // Isara ang pop-up kapag pinindot ang Escape key.
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeModal();
        }
    });

    // Simulan sa tamang mode ang Amount Received.
    updatePaymentMode();
});
