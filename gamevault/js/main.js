// GameVault — Main JS

// Auto-dismiss flash messages after 5s
document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('.flash');
    if (flash) {
        setTimeout(() => { flash.style.opacity = '0'; setTimeout(() => flash.remove(), 300); }, 5000);
        flash.style.transition = 'opacity 0.3s ease';
    }

    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });
});
