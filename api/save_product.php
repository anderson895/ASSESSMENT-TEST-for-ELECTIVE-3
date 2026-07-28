<?php
/**
 * api/save_product.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng products.php (Product Management page).
 *
 * Isang endpoint lang para sa dalawang trabaho:
 *   - product_id = 0  -> MAGDAGDAG ng bagong produkto
 *   - product_id > 0  -> I-UPDATE ang dating produkto
 *
 * MULTIPART ang tinatanggap nito (hindi JSON) dahil may kasamang
 * larawan na ina-upload. Kaya $_POST at $_FILES ang binabasa,
 * hindi ang json_input().
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

/** Saan itatago ang mga larawan (relatibo sa root ng project). */
define('IMAGE_FOLDER', 'assets/img/products');

/** Pinakamalaking laki ng larawan: 3 MB. */
define('MAX_IMAGE_BYTES', 3 * 1024 * 1024);

/**
 * Gawing malinis na filename ang pangalan ng produkto.
 * Halimbawa: "Coffee Drink (Large)" -> "coffee-drink-large"
 */
function slugify($text)
{
    $text = strtolower(trim((string) $text));

    // Anumang hindi letra o numero ay gagawing gitling.
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');

    if ($text == '') {
        $text = 'product';
    }
    return $text;
}

/**
 * Tanggapin ang ina-upload na larawan.
 *
 * Ibabalik:
 *   - string na path (halimbawa "assets/img/products/rice.jpg") kapag okay
 *   - null kapag walang ipinadalang larawan
 *   - hihinto na may error message kapag may problema
 */
function acceptUploadedImage($fileKey, $productName)
{
    // Walang pinadalang file - hindi ito error, ibig sabihin lang
    // walang bagong larawan (hindi gagalawin ang dati).
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fileKey];

    // Iba pang error mula sa PHP mismo.
    if ($file['error'] != UPLOAD_ERR_OK) {
        if ($file['error'] == UPLOAD_ERR_INI_SIZE || $file['error'] == UPLOAD_ERR_FORM_SIZE) {
            json_response(array('ok' => false, 'error' => 'The image is too large. Please use a file under 3 MB.'), 422);
        }
        json_response(array('ok' => false, 'error' => 'The image failed to upload. Please try again.'), 422);
    }

    if ($file['size'] > MAX_IMAGE_BYTES) {
        json_response(array('ok' => false, 'error' => 'The image is too large. Please use a file under 3 MB.'), 422);
    }

    // Siguraduhing TOTOONG larawan ito - hindi lang pinalitan ng .jpg
    // ang pangalan ng ibang file.
    $info = @getimagesize($file['tmp_name']);

    if ($info == false) {
        json_response(array('ok' => false, 'error' => 'That file is not a valid image. Please use a JPG, PNG, GIF, or WEBP.'), 422);
    }

    // Ang extension ay galing sa TOTOONG uri ng larawan, hindi sa pangalan
    // ng file na ipinadala - ito ang ligtas na paraan.
    $allowed = array(
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp'
    );

    if (!isset($allowed[$info[2]])) {
        json_response(array('ok' => false, 'error' => 'Only JPG, PNG, GIF, and WEBP images are allowed.'), 422);
    }

    $extension = $allowed[$info[2]];
    $folder    = dirname(__DIR__) . '/' . IMAGE_FOLDER;

    if (!is_dir($folder)) {
        @mkdir($folder, 0777, true);
    }

    // Hanapin ang filename na hindi pa gamit.
    $base     = slugify($productName);
    $filename = $base . '.' . $extension;
    $counter   = 2;

    while (file_exists($folder . '/' . $filename)) {
        $filename = $base . '-' . $counter . '.' . $extension;
        $counter  = $counter + 1;
    }

    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $filename)) {
        json_response(array('ok' => false, 'error' => 'Could not save the image on the server. Check folder permissions.'), 500);
    }

    return IMAGE_FOLDER . '/' . $filename;
}

/* ---------------------------------------------------------------------
   1) Basahin at suriin ang ipinadalang datos
   --------------------------------------------------------------------- */

$productId   = isset($_POST['product_id'])   ? (int) $_POST['product_id']            : 0;
$category    = isset($_POST['category'])     ? trim($_POST['category'])              : '';
$productName = isset($_POST['product_name']) ? trim($_POST['product_name'])          : '';
$priceRaw    = isset($_POST['price'])        ? trim(str_replace(',', '', $_POST['price'])) : '';
$stockRaw    = isset($_POST['stock'])        ? trim($_POST['stock'])                 : '';

$errors = array();

if ($productName == '') {
    $errors['product_name'] = 'Product name is required.';
} elseif (strlen($productName) > 120) {
    $errors['product_name'] = 'Product name is too long (120 characters maximum).';
}

if ($category == '') {
    $errors['category'] = 'Please choose a category.';
}

if ($priceRaw == '' || !is_numeric($priceRaw)) {
    $errors['price'] = 'Price must be a number.';
} elseif ((float) $priceRaw < 0) {
    $errors['price'] = 'Price cannot be negative.';
} elseif ((float) $priceRaw > 99999999.99) {
    $errors['price'] = 'Price is too large.';
}

if ($stockRaw === '' || !preg_match('/^\d+$/', $stockRaw)) {
    $errors['stock'] = 'Stock must be a whole number (0 or more).';
}

if (count($errors) > 0) {
    json_response(array(
        'ok'     => false,
        'error'  => 'Please fix the highlighted fields.',
        'fields' => $errors
    ), 422);
}

$price = round((float) $priceRaw, 2);
$stock = (int) $stockRaw;

/* ---------------------------------------------------------------------
   2) Itago sa database
   --------------------------------------------------------------------- */

try {
    $pdo   = db();
    $model = new Product($pdo);

    // Tingnan kung totoong kategorya ito.
    $validCategory = false;
    foreach ($model->categories() as $row) {
        if ($row['category_name'] == $category) {
            $validCategory = true;
            break;
        }
    }

    if (!$validCategory) {
        json_response(array(
            'ok'     => false,
            'error'  => 'That category does not exist.',
            'fields' => array('category' => 'Please choose a category from the list.')
        ), 422);
    }

    // ---- I-UPDATE ang dati ----
    if ($productId > 0) {
        $existing = $model->find($productId);

        if ($existing == null) {
            json_response(array('ok' => false, 'error' => 'That product no longer exists.'), 404);
        }

        if ($model->nameExists($category, $productName, $productId)) {
            json_response(array(
                'ok'     => false,
                'error'  => 'Another product in that category already uses this name.',
                'fields' => array('product_name' => 'This name is already taken.')
            ), 422);
        }

        // null = walang bagong larawan, kaya hindi gagalawin ang dati.
        $image = acceptUploadedImage('image', $productName);

        $model->update($productId, $category, $productName, $price, $stock, $image);

        json_response(array(
            'ok'      => true,
            'message' => 'Product updated.',
            'product' => $model->find($productId)
        ));
    }

    // ---- MAGDAGDAG ng bago ----
    if ($model->nameExists($category, $productName, 0)) {
        json_response(array(
            'ok'     => false,
            'error'  => 'That product already exists in this category.',
            'fields' => array('product_name' => 'This name is already taken.')
        ), 422);
    }

    $image = acceptUploadedImage('image', $productName);

    $newId = $model->create($category, $productName, $price, $stock, $image);

    json_response(array(
        'ok'      => true,
        'message' => 'Product added.',
        'product' => $model->find($newId)
    ));

} catch (Exception $e) {
    json_response(array('ok' => false, 'error' => 'Database error: ' . $e->getMessage()), 500);
}
