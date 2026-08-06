/**
 * Usage: call showToast('Added to cart!', 'success') anywhere after including this file.
 * Types: 'success', 'danger', 'warning', 'info'
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed; top:80px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px;';
        document.body.appendChild(container);
    }

    const colors = {
        success: '#198754',
        danger: '#dc3545',
        warning: '#ffc107',
        info: '#0dcaf0'
    };

    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${colors[type] || colors.success};
        color: ${type === 'warning' ? '#212529' : '#fff'};
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 250px;
        max-width: 350px;
        font-size: 0.9rem;
        opacity: 0;
        transform: translateX(30px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    `;
    toast.textContent = message;
    container.appendChild(toast);

    // Trigger the slide-in animation
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    });

    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(30px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Automatically show a toast if the page was loaded with a ?toast=...&type=... URL param
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const toastMsg = params.get('toast');
    const toastType = params.get('toast_type') || 'success';

    if (toastMsg) {
        showToast(decodeURIComponent(toastMsg), toastType);

        // Clean the URL so refreshing doesn't re-show the toast
        params.delete('toast');
        params.delete('toast_type');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', newUrl);
    }
});