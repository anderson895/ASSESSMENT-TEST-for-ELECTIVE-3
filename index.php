<?php
/**
 * index.php
 * -----------------------------------------------------------------
 * Ito ang pangunahing pahina ng Online Billing System.
 * Dito ipinapakita ang customer details, ang tatlong kategorya
 * ng produkto, ang mga button, at ang Bill Transactions.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/bootstrap.php';

// Kunin ang mga produkto mula sa database, naka-grupo ayon sa kategorya.
$grouped = array();
$dbError = null;

try {
    $productModel = new Product(db());
    $grouped      = $productModel->grouped();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

/**
 * Ginagawang ligtas ang teksto bago ipakita sa webpage.
 * Pumipigil ito sa HTML injection.
 */
function h($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MRA — Online Billing System</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="app-header">
    <img src="assets/img/logo.png" alt="MRA logo" class="app-logo"
         onerror="this.style.display='none'">
    <div class="app-title">
        <h1>ONLINE BILLING SYSTEM</h1>
        <p class="app-sub">Mika &bull; Ricky &bull; Angeline</p>
    </div>
</header>

<main class="container">

    <?php if ($dbError): ?>
        <div class="alert">
            <strong>Database not connected.</strong> <?= h($dbError) ?><br>
            Make sure MySQL is running in XAMPP and that you imported
            <code>database/online_billing.sql</code>.
        </div>
    <?php endif; ?>

    <!-- ===================== CUSTOMER DETAILS ===================== -->
    <section class="card">
        <h2 class="card-title">Customer Details</h2>
        <div class="customer-grid">
            <label>Customer Name
                <input type="text" id="customerName" placeholder="Full name">
            </label>
            <label>Contact Number
                <input type="text" id="contactNumber" placeholder="09xxxxxxxxx">
            </label>
            <label>Order Number
                <input type="text" id="orderNumber" placeholder="ORD-xxxx">
            </label>
            <div class="find-wrap">
                <button type="button" class="btn" id="btnFind">Find</button>
                <button type="button" class="btn btn-ghost" id="btnAddCustomer">Add Customer</button>
            </div>
        </div>
        <p class="hint"><strong>Find</strong> an existing customer by Contact Number or Order Number.
           <strong>Add Customer</strong> to register a new one. For billing, enter the
           <strong>Customer Name</strong>, <strong>Contact Number</strong>,
           and <strong>Order Number</strong> (all required).</p>
    </section>

    <!-- ===================== PRODUCT CATEGORIES ===================== -->
    <section class="categories">
        <?php foreach ($grouped as $category => $products): ?>
            <div class="card category-card">
                <h2 class="card-title"><?= h($category) ?></h2>
                <table class="product-table">
                    <thead>
                        <tr><th>Product</th><th>Price</th><th>Qty</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= h($p['product_name']) ?></td>
                            <td class="price">₱<?= number_format((float) $p['price'], 2) ?></td>
                            <td>
                                <input type="number" min="0" max="9999" step="1" value="0"
                                       inputmode="numeric" pattern="[0-9]*"
                                       class="qty"
                                       data-product-id="<?= (int) $p['product_id'] ?>"
                                       data-category="<?= h($p['category']) ?>"
                                       data-price="<?= (float) $p['price'] ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- ===================== BUTTONS ===================== -->
    <section class="card">
        <p class="form-notice" id="formNotice"></p>
        <div class="button-bar">
            <button type="button" class="btn" id="btnTotal">Total</button>
            <button type="button" class="btn" id="btnBill">Bill</button>
            <button type="button" class="btn" id="btnEmail">E-Mail</button>
            <button type="button" class="btn" id="btnPrint">Print</button>
            <button type="button" class="btn btn-ghost" id="btnClear">Clear</button>
        </div>
    </section>

    <!-- ===================== BILL TRANSACTIONS ===================== -->
    <section class="card" id="billTransactions">
        <h2 class="card-title">Bill Transactions</h2>

        <div class="bill-grid">
            <div class="bill-col">
                <h3>Category Totals</h3>
                <div class="bill-row"><span>Beauty &amp; Personal Care Total</span><output id="totBeauty">₱0.00</output></div>
                <div class="bill-row"><span>Grocery Total</span><output id="totGrocery">₱0.00</output></div>
                <div class="bill-row"><span>Beverages Total</span><output id="totBeverage">₱0.00</output></div>
            </div>

            <div class="bill-col">
                <h3>Taxes</h3>
                <div class="bill-row"><span>Beauty Tax</span><output id="taxBeauty">₱0.00</output></div>
                <div class="bill-row"><span>Grocery Tax</span><output id="taxGrocery">₱0.00</output></div>
                <div class="bill-row"><span>Beverage Tax</span><output id="taxBeverage">₱0.00</output></div>
            </div>

            <div class="bill-col">
                <h3>Overall Computation</h3>
                <div class="bill-row"><span>Subtotal</span><output id="subtotal">₱0.00</output></div>
                <div class="bill-row"><span>Total Tax</span><output id="totalTax">₱0.00</output></div>
                <div class="bill-row grand"><span>Grand Total</span><output id="grandTotal">₱0.00</output></div>
            </div>
        </div>
    </section>


</main>

<!-- ===================== RECEIPT POP-UP (Print / E-Mail) ===================== -->
<div class="modal-overlay hidden" id="receiptModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
        <div class="modal-head">
            <h2 id="modalTitle" class="card-title">Receipt Preview</h2>
            <button type="button" class="modal-close" id="modalClose" aria-label="Close">&times;</button>
        </div>

        <pre id="modalBody" class="receipt"></pre>

        <!-- E-Mail controls (shown only in e-mail mode) -->
        <div class="modal-email hidden" id="emailControls">
            <label for="emailTo">Send to e-mail address</label>
            <div class="email-row">
                <input type="email" id="emailTo" placeholder="customer@email.com">
                <button type="button" class="btn" id="btnSendEmail">Send</button>
            </div>
            <p class="email-status" id="emailStatus"></p>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn" id="modalPrint">Print</button>
            <button type="button" class="btn btn-ghost" id="modalDone">Close</button>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
