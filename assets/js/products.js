/* =====================================================================
   products.js
   ---------------------------------------------------------------------
   Ito ang JavaScript ng products.php (Product Management page).

   Tatlong trabaho lang:
     1) MAGDAGDAG / MAG-EDIT ng produkto (may larawan)
     2) MAGBURA ng produkto
     3) Magpakita ng preview ng larawan bago i-save
   ===================================================================== */


/* ---------------------------------------------------------------------
   BAHAGI 1: Mga maliit na tulong
   --------------------------------------------------------------------- */

/** Kunin ang element gamit ang id. */
function el(id) {
    return document.getElementById(id);
}

/** Ang larawang gagamitin kapag walang larawan ang produkto. */
const PLACEHOLDER = "assets/img/products/placeholder.svg";

/** Mga kahon na puwedeng magkulay pula kapag mali. */
const FORM_BOXES = ["productName", "productCategory", "productPrice", "productStock"];


/**
 * Magpakita ng mensahe sa itaas ng pahina.
 * type = "ok" (berde) o "err" (pula)
 */
function showMessage(text, type) {
    const notice = el("formNotice");

    notice.textContent = text;
    notice.className   = "form-notice show " + type;

    notice.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

/** Alisin ang mensahe. */
function clearMessage() {
    const notice = el("formNotice");

    notice.textContent = "";
    notice.className   = "form-notice";
}

/** Kulayan ng pula (o alisin ang pula sa) isang kahon. */
function markInvalid(boxId, isInvalid) {
    const box = el(boxId);

    if (box == null) {
        return;
    }

    if (isInvalid) {
        box.classList.add("invalid");
    } else {
        box.classList.remove("invalid");
    }
}

/** Alisin ang pula sa lahat ng kahon ng form. */
function clearHighlights() {
    for (let i = 0; i < FORM_BOXES.length; i++) {
        markInvalid(FORM_BOXES[i], false);
    }
}


/* ---------------------------------------------------------------------
   BAHAGI 2: Ang form (Add / Edit)
   --------------------------------------------------------------------- */

/**
 * Ibalik ang form sa "Add New Product" na anyo.
 */
function resetForm() {
    el("productForm").reset();

    el("productId").value    = "0";
    el("formTitle").textContent   = "Add New Product";
    el("btnSaveProduct").textContent = "Add Product";
    el("imagePreview").src   = PLACEHOLDER;

    el("btnCancelEdit").classList.add("hidden");

    clearHighlights();
}

/**
 * Ilagay sa form ang datos ng produktong ie-edit.
 * Ang datos ay galing sa data-* attributes ng Edit button.
 */
function startEdit(button) {
    el("productId").value       = button.getAttribute("data-id");
    el("productName").value     = button.getAttribute("data-name");
    el("productCategory").value = button.getAttribute("data-category");
    el("productPrice").value    = button.getAttribute("data-price");
    el("productStock").value    = button.getAttribute("data-stock");
    el("imagePreview").src      = button.getAttribute("data-image") || PLACEHOLDER;

    // Walang bagong file na napili - kaya babantayan natin ang dati.
    el("productImage").value = "";

    el("formTitle").textContent       = "Edit: " + button.getAttribute("data-name");
    el("btnSaveProduct").textContent  = "Save Changes";
    el("btnCancelEdit").classList.remove("hidden");

    clearHighlights();
    clearMessage();

    // Iakyat ang tingin papunta sa form.
    el("formTitle").scrollIntoView({ behavior: "smooth", block: "start" });
    el("productName").focus();
}

/**
 * Tingnan muna sa browser kung kompleto ang form.
 * Ibabalik ang true kapag okay na.
 */
function validateForm() {
    const name     = el("productName").value.trim();
    const category = el("productCategory").value;
    const price    = el("productPrice").value.trim();
    const stock    = el("productStock").value.trim();

    markInvalid("productName",     name === "");
    markInvalid("productCategory", category === "");
    markInvalid("productPrice",    price === "" || isNaN(Number(price)) || Number(price) < 0);
    markInvalid("productStock",    !/^\d+$/.test(stock));

    if (name === "") {
        showMessage("Please enter the product name.", "err");
        el("productName").focus();
        return false;
    }
    if (category === "") {
        showMessage("Please choose a category.", "err");
        el("productCategory").focus();
        return false;
    }
    if (price === "" || isNaN(Number(price)) || Number(price) < 0) {
        showMessage("Price must be a number (0 or more).", "err");
        el("productPrice").focus();
        return false;
    }
    if (!/^\d+$/.test(stock)) {
        showMessage("Stock must be a whole number (0 or more).", "err");
        el("productStock").focus();
        return false;
    }

    return true;
}

/**
 * I-SAVE ang produkto (magdagdag o mag-update).
 *
 * FormData ang gamit natin - hindi JSON - dahil may kasamang larawan.
 */
async function submitForm(event) {
    event.preventDefault();

    if (!validateForm()) {
        return;
    }

    const saveButton = el("btnSaveProduct");
    const oldLabel   = saveButton.textContent;

    saveButton.disabled    = true;
    saveButton.textContent = "Saving...";

    let result;

    try {
        const response = await fetch("api/save_product.php", {
            method: "POST",
            body:   new FormData(el("productForm"))
        });

        result = await response.json();
    } catch (error) {
        result = { ok: false, error: "Could not reach the server. Is XAMPP running?" };
    } finally {
        saveButton.disabled    = false;
        saveButton.textContent = oldLabel;
    }

    // ---- May mali ----
    if (!result.ok) {
        showMessage(result.error, "err");

        // Kulayan ng pula ang mga partikular na kahon na tinutol ng server.
        if (result.fields) {
            if (result.fields.product_name) { markInvalid("productName", true); }
            if (result.fields.category)     { markInvalid("productCategory", true); }
            if (result.fields.price)        { markInvalid("productPrice", true); }
            if (result.fields.stock)        { markInvalid("productStock", true); }
        }
        return;
    }

    // ---- Tagumpay ----
    // I-reload ang pahina para makita agad ang bagong listahan.
    // Itatago muna ang mensahe para mabasa pa rin ito pagkatapos mag-reload.
    sessionStorage.setItem("productNotice", "✔ " + result.message);
    window.location.reload();
}


/* ---------------------------------------------------------------------
   BAHAGI 3: Pagbura ng produkto
   --------------------------------------------------------------------- */

/**
 * Burahin ang produkto matapos magtanong.
 */
async function deleteProduct(button) {
    const productId = button.getAttribute("data-id");
    const name      = button.getAttribute("data-name");

    if (!window.confirm('Delete "' + name + '"?\n\nThis cannot be undone.')) {
        return;
    }

    button.disabled    = true;
    button.textContent = "...";

    let result;

    try {
        const response = await fetch("api/delete_product.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify({ product_id: Number(productId) })
        });

        result = await response.json();
    } catch (error) {
        result = { ok: false, error: "Could not reach the server. Is XAMPP running?" };
    } finally {
        button.disabled    = false;
        button.textContent = "Delete";
    }

    if (!result.ok) {
        showMessage(result.error, "err");
        return;
    }

    sessionStorage.setItem("productNotice", "✔ " + result.message);
    window.location.reload();
}


