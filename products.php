<?php
/**
 * products.php
 * -----------------------------------------------------------------
 * PRODUCT MANAGEMENT — hiwalay na pahina para sa mga produkto.
 *
 * Dito puwedeng:
 *   - MAGDAGDAG ng bagong produkto (may larawan na ina-upload)
 *   - I-EDIT ang pangalan, kategorya, presyo, stock, at larawan
 *   - MAGBURA ng produkto (kung hindi pa ito naibenta)
 *
 * Ang pag-bill ay nasa index.php — hindi na magulo dahil hiwalay
 * na ang pamamahala ng produkto sa pag-transact.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/config/bootstrap.php';

$store = store();

$products   = array();
$categories = array();
$dbError    = null;

try {
    $pdo = db();

    $productModel = new Product($pdo);
    $products     = $productModel->all();
    $categories   = $productModel->categories();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

/**
 * Ginagawang ligtas ang teksto bago ipakita sa webpage.
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
    <title>Products — <?= h($store['name']) ?></title>
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
        <a href="index.php" class="nav-link">Billing / Cashier</a>
        <a href="products.php" class="nav-link active">Products</a>
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

    <p class="form-notice" id="formNotice"></p>

    <!-- ===================== ADD / EDIT FORM ===================== -->
    <section class="card">
        <h2 class="card-title" id="formTitle">Add New Product</h2>

        <form id="productForm" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" id="productId" name="product_id" value="0">

            <div class="product-form-grid">

                <label class="field">
                    <span class="field-label">Product Name</span>
                    <input type="text" id="productName" name="product_name"
                           placeholder="e.g. Instant Noodles" maxlength="120">
                </label>

                <label class="field">
                    <span class="field-label">Category</span>
                    <select id="productCategory" name="category">
                        <option value="">— Select category —</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= h($category['category_name']) ?>">
                                <?= h($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span class="field-label">Price (₱)</span>
                    <input type="text" id="productPrice" name="price"
                           inputmode="decimal" placeholder="0.00">
                </label>

                <label class="field">
                    <span class="field-label">Stock</span>
                    <input type="text" id="productStock" name="stock"
                           inputmode="numeric" placeholder="0">
                </label>

                <div class="field field-image">
                    <span class="field-label">Product Picture</span>
                    <div class="image-picker">
                        <img src="assets/img/products/placeholder.svg" alt="" class="image-preview"
                             id="imagePreview">
                        <div class="image-picker-controls">
                            <input type="file" id="productImage" name="image"
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <p class="hint hint-tight">
                                JPG, PNG, GIF, or WEBP — 3 MB max. Square pictures look best.
                                Leave this empty when editing to keep the current picture.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="button-bar button-bar-left">
                <button type="submit" class="btn btn-primary" id="btnSaveProduct">Add Product</button>
                <button type="button" class="btn btn-ghost hidden" id="btnCancelEdit">Cancel Edit</button>
            </div>
        </form>
    </section>

    <!-- ===================== PRODUCT LIST ===================== -->
    <section class="card">
        <div class="list-head">
            <h2 class="card-title">All Products</h2>
            <span class="count-badge"><?= count($products) ?> item<?= count($products) == 1 ? '' : 's' ?></span>
        </div>

        <div class="table-scroll">
            <table class="product-table manage-table">
                <thead>
                    <tr>
                        <th colspan="2">Product</th>
                        <th>Category</th>
                        <th class="col-price">Price</th>
                        <th class="col-stock">Stock</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="productRows">
                <?php if (count($products) == 0 && $dbError == null): ?>
                    <tr>
                        <td colspan="6" class="empty-row">
                            No products yet — add your first one using the form above.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($products as $p): ?>
                    <tr data-row-for="<?= (int) $p['product_id'] ?>">
                        <td class="col-thumb">
                            <img src="<?= h($p['image']) ?>" alt="<?= h($p['product_name']) ?>"
                                 class="product-img" loading="lazy"
                                 onerror="this.src='assets/img/products/placeholder.svg'">
                        </td>
                        <td class="col-name"><?= h($p['product_name']) ?></td>
                        <td class="col-category"><?= h($p['category']) ?></td>
                        <td class="col-price">₱<?= number_format($p['price'], 2) ?></td>
                        <td class="col-stock">
                            <span class="stock-badge<?= $p['stock'] <= 0 ? ' out' : ($p['stock'] <= 10 ? ' low' : '') ?>">
                                <?= (int) $p['stock'] ?>
                            </span>
                        </td>
                        <td class="col-actions">
                            <button type="button" class="btn btn-tiny js-edit"
                                    data-id="<?= (int) $p['product_id'] ?>"
                                    data-name="<?= h($p['product_name']) ?>"
                                    data-category="<?= h($p['category']) ?>"
                                    data-price="<?= h(number_format($p['price'], 2, '.', '')) ?>"
                                    data-stock="<?= (int) $p['stock'] ?>"
                                    data-image="<?= h($p['image']) ?>">Edit</button>
                            <button type="button" class="btn btn-tiny btn-danger js-delete"
                                    data-id="<?= (int) $p['product_id'] ?>"
                                    data-name="<?= h($p['product_name']) ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="hint">
            <strong>Edit</strong> loads the product into the form above.
            <strong>Delete</strong> only works for products that were never sold — a product that
            already appears in a past order cannot be removed without erasing it from those
            receipts, so set its <strong>Stock to 0</strong> instead to stop selling it.
        </p>
    </section>

</main>

<script src="<?= h(asset('assets/js/products.js')) ?>"></script>
</body>
</html>
