/* Step-by-step screenshot capture for the Online Billing System.
   Drives the real running app (XAMPP) and saves numbered PNGs into ./ */
import { chromium } from 'playwright';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://localhost/Diestro_Ricky/OnlineBillingSystem/index.php';
const shot = (page, name, opts = {}) =>
  page.screenshot({ path: path.join(__dirname, name), fullPage: true, ...opts });

const wait = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

// ---- 01: Home / initial interface ----
await page.goto(BASE, { waitUntil: 'networkidle' });
await wait(300);
await shot(page, '01-home.png');
console.log('01 home ✓');

// ---- 02: Type a contact number to search ----
await page.fill('#contactNumber', '09171234567');
const custCard = page.locator('section.card').first();
await custCard.screenshot({ path: path.join(__dirname, '02-find-input.png') });
console.log('02 find-input ✓');

// ---- 03: Click Find -> customer auto-fills ----
await page.click('#btnFind');
await page.waitForFunction(() => document.querySelector('#customerName').value.length > 0);
await wait(300);
await custCard.screenshot({ path: path.join(__dirname, '03-find-result.png') });
console.log('03 find-result ✓');

// ---- 04: Enter quantities in each category ----
async function setQty(category, index, value) {
  const el = page.locator(`.qty[data-category="${category}"]`).nth(index);
  await el.fill(String(value));
}
await setQty('Beauty & Personal Care', 0, 2); // Facial Cleanser x2
await setQty('Grocery', 0, 3);                // Rice x3
await setQty('Beverages', 0, 1);              // Mineral Water x1
await page.locator('section.categories').screenshot({ path: path.join(__dirname, '04-quantities.png') });
console.log('04 quantities ✓');

// ---- 05: Click Total -> category totals ----
await page.click('#btnTotal');
await page.waitForFunction(() => document.querySelector('#subtotal').textContent !== '₱0.00');
await wait(300);
await page.locator('#billTransactions').screenshot({ path: path.join(__dirname, '05-total.png') });
console.log('05 total ✓');

// ---- 06: Click Bill -> saves order (capture alert message) ----
let billMsg = '';
page.once('dialog', async (d) => { billMsg = d.message(); await d.accept(); });
await page.click('#btnBill');
await wait(600);
await page.locator('#billTransactions').screenshot({ path: path.join(__dirname, '06-bill-saved.png') });
console.log('06 bill-saved ✓ ->', billMsg);

// ---- 07: E-Mail -> receipt preview ----
page.once('dialog', async (d) => { await d.accept(''); }); // prompt: blank = display only
await page.click('#btnEmail');
await page.waitForSelector('#outputCard:not(.hidden)');
await wait(400);
await page.locator('#outputCard').screenshot({ path: path.join(__dirname, '07-email-receipt.png') });
console.log('07 email-receipt ✓');

// ---- 08: Print -> printable PDF of the receipt ----
await page.pdf({ path: path.join(__dirname, '08-print-receipt.pdf'), printBackground: true, format: 'A4' });
console.log('08 print-pdf ✓');

// ---- 09: Clear -> reset ----
page.once('dialog', async (d) => { await d.accept(); });
await page.click('#btnClear');
await wait(300);
await shot(page, '09-clear.png');
console.log('09 clear ✓');

await browser.close();

// ---- 10: Database view via phpMyAdmin (best-effort) ----
try {
  const b2 = await chromium.launch();
  const p2 = await b2.newPage({ viewport: { width: 1280, height: 900 } });
  const url = 'http://localhost/phpmyadmin/index.php?route=/sql&db=online_billing&table=orders&pos=0';
  const resp = await p2.goto(url, { waitUntil: 'networkidle', timeout: 15000 });
  await wait(800);
  await p2.screenshot({ path: path.join(__dirname, '10-database-orders.png'), fullPage: true });
  await b2.close();
  console.log('10 database ✓ (status', resp?.status(), ')');
} catch (e) {
  console.log('10 database skipped:', e.message);
}

console.log('DONE');
