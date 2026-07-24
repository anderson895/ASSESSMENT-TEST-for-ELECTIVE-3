<?php
/**
 * index.php
 * Online Billing System - main interface.
 * Renders the product categories from the database and wires the buttons
 * to the OOP PHP API through assets/js/app.js.
 */
require_once __DIR__ . '/config/bootstrap.php';

try {
    $grouped  = (new Product(db()))->grouped();
    $dbError  = null;
} catch (Throwable $e) {
    $grouped  = [];
    $dbError  = $e->getMessage();
}

/** Escape helper. */
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Billing System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="app-header">
    <h1>ONLINE BILLING SYSTEM</h1>
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
            </div>
        </div>
        <p class="hint">Search an existing customer by <strong>Contact Number</strong> or <strong>Order Number</strong>.</p>
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
                                <input type="number" min="0" step="1" value="0"
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
    <section class="card button-bar">
        <button type="button" class="btn" id="btnTotal">Total</button>
        <button type="button" class="btn" id="btnBill">Bill</button>
        <button type="button" class="btn" id="btnEmail">E-Mail</button>
        <button type="button" class="btn" id="btnPrint">Print</button>
        <button type="button" class="btn btn-ghost" id="btnClear">Clear</button>
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

    <!-- ===================== EMAIL / RECEIPT OUTPUT ===================== -->
    <section class="card hidden" id="outputCard">
        <h2 class="card-title" id="outputTitle">Billing Details</h2>
        <pre id="outputBody" class="receipt"></pre>
    </section>

</main>

<script src="assets/js/app.js"></script>
</body>
</html>
