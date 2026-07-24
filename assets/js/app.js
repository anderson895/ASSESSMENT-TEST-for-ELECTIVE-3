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

/* ---------- quantity validation: integers >= 0 only ---------- */
/** Strip anything that is not a digit and clamp to [0, 9999]. */
function sanitizeQty(el) {
    let cleaned = el.value.replace(/[^\d]/g, ""); // remove letters, '-', '.', 'e', etc.
    cleaned = cleaned.replace(/^0+(?=\d)/, "");    // drop leading zeros (00 -> 0)
    if (cleaned === "") cleaned = "";
    if (cleaned !== "" && Number(cleaned) > 9999) cleaned = "9999";
    el.value = cleaned;
}

function wireQtyValidation() {
    $$(".qty").forEach((el) => {
        // Block invalid keystrokes outright (letters, minus, plus, dot, exponent).
        el.addEventListener("keydown", (e) => {
            const allowed = ["Backspace", "Delete", "Tab", "ArrowLeft", "ArrowRight",
                             "ArrowUp", "ArrowDown", "Home", "End", "Enter"];
            if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
            if (!/^[0-9]$/.test(e.key)) e.preventDefault();
        });
        el.addEventListener("input", () => sanitizeQty(el));
        // On blur, an empty box falls back to 0.
        el.addEventListener("blur", () => { if (el.value === "") el.value = "0"; });
        // Block pasted non-numeric content.
        el.addEventListener("paste", (e) => {
            const text = (e.clipboardData || window.clipboardData).getData("text");
            if (!/^\d+$/.test(text.trim())) e.preventDefault();
        });
    });
}

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
    $("#billTransactions").scrollIntoView({ behavior: "smooth", block: "center" });
    alert("Bill saved. Order #" + data.order_id);
}

/* ---------- receipt modal helpers ---------- */
/** Fetch the formatted receipt body from the server (no send when to = ""). */
async function fetchReceipt() {
    const cart = collectCart();
    if (cart.length === 0) {
        alert("Enter at least one quantity first.");
        return null;
    }
    const data = await postJSON("api/email.php", {
        to: "",
        customer: currentCustomer(),
        cart,
    });
    if (!data.ok) {
        alert(data.error || "Unable to build the receipt.");
        return null;
    }
    return data.body;
}

function openModal({ title, body, emailMode }) {
    $("#modalTitle").textContent = title;
    $("#modalBody").textContent  = body;
    $("#emailControls").classList.toggle("hidden", !emailMode);
    $("#emailStatus").textContent = "";
    $("#emailTo").value = "";
    $("#receiptModal").classList.remove("hidden");
    document.body.classList.add("modal-open");
}

function closeModal() {
    $("#receiptModal").classList.add("hidden");
    document.body.classList.remove("modal-open");
}

/* ---------- button: E-MAIL ---------- */
async function onEmail() {
    const body = await fetchReceipt();
    if (body === null) return;
    openModal({ title: "E-Mail Receipt", body, emailMode: true });
}

async function onSendEmail() {
    const to = $("#emailTo").value.trim();
    if (!to || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(to)) {
        $("#emailStatus").textContent = "Please enter a valid e-mail address.";
        $("#emailStatus").className = "email-status err";
        return;
    }
    const data = await postJSON("api/email.php", {
        to,
        customer: currentCustomer(),
        cart: collectCart(),
    });
    if (data.ok && data.sent) {
        $("#emailStatus").textContent = "✔ Receipt sent to " + data.to;
        $("#emailStatus").className = "email-status ok";
    } else {
        $("#emailStatus").textContent =
            "Mail server not configured on this server — receipt shown above as preview.";
        $("#emailStatus").className = "email-status warn";
    }
}

/* ---------- button: PRINT (pop-up receipt, then print) ---------- */
async function onPrint() {
    const summary = await computeBill(); // refresh Bill Transactions first
    if (!summary) return;
    const body = await fetchReceipt();
    if (body === null) return;
    openModal({ title: "Print Receipt", body, emailMode: false });
}

/* ---------- button: CLEAR ---------- */
function onClear() {
    $("#customerName").value  = "";
    $("#contactNumber").value = "";
    $("#orderNumber").value   = "";
    $$(".qty").forEach((el) => (el.value = "0"));

    $$("output").forEach((el) => (el.textContent = "₱0.00"));
    closeModal();
}

/* ---------- wire everything up ---------- */
document.addEventListener("DOMContentLoaded", () => {
    wireQtyValidation();

    $("#btnFind").addEventListener("click", onFind);
    $("#btnTotal").addEventListener("click", computeBill);
    $("#btnBill").addEventListener("click", onBill);
    $("#btnEmail").addEventListener("click", onEmail);
    $("#btnPrint").addEventListener("click", onPrint);
    $("#btnClear").addEventListener("click", onClear);

    // Modal controls
    $("#modalClose").addEventListener("click", closeModal);
    $("#modalDone").addEventListener("click", closeModal);
    $("#modalPrint").addEventListener("click", () => window.print());
    $("#btnSendEmail").addEventListener("click", onSendEmail);
    $("#receiptModal").addEventListener("click", (e) => {
        if (e.target.id === "receiptModal") closeModal(); // click backdrop
    });
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal();
    });
});
