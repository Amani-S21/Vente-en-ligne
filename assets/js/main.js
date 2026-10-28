/**
 * Interactions dynamiques, Panier AJAX, Toasts et Validation Formulaire
 */

document.addEventListener('DOMContentLoaded', () => {
  initToastsContainer();
  initAddToCartAjax();
  initQuantityPickers();
  initPaymentToggle();
  initPasswordToggles();
});

/**
 * Crée le conteneur de toast s'il n'existe pas
 */
function initToastsContainer() {
  if (!document.querySelector('.toast-box')) {
    const box = document.createElement('div');
    box.className = 'toast-box';
    document.body.appendChild(box);
  }
}

/**
 * Affiche une notification toast fluide
 */
function showToast(message, type = 'success') {
  const box = document.querySelector('.toast-box');
  if (!box) return;

  const toast = document.createElement('div');
  toast.className = `toast-msg toast-${type}`;
  
  const icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
  toast.innerHTML = `<i class="bi ${icon}"></i> <span>${message}</span>`;

  box.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

/**
 * Intercepte l'ajout au panier via AJAX pour une expérience fluide
 */
function initAddToCartAjax() {
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.ajax-add-to-cart');
    if (!form) return;

    e.preventDefault();
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Ajout en cours...';
    }

    try {
      const response = await fetch('cart-action.php', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const data = await response.json();

      if (data.success) {
        showToast(data.message || 'Article ajouté au panier avec succès !', 'success');
        
        // Mettre à jour le badge du panier
        const badges = document.querySelectorAll('.cart-badge');
        badges.forEach(b => {
          b.textContent = data.cart_count;
          b.style.display = data.cart_count > 0 ? 'flex' : 'none';
        });
      } else {
        showToast(data.message || 'Impossible d\'ajouter l\'article.', 'danger');
      }
    } catch (err) {
      console.error(err);
      // Fallback submit si l'API JSON échoue
      form.submit();
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    }
  });
}

/**
 * Sélecteur de quantité (+ et -)
 */
function initQuantityPickers() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.qty-btn');
    if (!btn) return;

    const action = btn.dataset.action;
    const picker = btn.closest('.qty-picker');
    const input = picker.querySelector('.qty-input');
    if (!input) return;

    let currentVal = parseInt(input.value) || 1;
    const maxVal = parseInt(input.getAttribute('max')) || 99;
    const minVal = parseInt(input.getAttribute('min')) || 1;

    if (action === 'increase' && currentVal < maxVal) {
      input.value = currentVal + 1;
    } else if (action === 'decrease' && currentVal > minVal) {
      input.value = currentVal - 1;
    }

    // Déclencher l'événement change pour le recalcul automatique
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
}

/**
 * Basculement des méthodes de paiement (Carte Bancaire vs Mobile Money)
 */
function initPaymentToggle() {
  const methodRadios = document.querySelectorAll('input[name="payment_method"]');
  const cardSection = document.getElementById('cardPaymentFields');
  const mobileSection = document.getElementById('mobilePaymentFields');

  if (!methodRadios.length) return;

  function updateFields() {
    const selected = document.querySelector('input[name="payment_method"]:checked')?.value;
    if (cardSection) cardSection.style.display = (selected === 'card') ? 'block' : 'none';
    if (mobileSection) mobileSection.style.display = (selected === 'mobile_money') ? 'block' : 'none';
  }

  methodRadios.forEach(r => r.addEventListener('change', updateFields));
  updateFields();
}

/**
 * Bascule d'affichage du mot de passe (Afficher / Masquer)
 */
function initPasswordToggles() {
  const toggles = document.querySelectorAll('.toggle-password-btn');
  toggles.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const input = document.getElementById(targetId);
      if (!input) return;

      const isPassword = input.getAttribute('type') === 'password';
      input.setAttribute('type', isPassword ? 'text' : 'password');
      btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });
  });
}
