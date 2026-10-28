/**
 * Trivikratech POS - Interactive Client-Side Cart & Checkout Controller
 */

let cart = [];
let selectedPaymentMethod = 'Cash';
const GST_RATE = 0.03; // 3% GST on Bullion & Jewellery

function addToCart(el) {
  const id = el.getAttribute('data-id');
  const name = el.getAttribute('data-name');
  const sku = el.getAttribute('data-sku');
  const purity = el.getAttribute('data-purity');
  const price = parseFloat(el.getAttribute('data-price')) || 0;
  const weight = parseFloat(el.getAttribute('data-weight')) || 0;
  const making = parseFloat(el.getAttribute('data-making')) || 0;
  const makingType = el.getAttribute('data-making-type') || 'per_gram';
  let makingVal = parseFloat(el.getAttribute('data-making-val'));
  if (isNaN(makingVal) || makingVal === 0) {
    if (makingType === 'percentage') {
      makingVal = 0;
    } else {
      makingVal = weight > 0 ? Math.round(making / weight) : making;
    }
  }
  const stock = parseInt(el.getAttribute('data-stock')) || 0;

  if (stock <= 0) {
    showToast('Item is out of stock!');
    return;
  }

  const existing = cart.find(i => i.id === id);
  if (existing) {
    if (existing.qty >= stock) {
      showToast('Cannot exceed available stock!');
      return;
    }
    existing.qty += 1;
  } else {
    cart.push({ id, name, sku, purity, price, weight, making, makingType, makingVal, stock, qty: 1, discount: 0 });
  }

  renderCart();
  showToast(`Added ${name} to cart`);
}

function updateQty(id, delta) {
  const item = cart.find(i => i.id === id);
  if (!item) return;

  const newQty = item.qty + delta;
  if (newQty <= 0) {
    cart = cart.filter(i => i.id !== id);
  } else if (newQty > item.stock) {
    showToast('Cannot exceed available stock!');
    return;
  } else {
    item.qty = newQty;
  }

  renderCart();
}

function removeItem(id) {
  cart = cart.filter(i => i.id !== id);
  renderCart();
}

function renderCart() {
  const listEl = document.getElementById('cartItemsList');
  const emptyEl = document.getElementById('emptyCartMessage');
  const checkoutBtn = document.getElementById('btnCheckout');

  if (cart.length === 0) {
    listEl.innerHTML = '';
    listEl.appendChild(emptyEl);
    emptyEl.style.display = 'flex';
    checkoutBtn.disabled = true;
    recalculateCart();
    return;
  }

  let html = '';
  cart.forEach(item => {
    const lineTotal = (item.price * item.qty) - item.discount;
    const totalItemMaking = Math.round(item.making * item.qty);
    
    let makingTag = '';
    if (item.makingType === 'percentage') {
      makingTag = `<span style="display: inline-flex; align-items: center; gap: 4px; background: #EFF6FF; color: #1D4ED8; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px; border: 1px solid #BFDBFE;">
        ⚡ Making: ${item.makingVal}% (₹${totalItemMaking.toLocaleString('en-IN')})
      </span>`;
    } else if (item.makingType === 'fixed') {
      makingTag = `<span style="display: inline-flex; align-items: center; gap: 4px; background: #FEF3C7; color: #B45309; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px; border: 1px solid #FDE68A;">
        ⚡ Making: Flat ₹${totalItemMaking.toLocaleString('en-IN')}
      </span>`;
    } else {
      const perGramRate = item.makingVal > 0 ? item.makingVal : (item.weight > 0 ? Math.round(item.making / item.weight) : item.making);
      makingTag = `<span style="display: inline-flex; align-items: center; gap: 4px; background: #F0FDFA; color: #0F766E; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px; border: 1px solid #CCFBF1;">
        ⚡ Making: ₹${perGramRate}/g (₹${totalItemMaking.toLocaleString('en-IN')})
      </span>`;
    }

    html += `
      <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; box-shadow: var(--shadow-xs);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
          <div>
            <div style="font-weight: 700; font-size: 13.5px; color: #111827;">${item.name}</div>
            <div style="font-size: 11px; color: #6B7280; margin-top: 2px;">${item.purity} · ${item.weight}g · ${item.sku}</div>
            <div style="margin-top: 5px;">
              ${makingTag}
            </div>
          </div>
          <button type="button" onclick="removeItem('${item.id}')" style="background: none; border: none; color: #DC2626; cursor: pointer; padding: 2px;" title="Remove item">
            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
          </button>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; border-top: 1px dashed var(--color-border); padding-top: 6px;">
          <!-- Qty Adjuster -->
          <div style="display: flex; align-items: center; gap: 6px; background: var(--color-surface-2); border-radius: 6px; padding: 2px 6px;">
            <button type="button" onclick="updateQty('${item.id}', -1)" style="border: none; background: none; cursor: pointer; font-weight: 800; font-size: 14px; width: 18px; text-align: center;">-</button>
            <span style="font-size: 12px; font-weight: 700; min-width: 16px; text-align: center;">${item.qty}</span>
            <button type="button" onclick="updateQty('${item.id}', 1)" style="border: none; background: none; cursor: pointer; font-weight: 800; font-size: 14px; width: 18px; text-align: center;">+</button>
          </div>

          <!-- Price -->
          <div style="font-weight: 800; font-size: 14px; color: var(--color-primary-dark);">
            ₹${lineTotal.toLocaleString('en-IN')}
          </div>
        </div>
      </div>
    `;
  });

  listEl.innerHTML = html;
  checkoutBtn.disabled = false;
  recalculateCart();
  if (typeof lucide !== 'undefined') lucide.createIcons();
}