/* ---------------------------------------------------------------------
   BAHAGI 4: Preview ng larawan
   --------------------------------------------------------------------- */

/**
 * Ipakita agad ang napiling larawan bago pa ito i-upload.
 */
function previewImage() {
    const fileBox = el("productImage");
    const preview = el("imagePreview");

    if (fileBox.files.length === 0) {
        return;
    }

    const file = fileBox.files[0];

    // 3 MB ang pinakamalaki - kapareho ng limit sa server.
    if (file.size > 3 * 1024 * 1024) {
        showMessage("That image is larger than 3 MB. Please choose a smaller file.", "err");
        fileBox.value = "";
        return;
    }

    preview.src = URL.createObjectURL(file);
    clearMessage();
}


/* ---------------------------------------------------------------------
   BAHAGI 5: Ikabit ang lahat kapag handa na ang pahina
   --------------------------------------------------------------------- */
document.addEventListener("DOMContentLoaded", function () {

    // Ipakita ang mensahe mula sa nakaraang pag-save o pagbura.
    const savedNotice = sessionStorage.getItem("productNotice");

    if (savedNotice) {
        showMessage(savedNotice, "ok");
        sessionStorage.removeItem("productNotice");
    }

    // Ang form.
    el("productForm").addEventListener("submit", submitForm);
    el("btnCancelEdit").addEventListener("click", resetForm);
    el("productImage").addEventListener("change", previewImage);

    // Alisin ang pula kapag nag-type na ang user.
    for (let i = 0; i < FORM_BOXES.length; i++) {
        const boxId = FORM_BOXES[i];

        el(boxId).addEventListener("input",  function () { markInvalid(boxId, false); });
        el(boxId).addEventListener("change", function () { markInvalid(boxId, false); });
    }

    // Ang Edit at Delete na button ng bawat row.
    const editButtons = document.querySelectorAll(".js-edit");
    for (let i = 0; i < editButtons.length; i++) {
        editButtons[i].addEventListener("click", function () {
            startEdit(this);
        });
    }

    const deleteButtons = document.querySelectorAll(".js-delete");
    for (let i = 0; i < deleteButtons.length; i++) {
        deleteButtons[i].addEventListener("click", function () {
            deleteProduct(this);
        });
    }
});
