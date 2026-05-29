/**
 * enrollment.js — EnrollEase UI helpers
 * Modals, toasts, confirm dialogs, search.
 */

/* ── Modals ──────────────────────────────────────────────── */
function openModal(id) {
    var el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}

function closeModal(id) {
    var el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(function (el) {
            el.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
});

/* ── Confirm forms ───────────────────────────────────────── */
document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !confirm(msg)) e.preventDefault();
});

/* ── Search ──────────────────────────────────────────────── */
(function () {
    'use strict';

    var toggleBtn  = null;
    var panel      = null;
    var input      = null;
    var clearBtn   = null;
    var resultsList = null;
    var emptyMsg   = null;
    var searchOpen = false;
    var debounceTimer = null;

    // Local search data — built from the current page's table rows
    function buildIndex() {
        var rows = [];
        document.querySelectorAll('tbody tr').forEach(function (tr) {
            var cells = tr.querySelectorAll('td');
            if (!cells.length) return;

            var name   = (cells[1] && cells[1].querySelector('strong')) ? cells[1].querySelector('strong').textContent.trim() : '';
            var email  = (cells[1] && cells[1].querySelector('.td-sub'))  ? cells[1].querySelector('.td-sub').textContent.trim()  : '';
            var grade  = cells[2] ? cells[2].textContent.trim() : '';
            var status = cells[4] ? cells[4].textContent.trim() : (cells[5] ? cells[5].textContent.trim() : '');
            var ref    = cells[0] ? cells[0].textContent.trim() : '';

            if (name || email) {
                rows.push({ name: name, email: email, grade: grade, status: status, ref: ref, row: tr });
            }
        });
        return rows;
    }

    function runSearch(query) {
        if (!resultsList || !emptyMsg) return;

        var q = query.trim().toLowerCase();

        if (!q) {
            resultsList.hidden = true;
            emptyMsg.hidden    = true;
            resultsList.innerHTML = '';
            return;
        }

        var index   = buildIndex();
        var matches = index.filter(function (item) {
            return item.name.toLowerCase().includes(q)
                || item.email.toLowerCase().includes(q)
                || item.ref.toLowerCase().includes(q)
                || item.grade.toLowerCase().includes(q);
        });

        resultsList.innerHTML = '';

        if (!matches.length) {
            resultsList.hidden = true;
            emptyMsg.hidden    = false;
            return;
        }

        emptyMsg.hidden = true;

        matches.slice(0, 8).forEach(function (item) {
            var li = document.createElement('li');

            var a = document.createElement('a');
            a.className = 'app-search-result';
            a.href = '#';

            var title = document.createElement('span');
            title.className   = 'app-search-result-title';
            title.textContent = item.name || item.email;

            var sub = document.createElement('span');
            sub.className   = 'app-search-result-sub';
            sub.textContent = [item.email, item.grade].filter(Boolean).join(' · ');

            if (item.status) {
                var badge = document.createElement('span');
                badge.className   = 'badge badge-' + item.status.toLowerCase() + ' app-search-result-badge';
                badge.textContent = item.status;
                a.appendChild(badge);
            }

            a.appendChild(title);
            a.appendChild(sub);

            a.addEventListener('click', function (e) {
                e.preventDefault();
                // Highlight the matching row
                item.row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                item.row.style.outline = '2px solid var(--primary)';
                item.row.style.outlineOffset = '-2px';
                setTimeout(function () {
                    item.row.style.outline = '';
                    item.row.style.outlineOffset = '';
                }, 2000);
                closeSearch();
            });

            li.appendChild(a);
            resultsList.appendChild(li);
        });

        resultsList.hidden = false;
    }

    function openSearch() {
        if (!panel) return;
        searchOpen      = true;
        panel.hidden    = false;
        toggleBtn && toggleBtn.setAttribute('aria-expanded', 'true');
        setTimeout(function () { input && input.focus(); }, 50);
    }

    function closeSearch() {
        if (!panel) return;
        searchOpen      = false;
        panel.hidden    = true;
        toggleBtn && toggleBtn.setAttribute('aria-expanded', 'false');
        if (input)       input.value = '';
        if (clearBtn)    clearBtn.hidden = true;
        if (resultsList) { resultsList.hidden = true; resultsList.innerHTML = ''; }
        if (emptyMsg)    emptyMsg.hidden = true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleBtn   = document.getElementById('searchToggleBtn');
        panel       = document.getElementById('searchPanel');
        input       = document.getElementById('searchInput');
        clearBtn    = document.getElementById('searchClearBtn');
        resultsList = document.getElementById('searchResults');
        emptyMsg    = document.getElementById('searchEmpty');

        if (!toggleBtn || !panel) return;

        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            searchOpen ? closeSearch() : openSearch();
        });

        if (input) {
            input.addEventListener('input', function () {
                var val = input.value;
                clearBtn && (clearBtn.hidden = !val);
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () { runSearch(val); }, 200);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeSearch();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (input) input.value = '';
                clearBtn.hidden = true;
                if (resultsList) { resultsList.hidden = true; resultsList.innerHTML = ''; }
                if (emptyMsg)    emptyMsg.hidden = true;
                input && input.focus();
            });
        }

        document.addEventListener('click', function (e) {
            if (!searchOpen) return;
            var container = document.getElementById('appSearch');
            if (container && !container.contains(e.target)) closeSearch();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSearch();
        });
    });
})();
