<div class="app-notifications" id="appNotifications">
    <button type="button"
            class="app-notif-bell"
            id="notifBellBtn"
            aria-label="Notifications"
            aria-expanded="false"
            aria-controls="notifDropdown">
        <i class="fa-solid fa-bell" aria-hidden="true"></i>
        <span class="app-notif-badge" id="notifBadge" hidden>0</span>
    </button>
    <div class="app-notif-dropdown" id="notifDropdown" role="region" aria-label="Notifications" hidden>
        <div class="app-notif-dropdown-header">
            <span>Notifications</span>
            <button type="button" class="app-notif-mark-read" id="notifMarkAllRead">Mark all read</button>
        </div>
        <ul class="app-notif-list" id="notifList"></ul>
        <p class="app-notif-empty" id="notifEmpty">No notifications yet.</p>
    </div>
</div>