let selectedExchangeId = null;
let selectedExchangeCredit = 0;
let appliedExchangeCredit = 0;
let selectedExchangeNo = '';

async function onCustomerChanged(selectEl) {
  const custId = selectEl.value;
  const voucherSelect = document.getElementById('posExchangeSelect');
  const noticeEl = document.getElementById('exchangeCreditNotice');

  // Reset current selection
  selectedExchangeId = null;
  selectedExchangeCredit = 0;
  appliedExchangeCredit = 0;
  selectedExchangeNo = '';
  if (voucherSelect) {
    voucherSelect.innerHTML = '<option value="">-- No Exchange Credit Applied --</option>';
  }
  if (noticeEl) noticeEl.style.display = 'none';

  recalculateCart();

  if (!custId) return;

  try {
    const res = await fetch(`api/old_gold_exchange.php?action=get_available&customer_id=${custId}`);
    const data = await res.json();
    if (data.success && data.exchanges && data.exchanges.length > 0) {
      data.exchanges.forEach(ex => {
        const opt = document.createElement('option');
        opt.value = ex.id;
        opt.setAttribute('data-no', ex.exchange_no);
        opt.setAttribute('data-val', ex.credit_balance);
        opt.setAttribute('data-wt', ex.total_net_weight);
        opt.innerText = `⭐ ${ex.exchange_no} (₹${Number(ex.credit_balance).toLocaleString('en-IN')} · ${ex.total_net_weight}g)`;
        voucherSelect.appendChild(opt);
      });

      // Auto-select first voucher if available
      voucherSelect.selectedIndex = 1;
      onExchangeVoucherSelected(voucherSelect);
      showToast(`${data.exchanges.length} Old Gold Credit Voucher(s) found!`);
    }
  } catch (err) {
    console.error('Error fetching exchange vouchers:', err);
  }
}

function onExchangeVoucherSelected(selectEl) {
  const opt = selectEl.options[selectEl.selectedIndex];
  const noticeEl = document.getElementById('exchangeCreditNotice');

  if (!opt || !opt.value) {
    selectedExchangeId = null;
    selectedExchangeCredit = 0;
    appliedExchangeCredit = 0;
    selectedExchangeNo = '';
    if (noticeEl) noticeEl.style.display = 'none';
  } else {
    selectedExchangeId = parseInt(opt.value);
    selectedExchangeCredit = parseFloat(opt.getAttribute('data-val')) || 0;
    selectedExchangeNo = opt.getAttribute('data-no') || 'OGE';
  }

  recalculateCart();
}

