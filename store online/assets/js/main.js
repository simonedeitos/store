/**
 * AirDirector Store - JavaScript Principale
 */

document.addEventListener('DOMContentLoaded', function() {

    // ============================================================
    // ADD TO CART (AJAX)
    // ============================================================
    document.querySelectorAll('.btn-add-cart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const type = this.dataset.type;
            const id = this.dataset.id;
            const qty = this.dataset.qty || 1;

            fetch(window.SITE_URL + '/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&type=${type}&id=${id}&qty=${qty}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Aggiorna badge carrello
                    updateCartBadge(data.cart_count);
                    showToast('Aggiunto al carrello!', 'success');
                } else {
                    showToast(data.error || 'Errore', 'danger');
                }
            })
            .catch(() => showToast('Errore di connessione', 'danger'));
        });
    });

    // ============================================================
    // UPDATE CART QUANTITY
    // ============================================================
    document.querySelectorAll('.cart-qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const key = this.dataset.key;
            const qty = this.value;
            
            fetch(window.SITE_URL + '/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update&key=${encodeURIComponent(key)}&qty=${qty}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
            });
        });
    });

    // ============================================================
    // REMOVE FROM CART
    // ============================================================
    document.querySelectorAll('.btn-remove-cart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const key = this.dataset.key;

            fetch(window.SITE_URL + '/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=remove&key=${encodeURIComponent(key)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
            });
        });
    });

    // ============================================================
    // COUPON
    // ============================================================
    const couponBtn = document.getElementById('applyCoupon');
    if (couponBtn) {
        couponBtn.addEventListener('click', function() {
            const code = document.getElementById('couponCode').value.trim();
            if (!code) return;

            fetch(window.SITE_URL + '/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=coupon&code=${encodeURIComponent(code)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showToast(data.error || 'Coupon non valido', 'danger');
                }
            });
        });
    }

    // ============================================================
    // COPY SERIAL KEY
    // ============================================================
    document.querySelectorAll('.btn-copy-serial').forEach(btn => {
        btn.addEventListener('click', function() {
            const serial = this.dataset.serial;
            navigator.clipboard.writeText(serial).then(() => {
                showToast('Seriale copiato!', 'success');
            });
        });
    });

    // ============================================================
    // IMAGE PREVIEW (ADMIN)
    // ============================================================
    const imageInput = document.getElementById('imageUpload');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const preview = document.getElementById('imagePreview');
            if (preview && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // ============================================================
    // HELPERS
    // ============================================================
    function updateCartBadge(count) {
        let badge = document.querySelector('.navbar .badge');
        if (count > 0) {
            if (!badge) {
                const cartLink = document.querySelector('a[href*="cart.php"]');
                if (cartLink) {
                    badge = document.createElement('span');
                    badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    cartLink.classList.add('position-relative');
                    cartLink.appendChild(badge);
                }
            }
            if (badge) badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    }

    function showToast(message, type = 'info') {
        // Crea toast container se non esiste
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show`;
        toast.style.cssText = 'min-width:250px;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
        toast.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Rendi SITE_URL disponibile globalmente
    if (!window.SITE_URL) {
        const baseTag = document.querySelector('link[href*="style.css"]');
        if (baseTag) {
            window.SITE_URL = baseTag.href.replace('/assets/css/style.css', '');
        }
    }
});