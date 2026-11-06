import Alpine from 'alpinejs';
import QRCode from 'qrcode';

const CART_KEY = 'ck_cart_v1';

// A cart line is identified by item id + chosen size + egg, so the same dish
// in different sizes stays on separate lines.
function lineKey(id, size, egg) {
    return [id, size || 'n', egg || 'none'].join('|');
}

function normalizeLine(i) {
    const size = i.size ?? null;
    const egg = i.egg || 'none';
    return { ...i, size, egg, key: i.key || lineKey(i.id, size, egg) };
}

function loadCart() {
    try {
        const raw = localStorage.getItem(CART_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed.map(normalizeLine) : [];
    } catch (e) {
        return [];
    }
}

function saveCart(items) {
    try {
        localStorage.setItem(CART_KEY, JSON.stringify(items));
    } catch (e) {
        /* storage unavailable (private mode) — cart just won't persist */
    }
}

document.addEventListener('alpine:init', () => {
    // --- Toast notifications ---
    Alpine.store('toasts', {
        items: [],
        push(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => this.dismiss(id), 3200);
        },
        dismiss(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    });

    // --- Cart (persisted to localStorage) ---
    Alpine.store('cart', {
        items: loadCart(),

        init() {
            // Keep the badge in sync across tabs.
            window.addEventListener('storage', (e) => {
                if (e.key === CART_KEY) {
                    this.items = loadCart();
                }
            });
        },

        persist() {
            saveCart(this.items);
        },

        find(key) {
            return this.items.find((i) => i.key === key);
        },

        add(item, qty = 1, note = '') {
            qty = Math.max(1, Math.min(20, parseInt(qty, 10) || 1));
            const size = item.size ?? null;
            const egg = item.egg || 'none';
            const key = lineKey(item.id, size, egg);
            const existing = this.find(key);
            if (existing) {
                existing.qty = Math.min(20, existing.qty + qty);
                if (note) existing.note = note;
            } else {
                this.items.push({
                    key,
                    id: item.id,
                    name: item.name,
                    price: item.price, // minor units — unit price incl. options
                    image: item.image || null,
                    isDrink: item.isDrink || false,
                    size,
                    egg,
                    qty,
                    note: note || '',
                });
            }
            this.persist();
            Alpine.store('toasts').push(`${item.name} added to cart`);
        },

        setQty(key, qty) {
            const item = this.find(key);
            if (!item) return;
            qty = Math.max(1, Math.min(20, parseInt(qty, 10) || 1));
            item.qty = qty;
            this.persist();
        },

        increment(key) {
            const item = this.find(key);
            if (item) this.setQty(key, item.qty + 1);
        },

        decrement(key) {
            const item = this.find(key);
            if (item) this.setQty(key, item.qty - 1);
        },

        setNote(key, note) {
            const item = this.find(key);
            if (!item) return;
            item.note = note;
            this.persist();
        },

        remove(key) {
            this.items = this.items.filter((i) => i.key !== key);
            this.persist();
            Alpine.store('toasts').push('Item removed', 'info');
        },

        clear() {
            this.items = [];
            this.persist();
        },

        get count() {
            return this.items.reduce((sum, i) => sum + i.qty, 0);
        },

        get subtotal() {
            return this.items.reduce((sum, i) => sum + i.price * i.qty, 0);
        },

        // Compact payload for the checkout POST.
        toPayload() {
            return JSON.stringify(
                this.items.map((i) => ({
                    id: i.id,
                    qty: i.qty,
                    note: i.note || '',
                    size: i.size || null,
                    egg: i.egg || 'none',
                }))
            );
        },
    });
});

// Format minor-unit integer prices for display, e.g. 1250 -> "12.50".
window.money = (minor) => (Number(minor) / 100).toFixed(2);

/*
 * PromptPay dynamic QR.
 * Builds the EMVCo/Thai-QR payload for a PromptPay transfer with the exact
 * amount embedded, so the customer never types the amount into their bank app.
 * Spec: EMV QRCPS + Bank of Thailand PromptPay. CRC16-CCITT (0x1021, init 0xFFFF).
 */
const ppField = (id, value) => id + String(value.length).padStart(2, '0') + value;

const ppCrc16 = (str) => {
    let crc = 0xffff;
    for (let i = 0; i < str.length; i++) {
        crc ^= str.charCodeAt(i) << 8;
        for (let j = 0; j < 8; j++) {
            crc = crc & 0x8000 ? (crc << 1) ^ 0x1021 : crc << 1;
            crc &= 0xffff;
        }
    }
    return crc.toString(16).toUpperCase().padStart(4, '0');
};

// Normalise a PromptPay target to the EMVCo sub-field (id + value).
const ppTarget = (raw) => {
    const n = String(raw).replace(/[^0-9]/g, '');
    if (n.length >= 15) return ppField('03', n); // e-Wallet ID
    if (n.length >= 13) return ppField('02', n); // National ID / Tax ID
    // Mobile number: strip leading 0, prefix 66, left-pad to 13.
    const phone = ('0000000000000' + n.replace(/^0/, '66')).slice(-13);
    return ppField('01', phone);
};

// id: mobile / national-id / e-wallet. amountBaht: number in THB (major unit).
window.promptpayPayload = (id, amountBaht) => {
    const merchant = ppField('00', 'A000000677010111') + ppTarget(id); // AID + target
    const amount = Number(amountBaht);
    const hasAmount = Number.isFinite(amount) && amount > 0;
    let payload =
        ppField('00', '01') +                         // payload format
        ppField('01', hasAmount ? '12' : '11') +      // dynamic (12) when amount present
        ppField('29', merchant) +                     // merchant account info (PromptPay)
        ppField('58', 'TH') +                         // country
        ppField('53', '764') +                        // currency THB
        (hasAmount ? ppField('54', amount.toFixed(2)) : '');
    payload += '6304';                                // CRC tag + length, value follows
    return payload + ppCrc16(payload);
};

// Render a PromptPay QR to a data URL (PNG). Resolves to '' on failure.
window.promptpayQrDataUrl = (id, amountBaht) => {
    try {
        const payload = window.promptpayPayload(id, amountBaht);
        return QRCode.toDataURL(payload, { margin: 1, width: 512, errorCorrectionLevel: 'M' });
    } catch (e) {
        return Promise.resolve('');
    }
};

window.Alpine = Alpine;
Alpine.start();
