/**
 * notifications.js — EnrollEase notification bell
 * Exact copy of EntryEase's notifications.js — adapted for EnrollEase.
 */
(function () {
    'use strict';

    var MAX_ITEMS   = 50;
    var memoryStore = [];

    function loadStore()       { return memoryStore.slice(); }
    function saveStore(items)  { memoryStore = items.slice(0, MAX_ITEMS); }

    function formatEventLabel(name) {
        return (name || 'Update').replace(/([A-Z])/g, ' $1').trim();
    }

    function formatTime(isoOrDate) {
        var d = isoOrDate instanceof Date ? isoOrDate : new Date(isoOrDate || Date.now());
        return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    }

    var state = { items: loadStore(), open: false };
    var els   = {};

    function unreadCount() {
        return state.items.filter(function (n) { return !n.read; }).length;
    }

    function render() {
        if (!els.list) return;

        var unread = unreadCount();
        if (els.badge) {
            if (unread > 0) {
                els.badge.textContent = unread > 99 ? '99+' : String(unread);
                els.badge.hidden = false;
            } else {
                els.badge.hidden = true;
            }
        }

        if (els.empty) {
            els.empty.hidden = state.items.length > 0;
        }

        els.list.innerHTML = '';
        state.items.forEach(function (item) {
            var li    = document.createElement('li');
            li.className   = 'app-notif-item' + (item.read ? ' is-read' : ' is-unread');
            li.dataset.id  = item.id;

            var title = document.createElement('span');
            title.className   = 'app-notif-item-title';
            title.textContent = item.title;

            var meta = document.createElement('span');
            meta.className   = 'app-notif-item-meta';
            meta.textContent = formatTime(item.at);

            var body = document.createElement('p');
            body.className   = 'app-notif-item-body';
            body.textContent = item.message;

            li.appendChild(title);
            li.appendChild(meta);
            li.appendChild(body);

            li.addEventListener('click', function () { markRead(item.id); });
            els.list.appendChild(li);
        });
    }

    function markRead(id) {
        state.items = state.items.map(function (n) {
            if (n.id === id) n.read = true;
            return n;
        });
        saveStore(state.items);
        render();
    }

    function markAllRead() {
        state.items = state.items.map(function (n) { n.read = true; return n; });
        saveStore(state.items);
        render();
    }

    function add(opts) {
        var item = {
            id:      opts.id      || 'n-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
            title:   opts.title   || 'Notification',
            message: opts.message || '',
            type:    opts.type    || 'info',
            at:      opts.at      || new Date().toISOString(),
            read:    false,
        };
        state.items.unshift(item);
        saveStore(state.items);
        render();
        document.dispatchEvent(new CustomEvent('enrollease:notification', { detail: item }));
        return item;
    }

    function toggleDropdown(force) {
        if (!els.dropdown || !els.btn) return;
        state.open = typeof force === 'boolean' ? force : !state.open;
        els.dropdown.hidden = !state.open;
        els.btn.setAttribute('aria-expanded', state.open ? 'true' : 'false');
    }

    function bindUi() {
        els.btn      = document.getElementById('notifBellBtn');
        els.dropdown = document.getElementById('notifDropdown');
        els.list     = document.getElementById('notifList');
        els.badge    = document.getElementById('notifBadge');
        els.empty    = document.getElementById('notifEmpty');
        els.markAll  = document.getElementById('notifMarkAllRead');

        if (!els.btn) return;

        els.btn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleDropdown();
        });

        if (els.markAll) {
            els.markAll.addEventListener('click', function (e) {
                e.stopPropagation();
                markAllRead();
            });
        }

        document.addEventListener('click', function (e) {
            if (!state.open) return;
            if (els.dropdown && !els.dropdown.contains(e.target) &&
                e.target !== els.btn && !els.btn.contains(e.target)) {
                toggleDropdown(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') toggleDropdown(false);
        });
    }

    function initFromPage() {
        var initial = window.ENROLLEASE_INITIAL_NOTIFICATIONS;
        if (Array.isArray(initial)) {
            initial.forEach(function (n) {
                add({ title: n.title, message: n.message, type: n.type, read: false });
            });
        }
    }

    function onDeorisRealtime(e) {
        var detail = e.detail || {};
        var name   = detail.event || 'Update';
        var data   = detail.data  || {};
        var msg    = data.student_name || data.student_email ||
                     (data.enrollment_id ? 'Enrollment #' + data.enrollment_id : '') ||
                     'Something changed in the enrollment system.';
        add({ title: formatEventLabel(name), message: String(msg).trim() || 'Live update received.', type: 'info' });
    }

    window.EnrollEaseNotifications = {
        add:        add,
        markRead:   markRead,
        markAllRead: markAllRead,
        getAll:     function () { return state.items.slice(); },
    };

    document.addEventListener('DOMContentLoaded', function () {
        bindUi();
        initFromPage();
        render();
        document.addEventListener('deoris:realtime', onDeorisRealtime);
    });
})();
