document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('#main-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!open));
            nav.classList.toggle('is-open', !open);
        });
    }

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Biztosan végrehajtja a műveletet?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const fieldId = button.getAttribute('data-password-toggle');
        const field = fieldId ? document.getElementById(fieldId) : null;
        if (!(field instanceof HTMLInputElement)) {
            return;
        }
        button.addEventListener('click', () => {
            const willShow = field.type === 'password';
            field.type = willShow ? 'text' : 'password';
            const label = willShow ? 'Jelszó elrejtése' : 'Jelszó megjelenítése';
            button.setAttribute('aria-pressed', String(willShow));
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.classList.toggle('is-visible', willShow);
            field.focus({ preventScroll: true });
        });
    });
});
