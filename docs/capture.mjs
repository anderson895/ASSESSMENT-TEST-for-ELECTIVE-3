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

async function setQty(cat, index, value) {
  const el = page.locator(`.qty[data-category="${cat}"]`).nth(index);
  await el.click(); await el.press('Control+A'); await el.press('Delete');
  await el.type(String(value));
}

// 01: Home
await page.goto(BASE, { waitUntil: 'networkidle' });
await wait(300);
await shot(page, '01-home.png');
console.log('01 home ✓');

// 02: Find input
await page.fill('#contactNumber', '09171234567');
const custCard = page.locator('section.card').first();
await custCard.screenshot({ path: path.join(__dirname, '02-find-input.png') });
console.log('02 find-input ✓');

// 03: Find result
await page.click('#btnFind');
await page.waitForFunction(() => document.querySelector('#customerName').value.length > 0);
await wait(200);
await custCard.screenshot({ path: path.join(__dirname, '03-find-result.png') });
console.log('03 find-result ✓');

// 04: Quantities
await setQty('Beauty & Personal Care', 0, 2);
await setQty('Grocery', 0, 3);
await setQty('Beverages', 0, 1);
await page.locator('section.categories').screenshot({ path: path.join(__dirname, '04-quantities.png') });
console.log('04 quantities ✓');

// 05: Total
await page.click('#btnTotal');
await page.waitForFunction(() => document.querySelector('#grandTotal').textContent !== '₱0.00');
await wait(200);
await page.locator('#billTransactions').screenshot({ path: path.join(__dirname, '05-total.png') });
console.log('05 total ✓');

// 06: Bill (save)
let billMsg = '';
page.once('dialog', async (d) => { billMsg = d.message(); await d.accept(); });
await page.click('#btnBill');
await wait(600);
await page.locator('#billTransactions').screenshot({ path: path.join(__dirname, '06-bill-saved.png') });
console.log('06 bill-saved ✓ ->', billMsg);

// 07: Print pop-up modal
await page.click('#btnPrint');
await page.waitForFunction(() => !document.querySelector('#receiptModal').classList.contains('hidden'));
await wait(300);
await shot(page, '07-print-modal.png');
console.log('07 print-modal ✓');

// 07b: printable PDF (print CSS shows only the receipt)
await page.emulateMedia({ media: 'print' });
await page.pdf({ path: path.join(__dirname, '07b-print-receipt.pdf'), printBackground: true, format: 'A5' });
await page.emulateMedia({ media: 'screen' });
await page.click('#modalClose');
await page.waitForFunction(() => document.querySelector('#receiptModal').classList.contains('hidden'));
console.log('07b print-pdf ✓');

// 08: E-Mail modal + send
await page.click('#btnEmail');
await page.waitForFunction(() => !document.querySelector('#receiptModal').classList.contains('hidden'));
await page.fill('#emailTo', 'sophia.reyes@email.com');
await page.click('#btnSendEmail');
await page.waitForFunction(() => document.querySelector('#emailStatus').textContent.length > 0);
await wait(300);
await shot(page, '08-email-modal.png');
await page.click('#modalDone');
console.log('08 email-modal ✓');

// 09: Clear
page.once('dialog', async (d) => { await d.accept(); });
await page.click('#btnClear');
await wait(300);
await shot(page, '09-clear.png');
console.log('09 clear ✓');

await browser.close();

// 10: Database view via phpMyAdmin (best-effort)
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
