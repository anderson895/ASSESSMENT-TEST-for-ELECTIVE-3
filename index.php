<?php
/**
 * index.php
 * -----------------------------------------------------------------
 * Ito ang pangunahing pahina ng MRA STORE Online Billing System.
 *
 * Dito makikita ang:
 *   - Cashier on Duty (may Employee ID, Position, at Shift)
 *   - AUTOMATIC na Order Number
 *   - Customer Details
 *   - Mga produkto kada kategorya (may LARAWAN, Price, at Stock)
 *   - Payment Method, Amount Received, at Discount
 *   - Bill Transactions (Subtotal, Discount, VAT 12%, Grand Total)
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/bootstrap.php';

$store = store();

// Kunin ang mga produkto at empleyado mula sa database.
$grouped   = array();
$employees = array();
$dbError   = null;

try {
    $pdo = db();

    $productModel  = new Product($pdo);
    $grouped       = $productModel->grouped();

    $employeeModel = new Employee($pdo);
    $employees     = $employeeModel->allActive();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

/**
 * Ginagawang ligtas ang teksto bago ipakita sa webpage.
 * Pumipigil ito sa HTML injection.
 */
function h($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($store['name']) ?> — Online Billing System</title>
    <link rel="icon" type="image/png" href="<?= h($store['logo']) ?>">
    <link rel="stylesheet" href="<?= h(asset('assets/css/style.css')) ?>">

    <?php /* Ipapakita ang anumang error ng JavaScript bilang pulang banner. */ ?>
    <?php require __DIR__ . '/config/error_banner.php'; ?>
</head>
<body>

<!-- ===================== HEADER ===================== -->
<header class="app-header">
    <div class="app-header-inner">
        <img src="<?= h($store['logo']) ?>" alt="<?= h($store['name']) ?> logo" class="app-logo"
             onerror="this.style.display='none'">

        <div class="app-title">
            <h1><?= h($store['name']) ?></h1>
            <p class="app-sub"><?= h($store['tagline']) ?></p>
        </div>

        <div class="app-store-info">
            <span><?= h($store['address']) ?></span>
            <span><?= h($store['contact']) ?></span>
            <span><?= h($store['email']) ?></span>
        </div>
    </div>
    <p class="app-strip">Online Billing System</p>
</header>

<!-- ===================== NAV ===================== -->
<nav class="app-nav">
    <div class="app-nav-inner">
        <a href="index.php" class="nav-link active">Billing / Cashier</a>
        <a href="products.php" class="nav-link">Products</a>
    </div>
</nav>

<main class="container">

    <?php if ($dbError): ?>
        <div class="alert">
            <strong>Database not connected.</strong> <?= h($dbError) ?><br>
            Make sure MySQL is running in XAMPP and that you imported
            <code>database/online_billing.sql</code>.
        </div>
    <?php endif; ?>

    <!-- ===================== STEP 1: CASHIER + ORDER NUMBER ===================== -->
    <section class="card counter-card step-card">
        <h2 class="card-title step-title">
            <span class="step-no">1</span>
            Who is on duty?
            <span class="step-hint">Pick the cashier — the Order No. fills in by itself.</span>
        </h2>

        <div class="counter-grid">

            <label class="field">
                <span class="field-label">Cashier on Duty</span>
                <select id="cashierSelect">
                    <option value="">— Select cashier —</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= (int) $employee['employee_id'] ?>"
                                data-code="<?= h($employee['employee_code']) ?>"
                                data-position="<?= h($employee['position']) ?>"
                                data-shift="<?= h($employee['shift']) ?>">
                            <?= h($employee['employee_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="field">
                <span class="field-label">Employee ID</span>
                <output class="field-static" id="cashierCode">—</output>
            </div>

            <div class="field">
                <span class="field-label">Position</span>
                <output class="field-static" id="cashierPosition">—</output>
            </div>

            <div class="field">
                <span class="field-label">Shift</span>
                <output class="field-static" id="cashierShift">—</output>
            </div>

            <div class="field field-order">
                <span class="field-label">Order No. <em>(automatic)</em></span>
                <output class="field-static field-order-no" id="orderNumber">—</output>
            </div>

        </div>
    </section>

    <!-- ===================== STEP 2: CUSTOMER DETAILS ===================== -->
    <section class="card step-card">
        <h2 class="card-title step-title">
            <span class="step-no">2</span>
            Who is buying?
            <span class="step-hint">Type a new customer, or Find an existing one.</span>
        </h2>

        <div class="customer-grid">
            <label class="field">
                <span class="field-label">Customer Name</span>
                <input type="text" id="customerName" placeholder="Full name">
            </label>

            <label class="field">
                <span class="field-label">Contact Number <em>(optional)</em></span>
                <input type="text" id="contactNumber" placeholder="09xxxxxxxxx or ORD-20260728-0001">
            </label>

            <div class="find-wrap">
                <button type="button" class="btn" id="btnFind">Find</button>
                <button type="button" class="btn btn-ghost" id="btnAddCustomer">Add Customer</button>
            </div>
        </div>

        <p class="hint">
            Only the <strong>Customer Name</strong> is required — leave the Contact Number blank
            for a walk-in. <strong>Find</strong> looks up a past customer by Contact Number
            <em>or</em> by any <strong>Order Number</strong> printed on an old receipt, and lists
            their previous orders below. <strong>Add Customer</strong> registers a new one. The
            <strong>Order Number is generated automatically</strong> when you press Bill.
        </p>
    </section>

    <!-- ===================== ORDER HISTORY (Find button) ===================== -->
    <section class="card hidden" id="orderHistory">
        <h2 class="card-title">Order History</h2>
        <p class="history-for" id="historyFor"></p>
        <div id="historyList"></div>
    </section>

    <!-- ===================== STEP 3: PRODUCT CATEGORIES ===================== -->
    <section class="card step-card">
        <h2 class="card-title step-title">
            <span class="step-no">3</span>
            What are they buying?
            <span class="step-hint">Use &minus; and + to set the quantity. Totals update by themselves.</span>
        </h2>
    </section>

    <section class="categories">
        <?php foreach ($grouped as $categoryName => $category): ?>
            <div class="card category-card">

                <div class="category-head">
                    <img src="<?= h($category['image']) ?>" alt="<?= h($categoryName) ?>"
                         class="category-img" onerror="this.style.display='none'">
                    <h2 class="category-name"><?= h($categoryName) ?></h2>
                </div>

                <table class="product-table">
                    <thead>
                        <tr>
                            <th colspan="2">Product</th>
                            <th class="col-price">Price</th>
                            <th class="col-stock">Stock</th>
                            <th class="col-qty">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($category['products'] as $p): ?>
                        <tr>
                            <td class="col-thumb">
                                <img src="<?= h($p['image']) ?>" alt="<?= h($p['product_name']) ?>"
                                     class="product-img" loading="lazy"
                                     onerror="this.src='assets/img/products/placeholder.svg'">
                            </td>
                            <td class="col-name"><?= h($p['product_name']) ?></td>
                            <td class="col-price">₱<?= number_format($p['price'], 2) ?></td>
                            <td class="col-stock">
                                <span class="stock-badge<?= $p['stock'] <= 0 ? ' out' : ($p['stock'] <= 10 ? ' low' : '') ?>"
                                      data-stock-for="<?= (int) $p['product_id'] ?>">
                                    <?= (int) $p['stock'] ?>
                                </span>
                            </td>
                            <td class="col-qty">
                                <div class="qty-wrap">
                                    <button type="button" class="qty-btn qty-minus" tabindex="-1"
                                            aria-label="Remove one <?= h($p['product_name']) ?>"
                                            <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>&minus;</button>

                                    <input type="number" min="0" max="<?= (int) $p['stock'] ?>" step="1" value="0"
                                           inputmode="numeric" pattern="[0-9]*"
                                           class="qty"
                                           <?= $p['stock'] <= 0 ? 'disabled' : '' ?>
                                           data-product-id="<?= (int) $p['product_id'] ?>"
                                           data-category="<?= h($p['category']) ?>"
                                           data-stock="<?= (int) $p['stock'] ?>"
                                           data-price="<?= (float) $p['price'] ?>">

                                    <button type="button" class="qty-btn qty-plus" tabindex="-1"
                                            aria-label="Add one <?= h($p['product_name']) ?>"
                                            <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>+</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        <?php endforeach; ?>
    </section>

    <p class="form-notice" id="formNotice"></p>

    <!-- ===================== STEP 4: ORDER SUMMARY ===================== -->
    <section class="card step-card" id="billTransactions">
        <h2 class="card-title step-title">
            <span class="step-no">4</span>
            Check the order
            <span class="step-hint">This updates by itself — no need to press Total.</span>
        </h2>

        <!-- Listahan ng napiling item (ginagawa ng JavaScript). -->
        <p class="cart-empty" id="cartEmpty">No items selected yet — set a quantity in Step 3.</p>
        <ul class="cart-list hidden" id="cartList"></ul>

        <div class="bill-grid">

            <div class="bill-col">
                <h3>Category Totals</h3>
                <?php foreach ($grouped as $categoryName => $category): ?>
                    <div class="bill-row">
                        <span><?= h($categoryName) ?></span>
                        <output data-cat-total="<?= h($categoryName) ?>">₱0.00</output>
                    </div>
                <?php endforeach; ?>
                <div class="bill-row">
                    <span>Total Items</span>
                    <output id="totalItems">0</output>
                </div>
            </div>

            <div class="bill-col">
                <h3>Computation</h3>
                <div class="bill-row"><span>Subtotal</span><output id="subtotal">₱0.00</output></div>
                <div class="bill-row"><span id="discountLabel">Discount</span><output id="discountOut">₱0.00</output></div>
                <div class="bill-row"><span>VATable Sales</span><output id="vatableSales">₱0.00</output></div>
                <div class="bill-row"><span>VAT (12%)</span><output id="vatAmount">₱0.00</output></div>
                <div class="bill-row grand"><span>Grand Total</span><output id="grandTotal">₱0.00</output></div>
            </div>

            <div class="bill-col">
                <h3>Payment</h3>
                <div class="bill-row"><span>Payment Method</span><output id="methodOut">Cash</output></div>
                <div class="bill-row"><span>Amount Received</span><output id="receivedOut">₱0.00</output></div>
                <div class="bill-row change"><span>Change</span><output id="changeDue">₱0.00</output></div>
            </div>

        </div>
    </section>

    <!-- ===================== STEP 5: PAYMENT & DISCOUNT ===================== -->
    <section class="card step-card">
        <h2 class="card-title step-title">
            <span class="step-no">5</span>
            How are they paying?
            <span class="step-hint">Change the discount here — the totals above follow along.</span>
        </h2>

        <div class="payment-grid">

            <!-- ---- Payment Method ---- -->
            <div class="pay-block">
                <span class="field-label">Payment Method</span>
                <div class="choice-row">
                    <label class="choice">
                        <input type="radio" name="paymentMethod" value="Cash" checked>
                        <span class="choice-box">Cash</span>
                    </label>
                    <label class="choice">
                        <input type="radio" name="paymentMethod" value="GCash">
                        <span class="choice-box">GCash</span>
                    </label>
                    <label class="choice">
                        <input type="radio" name="paymentMethod" value="Card">
                        <span class="choice-box">Card</span>
                    </label>
                </div>
            </div>

            <!-- ---- Amount Received / Change ---- -->
            <div class="pay-block">
                <label class="field">
                    <span class="field-label">Amount Received</span>
                    <input type="text" id="amountReceived" inputmode="decimal"
                           placeholder="0.00" value="">
                </label>
                <p class="pay-note" id="amountNote">Cash payment — enter the amount handed over.</p>
            </div>

            <!-- ---- Discount ---- -->
            <div class="pay-block">
                <label class="field">
                    <span class="field-label">Discount Type</span>
                    <select id="discountType">
                        <option value="None">No Discount</option>
                        <option value="Senior Citizen">Senior Citizen (20%)</option>
                        <option value="PWD">PWD (20%)</option>
                        <option value="Promo">Promo (10%)</option>
                    </select>
                </label>
                <div class="pay-inline">
                    <span>Discount Amount</span>
                    <output id="discountAmount">₱0.00</output>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================== ACTION BAR (sticky sa ilalim) ===================== -->
    <section class="action-bar">
        <div class="action-live">
            <span class="action-live-label">Grand Total</span>
            <span class="action-live-total" id="liveGrandTotal">₱0.00</span>
            <span class="action-live-items" id="liveItems">No items yet</span>
        </div>

        <div class="button-bar">
            <button type="button" class="btn btn-ghost" id="btnClear">Clear</button>
            <button type="button" class="btn" id="btnTotal">Total</button>
            <button type="button" class="btn btn-primary" id="btnBill">Bill</button>
            <button type="button" class="btn" id="btnPrint" disabled
                    title="Save the bill first">Print</button>
            <button type="button" class="btn" id="btnEmail" disabled
                    title="Save the bill first">E-Mail</button>
        </div>
    </section>

</main>

<!-- ===================== RECEIPT POP-UP (Bill / Print / E-Mail) ===================== -->
<div class="modal-overlay hidden" id="receiptModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
        <div class="modal-head">
            <h2 id="modalTitle" class="card-title">Receipt Preview</h2>
            <button type="button" class="modal-close" id="modalClose" aria-label="Close">&times;</button>
        </div>

        <!-- Dito inilalagay ang resibong ginawa ng Receipt class (PHP). -->
        <div id="modalBody" class="receipt-wrap"></div>

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

<script src="<?= h(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