function recalculateCart() {
  const subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
  const totalMaking = cart.reduce((sum, i) => sum + (i.making * i.qty), 0);
  const gst = Math.round(subtotal * GST_RATE);
  const orderDiscount = parseFloat(document.getElementById('posDiscount')?.value) || 0;
  const grossBill = Math.max(0, subtotal + gst - orderDiscount);

  // Old Gold Exchange Credit Calculation
  const creditRow = document.getElementById('posExchangeCreditRow');
  const creditLabel = document.getElementById('posExchangeCreditLabel');
  const creditText = document.getElementById('posExchangeCreditText');
  const noticeEl = document.getElementById('exchangeCreditNotice');

  if (selectedExchangeId && selectedExchangeCredit > 0) {
    appliedExchangeCredit = Math.min(grossBill, selectedExchangeCredit);
    const remainingVoucherBal = Math.max(0, selectedExchangeCredit - appliedExchangeCredit);

    if (creditRow) {
      creditRow.style.display = 'flex';
      if (creditLabel) creditLabel.innerText = `− Old Gold Credit (${selectedExchangeNo}):`;
      if (creditText) creditText.innerText = `-₹${appliedExchangeCredit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    if (noticeEl) {
      noticeEl.style.display = 'block';
      noticeEl.innerHTML = `✓ Voucher <strong>${selectedExchangeNo}</strong>: ₹${appliedExchangeCredit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} credit applied. (Remaining voucher balance: ₹${remainingVoucherBal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })})`;
    }
  } else {
    appliedExchangeCredit = 0;
    if (creditRow) creditRow.style.display = 'none';
    if (noticeEl) noticeEl.style.display = 'none';
  }

  const grandTotal = Math.max(0, grossBill - appliedExchangeCredit);

  document.getElementById('posSubtotal').innerText = '₹' + subtotal.toLocaleString('en-IN');
  document.getElementById('posMakingCharges').innerText = '₹' + totalMaking.toLocaleString('en-IN');
  
  const makingLabelEl = document.getElementById('posMakingLabel');
  if (makingLabelEl) {
    const percentItems = cart.filter(i => i.makingType === 'percentage');
    if (percentItems.length > 0) {
      const pStr = percentItems.map(i => `${i.makingVal}%`).filter((v, idx, arr) => arr.indexOf(v) === idx).join(', ');
      makingLabelEl.innerHTML = `Total Making Charges <span style="font-size: 11px; color: #1D4ED8; font-weight: 700; background: #EFF6FF; padding: 2px 6px; border-radius: 4px; border: 1px solid #BFDBFE;">(${pStr} in %)</span>:`;
    } else {
      makingLabelEl.innerText = 'Total Making Charges:';
    }
  }

  document.getElementById('posGst').innerText = '₹' + gst.toLocaleString('en-IN');
  document.getElementById('posGrandTotal').innerText = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function selectPaymentMethod(method, btnEl) {
  selectedPaymentMethod = method;
  document.querySelectorAll('.pay-btn').forEach(b => {
    b.style.background = 'var(--color-surface-2)';
    b.style.color = 'var(--color-text-1)';
    b.classList.remove('active');
  });
  btnEl.style.background = 'var(--color-primary)';
  btnEl.style.color = '#fff';
  btnEl.classList.add('active');
}

// Product Search & Metal Tab Filtering
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('posSearch');
  const barcodeInput = document.getElementById('barcodeScanInput');
  const metalTabs = document.querySelectorAll('.metal-tab');

  let activeMetal = 'All';

  function filterCards() {
    const query = (searchInput.value || '').toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      const sku = (card.getAttribute('data-sku') || '').toLowerCase();
      const metal = card.getAttribute('data-metal') || '';
      const barcode = card.getAttribute('data-barcode') || '';

      const matchMetal = activeMetal === 'All' || metal === activeMetal;
      const matchQuery = !query || name.includes(query) || sku.includes(query) || barcode.includes(query);

      if (matchMetal && matchQuery) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });
  }

  if (searchInput) searchInput.addEventListener('input', filterCards);

  metalTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      metalTabs.forEach(t => {
        t.style.background = 'transparent';
        t.style.color = 'var(--color-text-2)';
        t.classList.remove('active');
      });
      tab.style.background = '#fff';
      tab.style.color = 'var(--color-primary)';
      tab.style.fontWeight = '700';
      tab.classList.add('active');
      activeMetal = tab.getAttribute('data-filter');
      filterCards();
    });
  });

  // Barcode scanning
  if (barcodeInput) {
    barcodeInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const code = barcodeInput.value.trim();
        if (!code) return;
        const matchingCard = document.querySelector(`.product-card[data-barcode="${code}"]`);
        if (matchingCard) {
          addToCart(matchingCard);
          barcodeInput.value = '';
        } else {
          showToast(`No item found for barcode: ${code}`);
        }
      }
    });
  }

  // Pre-selected Exchange Voucher check
  const exSelect = document.getElementById('posExchangeSelect');
  if (exSelect && exSelect.value) {
    onExchangeVoucherSelected(exSelect);
  }
});

// Submit Sale to Server
async function submitPOSSale() {
  if (cart.length === 0) return;

  const custSelect = document.getElementById('posCustomerSelect');
  const customerId = custSelect.value || null;
  const customerName = custSelect.options[custSelect.selectedIndex]?.getAttribute('data-name') || 'Walk-in Customer';
  const discount = parseFloat(document.getElementById('posDiscount')?.value) || 0;

  const subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
  const makingCharges = cart.reduce((sum, i) => sum + (i.making * i.qty), 0);
  const gst = Math.round(subtotal * GST_RATE);
  const grossBill = Math.max(0, subtotal + gst - discount);
  const finalPayable = Math.max(0, grossBill - appliedExchangeCredit);

  const payload = {
    customer_id: customerId,
    customer_name: customerName,
    payment_method: (appliedExchangeCredit > 0 && finalPayable <= 0.01) ? 'Gold Exchange' : selectedPaymentMethod,
    discount: discount,
    subtotal: subtotal,
    making_charges: makingCharges,
    gst: gst,
    grand_total: finalPayable,
    exchange_id: selectedExchangeId,
    exchange_credit_applied: appliedExchangeCredit,
    cart_items: cart
  };

  try {
    const res = await fetch('api/pos.php?action=complete_sale', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    const soldItems = JSON.parse(JSON.stringify(cart));
    const usedExchangeCredit = appliedExchangeCredit;
    const usedExchangeNo = selectedExchangeNo;

    if (data.success) {
      showSaleReceipt(data.order_no, data.invoice_no, customerName, finalPayable, subtotal, gst, discount, data, soldItems, usedExchangeCredit, usedExchangeNo);
      // Reset POS Cart & Exchange selection
      cart = [];
      selectedExchangeId = null;
      selectedExchangeCredit = 0;
      appliedExchangeCredit = 0;
      selectedExchangeNo = '';
      const exSelect = document.getElementById('posExchangeSelect');
      if (exSelect) exSelect.value = '';
      const notEl = document.getElementById('exchangeCreditNotice');
      if (notEl) notEl.style.display = 'none';

      renderCart();
      if (document.getElementById('posDiscount')) document.getElementById('posDiscount').value = 0;
      showToast('Sale successfully registered!');
    } else {
      alert('Sale Error: ' + (data.error || 'Unable to complete order.'));
    }
  } catch (err) {
    console.error(err);
    alert('Server connection error while saving sale.');
  }
}

function showSaleReceipt(orderNo, invoiceNo, customerName, total, subtotal, gst, discount, storeData = null, items = [], exchangeCredit = 0, exchangeNo = '') {
  const modal = document.getElementById('saleSuccessModal');
  const content = document.getElementById('printReceiptContent');

  const sName = storeData?.store_name || window.STORE_INFO?.name || 'Kulthe Suvarnkar';
  const sAddress = storeData?.store_address || window.STORE_INFO?.address || 'Bandra West, Mumbai';
  const sGstin = storeData?.store_gstin || window.STORE_INFO?.gstin || '';
  const sPhone = storeData?.store_phone || window.STORE_INFO?.phone || '';

  const cgst = Math.round(gst / 2);
  const sgst = gst - cgst;

  let itemsRows = '';
  items.forEach((item, index) => {
    const lineTotal = (item.price * item.qty);
    const itemMakingTotal = (item.making || 0) * item.qty;
    let makingColHtml = '';
    if (item.makingType === 'percentage') {
      makingColHtml = `<div>₹${itemMakingTotal.toLocaleString('en-IN')}</div><div style="font-size: 9.5px; color: #1D4ED8; font-weight: 700;">(${item.makingVal}% on Gold)</div>`;
    } else if (item.makingType === 'fixed') {
      makingColHtml = `<div>₹${itemMakingTotal.toLocaleString('en-IN')}</div><div style="font-size: 9.5px; color: #B45309; font-weight: 600;">(Fixed)</div>`;
    } else {
      const perG = item.makingVal > 0 ? item.makingVal : (item.weight > 0 ? Math.round(item.making / item.weight) : item.making);
      makingColHtml = `<div>₹${itemMakingTotal.toLocaleString('en-IN')}</div><div style="font-size: 9.5px; color: #0F766E; font-weight: 600;">(@₹${perG}/g)</div>`;
    }

    itemsRows += `
      <tr style="border-bottom: 1px solid #E5E7EB;">
        <td style="padding: 6px 8px; text-align: center; font-size: 11px;">${index + 1}</td>
        <td style="padding: 6px 8px; font-size: 11.5px; font-weight: 600;">
          ${item.name}
          <div style="font-size: 10px; color: #6B7280;">SKU: ${item.sku}</div>
        </td>
        <td style="padding: 6px 8px; text-align: center; font-size: 11px;">${item.purity || '-'}</td>
        <td style="padding: 6px 8px; text-align: right; font-size: 11px;">${(item.weight || 0).toFixed(3)}g</td>
        <td style="padding: 6px 8px; text-align: center; font-size: 11px;">${item.qty}</td>
        <td style="padding: 6px 8px; text-align: right; font-size: 11px;">${makingColHtml}</td>
        <td style="padding: 6px 8px; text-align: right; font-size: 11.5px; font-weight: 700;">₹${lineTotal.toLocaleString('en-IN')}</td>
      </tr>
    `;
  });

  content.innerHTML = `
    <div id="printArea" style="max-width: 750px; margin: 0 auto; background: #FFFFFF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #111827; line-height: 1.4;">
      
      <!-- Tax Invoice Header -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0F766E; padding-bottom: 12px; margin-bottom: 14px;">
        <div>
          <h1 style="font-size: 22px; font-weight: 900; color: #0F766E; margin: 0 0 4px; letter-spacing: -0.02em;">${sName}</h1>
          <div style="font-size: 11.5px; color: #4B5563;">${sAddress}</div>
          <div style="font-size: 11.5px; color: #4B5563; font-weight: 600; margin-top: 2px;">
            ${sGstin ? `GSTIN: <span style="font-family: monospace;">${sGstin}</span>` : ''} 
            ${sPhone ? `· Phone: ${sPhone}` : ''}
          </div>
        </div>
        <div style="text-align: right;">
          <div style="background: #0F766E; color: #FFFFFF; font-size: 10.5px; font-weight: 800; padding: 3px 10px; border-radius: 4px; display: inline-block; letter-spacing: 0.05em; margin-bottom: 4px;">
            TAX INVOICE / CASH MEMO
          </div>
          <div style="font-size: 12px; font-weight: 700;"><strong>Invoice:</strong> ${invoiceNo}</div>
          <div style="font-size: 11px; color: #4B5563;"><strong>Order:</strong> ${orderNo}</div>
          <div style="font-size: 11px; color: #4B5563;"><strong>Date:</strong> ${new Date().toLocaleDateString('en-IN')}</div>
        </div>
      </div>

      <!-- Bill To & Payment Info Box -->
      <div style="display: flex; justify-content: space-between; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 11.5px;">
        <div>
          <span style="font-size: 10px; text-transform: uppercase; font-weight: 800; color: #64748B;">Billed To:</span>
          <div style="font-weight: 700; font-size: 13px; color: #0F172A; margin-top: 1px;">${customerName}</div>
          <div style="color: #64748B; font-size: 11px;">Payment Mode: <strong>${selectedPaymentMethod}</strong> (Status: Paid)</div>
        </div>
        <div style="text-align: right;">
          <span style="font-size: 10px; text-transform: uppercase; font-weight: 800; color: #64748B;">Place of Supply:</span>
          <div style="font-weight: 600; color: #0F172A; margin-top: 1px;">Maharashtra (State Code: 27)</div>
          <div style="color: #059669; font-weight: 700; font-size: 11px;">✓ 100% Hallmarked Quality</div>
        </div>
      </div>

      <!-- Itemized Purchased Products Table -->
      <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px; border: 1px solid #E5E7EB; border-radius: 6px; overflow: hidden;">
        <thead>
          <tr style="background: #F1F5F9; border-bottom: 1.5px solid #CBD5E1; font-size: 10.5px; text-transform: uppercase; color: #475569; letter-spacing: 0.03em;">
            <th style="padding: 7px 8px; text-align: center; width: 35px;">#</th>
            <th style="padding: 7px 8px; text-align: left;">Ornament Description</th>
            <th style="padding: 7px 8px; text-align: center; width: 70px;">Purity</th>
            <th style="padding: 7px 8px; text-align: right; width: 75px;">Weight</th>
            <th style="padding: 7px 8px; text-align: center; width: 45px;">Qty</th>
            <th style="padding: 7px 8px; text-align: right; width: 80px;">Making</th>
            <th style="padding: 7px 8px; text-align: right; width: 95px;">Amount</th>
          </tr>
        </thead>
        <tbody>
          ${itemsRows}
        </tbody>
      </table>

      <!-- Summary & Tax Breakdown Section -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; gap: 20px;">
        <!-- Terms and Notes -->
        <div style="flex: 1; font-size: 10.5px; color: #64748B; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px 12px;">
          <div style="font-weight: 700; color: #334155; margin-bottom: 4px;">Terms & Conditions:</div>
          <ol style="margin: 0; padding-left: 14px; line-height: 1.45;">
            <li>Jewellery exchange accepted within 7 days with original tax invoice.</li>
            <li>Bullion rates are fluctuating and based on prevailing market rates.</li>
            <li>Subject to Mumbai jurisdiction only.</li>
          </ol>
        </div>

        <!-- Totals Table -->
        <div style="width: 270px; font-size: 11.5px;">
          <div style="display: flex; justify-content: space-between; padding: 3px 0;">
            <span style="color: #64748B;">Taxable Value:</span>
            <span style="font-weight: 600;">₹${subtotal.toLocaleString('en-IN')}</span>
          </div>
          <div style="display: flex; justify-content: space-between; padding: 3px 0;">
            <span style="color: #64748B;">CGST (1.5%):</span>
            <span style="font-weight: 600;">₹${cgst.toLocaleString('en-IN')}</span>
          </div>
          <div style="display: flex; justify-content: space-between; padding: 3px 0;">
            <span style="color: #64748B;">SGST (1.5%):</span>
            <span style="font-weight: 600;">₹${sgst.toLocaleString('en-IN')}</span>
          </div>
          ${discount > 0 ? `
          <div style="display: flex; justify-content: space-between; padding: 3px 0; color: #059669; font-weight: 700;">
            <span>Special Discount:</span>
            <span>-₹${discount.toLocaleString('en-IN')}</span>
          </div>` : ''}
          ${exchangeCredit > 0 ? `
          <div style="display: flex; justify-content: space-between; padding: 4px 6px; color: #B45309; font-weight: 700; background: #FEF3C7; border: 1px solid #FDE68A; border-radius: 4px; margin: 3px 0;">
            <span>− Old Gold Credit (${exchangeNo || 'OGE'}):</span>
            <span>-₹${exchangeCredit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
          </div>` : ''}
          <div style="display: flex; justify-content: space-between; border-top: 2px solid #0F766E; padding-top: 6px; margin-top: 6px; font-size: 15px; font-weight: 900; color: #0F766E;">
            <span>Net Payable:</span>
            <span>₹${total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
          </div>
        </div>
      </div>

      <!-- Authorized Signature Footer -->
      <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 14px; border-top: 1px dashed #CBD5E1; font-size: 11px;">
        <div>
          <div style="color: #64748B;">Customer Signature</div>
        </div>
        <div style="text-align: right;">
          <div style="font-weight: 700; color: #0F766E; margin-bottom: 24px;">For ${sName}</div>
          <div style="border-top: 1px solid #94A3B8; padding-top: 3px; color: #475569; font-weight: 600;">Authorized Signatory</div>
        </div>
      </div>

    </div>
  `;

  modal.classList.add('show');
  if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeSaleSuccessModal() {
  const modal = document.getElementById('saleSuccessModal');
  if (modal) modal.classList.remove('show');
}

function startNextSale(mode = 'walk_in') {
  closeSaleSuccessModal();
  const select = document.getElementById('posCustomerSelect');
  
  if (mode === 'new_customer') {
    if (select) select.value = '';
    setTimeout(() => {
      openNewCustomerModal();
    }, 150);
  } else if (mode === 'select_customer') {
    if (select) {
      select.focus();
      select.style.borderColor = 'var(--color-primary)';
      select.style.boxShadow = '0 0 0 3px rgba(15, 118, 110, 0.25)';
      setTimeout(() => {
        select.style.borderColor = '';
        select.style.boxShadow = '';
      }, 2500);
    }
  } else {
    if (select) select.value = '';
    const search = document.getElementById('posSearch');
    if (search) search.focus();
  }
}

function printReceipt() {
  const printEl = document.getElementById('printArea') || document.getElementById('printReceiptContent');
  if (!printEl) {
    window.print();
    return;
  }

  let printFrame = document.getElementById('receiptPrintIframe');
  if (!printFrame) {
    printFrame = document.createElement('iframe');
    printFrame.id = 'receiptPrintIframe';
    printFrame.style.position = 'fixed';
    printFrame.style.right = '0';
    printFrame.style.bottom = '0';
    printFrame.style.width = '0';
    printFrame.style.height = '0';
    printFrame.style.border = '0';
    document.body.appendChild(printFrame);
  }

  const doc = printFrame.contentWindow.document;
  doc.open();
  doc.write(`
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Tax Invoice - ${window.STORE_INFO?.name || 'Kulthe Suvarnkar'}</title>
      <style>
        @page {
          size: A4 portrait;
          margin: 10mm 15mm;
        }
        * {
          box-sizing: border-box;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }
        html, body {
          margin: 0;
          padding: 0;
          background: #ffffff;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
          color: #111827;
          font-size: 11.5px;
          line-height: 1.4;
        }
        table {
          width: 100%;
          border-collapse: collapse;
        }
        #printArea {
          width: 100%;
          max-width: 100%;
          margin: 0 auto;
          page-break-after: avoid;
          page-break-inside: avoid;
        }
      </style>
    </head>
    <body>
      ${printEl.outerHTML}
    </body>
    </html>
  `);
  doc.close();

  setTimeout(() => {
    printFrame.contentWindow.focus();
    printFrame.contentWindow.print();
  }, 250);
}

// New Customer Modal
function openNewCustomerModal() {
  const modal = document.getElementById('newCustomerModal');
  if (modal) modal.classList.add('show');
  setTimeout(() => {
    const input = document.getElementById('newCustName');
    if (input) input.focus();
  }, 100);
}

function closeNewCustomerModal() {
  const modal = document.getElementById('newCustomerModal');
  if (modal) modal.classList.remove('show');
}

async function handleCreateCustomer(e) {
  e.preventDefault();
  const name = document.getElementById('newCustName').value.trim();
  const mobile = document.getElementById('newCustMobile').value.trim();
  const email = document.getElementById('newCustEmail').value.trim();
  const city = document.getElementById('newCustCity').value.trim();

  try {
    const res = await fetch('api/customers.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, mobile, email, city })
    });
    const data = await res.json();
    if (data.success) {
      // Add to select dropdown & select it
      const select = document.getElementById('posCustomerSelect');
      const opt = document.createElement('option');
      opt.value = data.customer_id;
      opt.text = `${name} (${mobile}) · Bronze`;
      opt.setAttribute('data-name', name);
      opt.setAttribute('data-mobile', mobile);
      select.appendChild(opt);
      select.value = data.customer_id;
      closeNewCustomerModal();
      showToast(`Customer ${name} added!`);
    } else {
      alert(data.error || 'Failed to add customer.');
    }
  } catch (err) {
    console.error(err);
    alert('Server error creating customer.');
  }
}
