import Alpine from 'alpinejs';

const CART_KEY = 'ck_cart_v1';

function loadCart() {
    try {
        const raw = localStorage.getItem(CART_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
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

        find(id) {
            return this.items.find((i) => i.id === id);
        },

        add(item, qty = 1, note = '') {
            qty = Math.max(1, Math.min(20, parseInt(qty, 10) || 1));
            const existing = this.find(item.id);
            if (existing) {
                existing.qty = Math.min(20, existing.qty + qty);
                if (note) existing.note = note;
            } else {
                this.items.push({
                    id: item.id,
                    name: item.name,
                    price: item.price, // minor units
                    image: item.image || null,
                    qty,
                    note: note || '',
                });
            }
            this.persist();
            Alpine.store('toasts').push(`${item.name} added to cart`);
        },

        setQty(id, qty) {
            const item = this.find(id);
            if (!item) return;
            qty = Math.max(1, Math.min(20, parseInt(qty, 10) || 1));
            item.qty = qty;
            this.persist();
        },

        increment(id) {
            const item = this.find(id);
            if (item) this.setQty(id, item.qty + 1);
        },

        decrement(id) {
            const item = this.find(id);
            if (item) this.setQty(id, item.qty - 1);
        },

        setNote(id, note) {
            const item = this.find(id);
            if (!item) return;
            item.note = note;
            this.persist();
        },

        remove(id) {
            this.items = this.items.filter((i) => i.id !== id);
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
                this.items.map((i) => ({ id: i.id, qty: i.qty, note: i.note || '' }))
            );
        },
    });
});

// Format minor-unit integer prices for display, e.g. 1250 -> "12.50".
window.money = (minor) => (Number(minor) / 100).toFixed(2);

window.Alpine = Alpine;
Alpine.start();
