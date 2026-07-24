/* =====================================================================
   Online Billing System - frontend controller
   Talks to the OOP PHP API in /api and drives the button events:
   Find, Total, Bill, E-Mail, Print, Clear.
   ===================================================================== */
"use strict";

/* ---------- helpers ---------- */
const $  = (sel) => document.querySelector(sel);
const $$ = (sel) => Array.from(document.querySelectorAll(sel));

const peso = (n) => "₱" + Number(n || 0).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/** Map a category name to its Bill Transactions field suffix. */
const CATEGORY_KEY = {
    "Beauty & Personal Care": "Beauty",
    "Grocery": "Grocery",
    "Beverages": "Beverage",
};

/** Collect every quantity input into a cart array. */
function collectCart() {
    return $$(".qty")
        .map((el) => ({
            product_id: Number(el.dataset.productId),
            category:   el.dataset.category,
            quantity:   parseInt(el.value, 10) || 0,
        }))
        .filter((line) => line.quantity > 0);
}

function currentCustomer() {
    return {
        name:         $("#customerName").value.trim(),
        contact:      $("#contactNumber").value.trim(),
        order_number: $("#orderNumber").value.trim(),
    };
}

/** POST JSON and parse the response. */
async function postJSON(url, payload) {
    const res  = await fetch(url, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({ ok: false, error: "Bad server response." }));
    return data;
}

/* ---------- render the bill summary into the page ---------- */
function renderSummary(summary) {
    const totals = summary.category_totals || {};
    const taxes  = summary.category_taxes  || {};

    for (const [cat, key] of Object.entries(CATEGORY_KEY)) {
        $("#tot" + key).textContent = peso(totals[cat] || 0);
        $("#tax" + key).textContent = peso(taxes[cat]  || 0);
    }

    $("#subtotal").textContent   = peso(summary.subtotal);
    $("#totalTax").textContent   = peso(summary.total_tax);
    $("#grandTotal").textContent = peso(summary.grand_total);
}

/* ---------- button: FIND ---------- */
async function onFind() {
    const keyword = $("#contactNumber").value.trim() || $("#orderNumber").value.trim();
    if (!keyword) {
        alert("Enter a Contact Number or Order Number to search.");
        return;
    }
    const data = await postJSON("api/find_customer.php", { keyword });
    if (!data.ok) {
        alert(data.error || "Customer not found.");
        return;
    }
    $("#customerName").value  = data.customer.customer_name;
    $("#contactNumber").value = data.customer.contact_number;
    $("#orderNumber").value   = data.customer.order_number;
}

/* ---------- button: TOTAL / BILL (compute) ---------- */
async function computeBill() {
    const cart = collectCart();
    if (cart.length === 0) {
        alert("Enter at least one quantity first.");
        return null;
    }
    const data = await postJSON("api/calculate.php", { cart });
    if (!data.ok) {
        alert(data.error || "Calculation failed.");
        return null;
    }
    renderSummary(data.summary);
    return data.summary;
}

/* ---------- button: BILL (compute + persist) ---------- */
async function onBill() {
    const cart = collectCart();
    if (cart.length === 0) {
        alert("Enter at least one quantity first.");
        return;
    }
    const data = await postJSON("api/save_order.php", {
        customer: currentCustomer(),
        cart,
    });
    if (!data.ok) {
        alert(data.error || "Unable to save the bill.");
        return;
    }
    renderSummary(data.summary);
    // Reflect the generated / used order number back into the form.
    $("#billTransactions").scrollIntoView({ behavior: "smooth", block: "center" });
    alert("Bill saved. Order #" + data.order_id);
}

/* ---------- button: E-MAIL ---------- */
async function onEmail() {
    const cart = collectCart();
    if (cart.length === 0) {
        alert("Enter at least one quantity first.");
        return;
    }
    const to = prompt("Send billing details to (leave blank to just display):", "");
    if (to === null) return; // cancelled

    const data = await postJSON("api/email.php", {
        to,
        customer: currentCustomer(),
        cart,
    });
    if (!data.ok) {
        alert(data.error || "Unable to build the billing details.");
        return;
    }
    $("#outputTitle").textContent = data.sent
        ? "Billing details sent to " + data.to
        : "Billing Details (preview)";
    $("#outputBody").textContent = data.body;
    $("#outputCard").classList.remove("hidden");
    $("#outputCard").scrollIntoView({ behavior: "smooth", block: "center" });
}

/* ---------- button: PRINT ---------- */
async function onPrint() {
    // Make sure the totals are up to date before printing.
    const summary = await computeBill();
    if (!summary) return;
    window.print();
}

/* ---------- button: CLEAR ---------- */
function onClear() {
    $("#customerName").value  = "";
    $("#contactNumber").value = "";
    $("#orderNumber").value   = "";
    $$(".qty").forEach((el) => (el.value = 0));

    $$("output").forEach((el) => (el.textContent = "₱0.00"));
    $("#outputCard").classList.add("hidden");
    $("#outputBody").textContent = "";
}

/* ---------- wire everything up ---------- */
document.addEventListener("DOMContentLoaded", () => {
    $("#btnFind").addEventListener("click", onFind);
    $("#btnTotal").addEventListener("click", computeBill);
    $("#btnBill").addEventListener("click", onBill);
    $("#btnEmail").addEventListener("click", onEmail);
    $("#btnPrint").addEventListener("click", onPrint);
    $("#btnClear").addEventListener("click", onClear);
});
