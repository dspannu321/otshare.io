/**
 * Operations dashboard: tab switching, submit buttons (disable until valid), loading spinners.
 */
function initOpsClock() {
    const el = document.getElementById('admin-ops-clock');
    if (!el) {
        return;
    }

    const tz = el.dataset.tz || 'UTC';

    const tick = () => {
        const now = new Date();
        try {
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: tz,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZoneName: 'short',
            });
            const parts = formatter.formatToParts(now);
            const p = Object.fromEntries(parts.filter((x) => x.type !== 'literal').map((x) => [x.type, x.value]));
            const zone = p.timeZoneName ? ` ${p.timeZoneName}` : '';
            el.textContent = `${p.year}-${p.month}-${p.day} ${p.hour}:${p.minute}:${p.second}${zone}`;
        } catch {
            el.textContent = `${now.toISOString().replace('T', ' ').slice(0, 19)} Z`;
        }
        el.dateTime = now.toISOString();
    };

    tick();
    setInterval(tick, 1000);
}

function initAdminTabs() {
    document.querySelectorAll('[data-admin-tabs]').forEach((root) => {
        const tabs = root.querySelectorAll('[data-admin-tab]');
        const panels = root.querySelectorAll('[data-admin-panel]');

        const activate = (id) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.adminTab === id;
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
                tab.classList.toggle('admin-tab-active', active);
            });
            panels.forEach((panel) => {
                const show = panel.dataset.adminPanel === id;
                panel.classList.toggle('hidden', !show);
                panel.hidden = !show;
            });
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activate(tab.dataset.adminTab));
            tab.addEventListener('keydown', (e) => {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') {
                    return;
                }
                e.preventDefault();
                const i = [...tabs].indexOf(tab);
                const next = e.key === 'ArrowRight' ? tabs[i + 1] || tabs[0] : tabs[i - 1] || tabs[tabs.length - 1];
                next.focus();
                activate(next.dataset.adminTab);
            });
        });

        const initial = [...tabs].find((t) => t.getAttribute('aria-selected') === 'true')?.dataset.adminTab || tabs[0]?.dataset.adminTab;
        if (initial) {
            activate(initial);
        }
    });
}

function isTotpValid(value) {
    return /^\d{6}$/.test((value || '').trim());
}

function updateFormSubmitState(form) {
    const mode = form.dataset.adminValidate || 'none';
    const submit = form.querySelector('[type="submit"][data-admin-submit]');
    if (!submit) {
        return;
    }

    let ok = true;
    if (mode === 'none') {
        ok = true;
    } else if (mode === 'password') {
        const input = form.querySelector('input[name="password"]');
        ok = Boolean(input && input.value.trim().length > 0);
    } else if (mode === 'totp') {
        const input = form.querySelector('input[name="code"]');
        ok = Boolean(input && isTotpValid(input.value));
    } else if (mode === 'purge') {
        const phrase = form.dataset.confirmPhrase || '';
        const input = form.querySelector('input[name="confirm"]');
        ok = Boolean(input && input.value.trim() === phrase);
    }

    submit.disabled = !ok;
}

function setSubmitLoading(form, loading) {
    const submit = form.querySelector('[type="submit"][data-admin-submit]');
    if (!submit) {
        return;
    }
    const label = submit.querySelector('.admin-btn-label');
    const spin = submit.querySelector('.admin-btn-spinner');
    submit.disabled = loading;
    if (loading) {
        submit.setAttribute('data-loading', '1');
        if (label) {
            label.classList.add('invisible');
        }
        if (spin) {
            spin.classList.remove('hidden');
            spin.classList.add('flex');
        }
    } else {
        submit.removeAttribute('data-loading');
        if (label) {
            label.classList.remove('invisible');
        }
        if (spin) {
            spin.classList.add('hidden');
            spin.classList.remove('flex');
        }
        updateFormSubmitState(form);
    }
}

function initAdminForms() {
    document.querySelectorAll('form[data-admin-validate]').forEach((form) => {
        const mode = form.dataset.adminValidate || 'none';
        const submit = form.querySelector('[type="submit"][data-admin-submit]');
        if (submit) {
            submit.disabled = mode !== 'none';
        }

        const onChange = () => {
            const btn = form.querySelector('[type="submit"][data-admin-submit]');
            if (btn?.getAttribute('data-loading') === '1') {
                return;
            }
            updateFormSubmitState(form);
        };

        form.querySelectorAll('input, textarea').forEach((el) => {
            el.addEventListener('input', onChange);
            el.addEventListener('change', onChange);
        });

        form.addEventListener('submit', () => {
            setSubmitLoading(form, true);
        });

        onChange();
        requestAnimationFrame(() => {
            onChange();
            setTimeout(onChange, 150);
            setTimeout(onChange, 500);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initOpsClock();
    initAdminTabs();
    initAdminForms();
});
