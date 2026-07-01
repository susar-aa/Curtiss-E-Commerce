<?php /* Premium Modernized Dashboard – Precise Alignment & Perfect Fit */ ?>
<style>
/* ── BACKGROUND ── */
#dashBg {
    position: fixed; inset: 0; z-index: -2;
    background: #0f0f1a;
    background-size: cover; background-position: center;
    transition: opacity 1.2s ease; opacity: 0;
}
#dashBg::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(150deg, rgba(8,8,22,.65) 0%, rgba(8,8,22,.35) 55%, rgba(8,8,22,.55) 100%);
}
#dashBg.ready { opacity: 1; }

/* ── FORCE MAIN CONTENT OVERRIDE TO ELIMINATE SCROLLING ── */
.main-content:has(#dashBg) {
    padding: 16px 24px !important;
    padding-bottom: 60px !important;
    overflow: hidden !important;
    height: calc(100vh - 82px) !important;
    box-sizing: border-box !important;
}

/* ── ROOT CONTAINER ── */
.dash-root {
    display: flex; flex-direction: column;
    height: 100%;
    overflow: hidden;
    gap: 12px;
    box-sizing: border-box;
}

/* ── TOPBAR ── */
.db-topbar {
    display: flex; align-items: center; justify-content: space-between;
    height: 42px;
    flex-shrink: 0;
}
.db-heading {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    margin: 0;
    letter-spacing: -0.5px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.db-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.db-search-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 20px;
    background: transparent;
    color: rgba(255,255,255,0.75);
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 500;
    transition: all 0.18s;
    cursor: pointer;
    border: 1px solid transparent;
}
.db-search-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.05);
}
.db-search-link i {
    font-size: 15px;
}

.db-kbd-hint {
    font-size: 9px; color: rgba(255,255,255,.45);
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    border-radius: 4px; padding: 1px 4px; font-family: monospace; flex-shrink: 0;
}
.db-icon-btn {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.82); font-size: 16px; text-decoration: none;
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
    transition: all .18s; position: relative; cursor: pointer;
}
.db-icon-btn:hover { background: rgba(79,70,229,.2); border-color: rgba(79,70,229,.35); color: #fff; }
.db-nbadge {
    background: #ef4444; color: #fff; border-radius: 9px;
    padding: 1px 4px; font-size: 8px; font-weight: 700;
    position: absolute; top: 1px; right: 1px;
    border: 2px solid rgba(10,10,20,.4);
}

/* ── 3-COLUMN GRID SYSTEM ── */
.db-grid {
    display: grid;
    grid-template-columns: 1.15fr 1.15fr 1fr;
    gap: 14px;
    flex: 1;
    min-height: 0;
}
@media (max-width: 1024px) {
    .db-grid { grid-template-columns: 1fr 1fr; }
    .db-col-right { grid-column: 1 / 3; }
}
@media (max-width: 768px) {
    .db-grid { grid-template-columns: 1fr; }
    .db-col-right { grid-column: auto; }
}

/* ── CARD BASE ── */
.d-card {
    background: rgba(255,255,255,0.07);
    backdrop-filter: blur(28px); -webkit-backdrop-filter: blur(28px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    overflow: hidden;
    transition: transform .22s, box-shadow .22s, border-color .22s;
    box-sizing: border-box;
}
.d-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 36px rgba(0,0,0,0.26);
    border-color: rgba(255,255,255,.18);
}

/* ── COLUMN SIZING ── */
.db-col {
    display: flex; flex-direction: column; gap: 14px; min-height: 0;
}

/* ── QUICK ACCESS (SQUARE BUTTONS) ── */
.d-quick {
    padding: 18px;
    height: 100%;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
}
.d-quick-header {
    font-size: 11px; font-weight: 700; color: rgba(255,255,255,.5);
    letter-spacing: 1.2px; text-transform: uppercase;
    margin-bottom: 12px; flex-shrink: 0;
}
.d-quick-grid-container {
    flex: 1;
    display: flex;
    align-items: stretch;
    justify-content: stretch;
    min-height: 0;
}
.d-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(3, 1fr);
    gap: 10px;
    width: 100%;
    height: 100%;
}
.d-qbtn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 14px;
    text-decoration: none;
    color: rgba(255,255,255,.9);
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    transition: all .2s ease;
    text-align: center;
    padding: 8px;
    box-sizing: border-box;
    width: 100%;
    height: 100%;
}
.d-qbtn:hover {
    background: rgba(79,70,229,.18); border-color: rgba(79,70,229,.35);
    color: #fff; transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(79,70,229,0.15);
}
.d-qbtn-icon {
    width: 32px; height: 32px; border-radius: 10px;
    background: rgba(255,255,255,.08);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; transition: transform .18s;
}
.d-qbtn:hover .d-qbtn-icon { transform: scale(1.08); }
.d-qbtn span {
    font-size: 11px;
    font-weight: 600;
    line-height: 1.25;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
    color: rgba(255,255,255,0.9);
}

/* ── TODO LIST ── */
.d-todo { padding: 18px; height: 100%; display: flex; flex-direction: column; box-sizing: border-box; }
.d-todo-header {
    font-size: 11px; font-weight: 700; color: rgba(255,255,255,.5);
    letter-spacing: 1.2px; text-transform: uppercase;
    margin-bottom: 12px; flex-shrink: 0;
}
.d-todo-list {
    flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px;
}
.d-todo-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; border-radius: 12px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
    transition: all 0.2s ease;
}
.d-todo-row:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.18);
}
.d-todo-icon-box {
    width: 36px; height: 36px; border-radius: 8px;
    background: rgba(255, 255, 255, 0.08);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-accent); font-size: 16px; flex-shrink: 0;
    cursor: pointer; transition: all 0.2s ease;
}
.d-todo-row.completed .d-todo-icon-box {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}
.d-todo-text {
    font-size: 13px; color: #ffffff !important; font-weight: 600; flex: 1;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}
.d-todo-row.completed .d-todo-text {
    text-decoration: line-through; opacity: 0.5;
}
.d-todo-delete {
    color: rgba(255,255,255,0.4); opacity: 0.8; cursor: pointer; transition: all 0.15s ease;
    background: transparent; border: none; font-size: 14px;
}
.d-todo-delete:hover {
    color: #ef4444; opacity: 1;
}
.d-todo-add {
    display: flex; gap: 8px; flex-shrink: 0;
}
.d-todo-input {
    flex: 1; height: 36px; border-radius: 10px;
    background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);
    color: #fff; padding: 0 12px; font-size: 12.5px; outline: none;
}
.d-todo-input:focus {
    border-color: var(--text-accent); background: rgba(255, 255, 255, 0.12);
}
.d-todo-btn {
    height: 36px; padding: 0 14px; border-radius: 10px;
    background: var(--text-accent); color: #fff; border: none;
    font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.18s;
}
.d-todo-btn:hover {
    background: var(--text-accent-light);
}

/* ── COMBINED PROFILE & DATE/TIME CARD ── */
.d-profile-combined {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    flex-shrink: 0;
    box-sizing: border-box;
}
.d-prof-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.d-prof-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.d-profile-avatar {
    width: 46px; height: 46px; border-radius: 50%;
    background: linear-gradient(135deg,#667eea,#764ba2);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px; font-weight: 700;
    box-shadow: 0 4px 14px rgba(102,126,234,.4);
    flex-shrink: 0;
}
.d-profile-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.d-profile-name {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 110px;
}
.d-profile-role {
    font-size: 8px;
    color: var(--text-accent);
    font-weight: 700;
    background: rgba(79,70,229,0.15);
    border-radius: 12px;
    padding: 2px 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    align-self: flex-start;
}
.d-prof-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    text-align: right;
    gap: 2px;
}
.d-time {
    font-size: 20px;
    font-weight: 300;
    color: #fff;
    letter-spacing: -0.5px;
    line-height: 1;
}
.d-time span {
    font-size: 11px;
    font-weight: 400;
    opacity: .6;
    margin-left: 2px;
}
.d-date-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}
.d-day {
    font-size: 9px;
    color: rgba(255,255,255,.45);
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 600;
}
.d-date {
    font-size: 11.5px;
    color: rgba(255,255,255,.75);
    font-weight: 500;
}
.d-prof-bottom {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: 10px;
    border-top: 1px solid rgba(255,255,255,.1);
}
.d-profile-logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px;
    border-radius: 10px;
    background: rgba(239,68,68,.12);
    border: 1px solid rgba(239,68,68,.25);
    color: rgba(239,68,68,.9);
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
    transition: all .18s;
    width: 100%;
}
.d-profile-logout-btn:hover {
    background: rgba(239,68,68,.22);
    border-color: rgba(239,68,68,.4);
    box-shadow: 0 4px 12px rgba(239,68,68,0.15);
}

/* ── NOTIFICATIONS CARD ── */
.d-notif { padding: 18px; display: flex; flex-direction: column; flex: 1; min-height: 0; box-sizing: border-box; }
.d-notif-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; flex-shrink: 0;
}
.d-notif-title { font-size: 11px; font-weight: 700; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: 1px; }
.d-notif-count { background: #ef4444; color: #fff; border-radius: 10px; padding: 2px 7px; font-size: 10px; font-weight: 700; }

.d-notif-list {
    flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;
}
.d-notif-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.07);
    transition: all 0.2s ease;
}
.d-notif-row:hover {
    background: rgba(255, 255, 255, 0.08);
}
.d-notif-icon-box {
    width: 36px; height: 36px; border-radius: 8px;
    background: rgba(255, 255, 255, 0.08);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-accent); font-size: 16px; flex-shrink: 0;
}
.d-notif-content-box {
    display: flex; flex-direction: column; gap: 2px; flex: 1; overflow: hidden;
}
.d-notif-text {
    font-size: 12.5px; font-weight: 600; color: var(--text-main);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.d-notif-sub {
    font-size: 10.5px; color: var(--text-muted);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.d-notif-empty {
    flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: rgba(255,255,255,.3); font-size: 12px; gap: 6px;
}
.d-notif-empty i { font-size: 24px; opacity: .5; }
.d-notif-link {
    display: block; text-align: center; padding-top: 10px;
    font-size: 11px; color: rgba(102,126,234,.9); text-decoration: none;
    padding: 7px; border-radius: 8px; background: rgba(79,70,229,.1); font-weight: 600;
    transition: background .18s; flex-shrink: 0; margin-top: 10px;
}
.d-notif-link:hover { background: rgba(79,70,229,.18); }
</style>

<!-- Background -->
<div id="dashBg"></div>

<div class="dash-root">

    <!-- Top Bar -->
    <div class="db-topbar">
        <?php
        $hour = date('H');
        if ($hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour < 17) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }
        ?>
        <h2 class="db-heading" id="dbGreeting"><?= $greeting ?></h2>
        
        <div class="db-actions">
            <!-- Circular Search Button same as other buttons -->
            <a href="javascript:void(0)" class="db-icon-btn" onclick="openCmdPalette()" title="Search">
                <i class="ph ph-magnifying-glass"></i>
            </a>

            <?php if (!empty($storeUrl)): ?>
            <a href="<?= htmlspecialchars($storeUrl) ?>" target="_blank" class="db-icon-btn" title="Store">
                <i class="ph ph-storefront"></i>
            </a>
            <?php endif; ?>
            
            <a href="<?= APP_URL ?>/notification" class="db-icon-btn" title="Notifications">
                <i class="ph ph-bell"></i>
                <?php if ($notifCount > 0): ?>
                    <span class="db-nbadge"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Settings icon only for admin role -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="<?= APP_URL ?>/settings" class="db-icon-btn" title="Settings">
                <i class="ph ph-gear"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3-Column Grid Layout -->
    <div class="db-grid">

        <!-- COLUMN 1: Quick Access (Square buttons) -->
        <div class="db-col">
            <div class="d-card d-quick">
                <div class="d-quick-header">Quick Access</div>
                <div class="d-quick-grid-container">
                    <div class="d-quick-grid">
                        <a href="<?= APP_URL ?>/sales/create" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#3b82f6"><i class="ph ph-file-plus"></i></div>
                            <span>Create Invoice</span>
                        </a>
                        <a href="<?= APP_URL ?>/salesorder/create" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#10b981"><i class="ph ph-file-text"></i></div>
                            <span>Sales Order</span>
                        </a>
                        <a href="<?= APP_URL ?>/customer" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#6366f1"><i class="ph ph-users"></i></div>
                            <span>Customers</span>
                        </a>
                        <a href="<?= APP_URL ?>/supplier" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#f59e0b"><i class="ph ph-factory"></i></div>
                            <span>Suppliers</span>
                        </a>
                        <a href="<?= APP_URL ?>/inventory" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#ec4899"><i class="ph ph-package"></i></div>
                            <span>Inventory</span>
                        </a>
                        <a href="<?= APP_URL ?>/reptracking" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#06b6d4"><i class="ph ph-map-pin"></i></div>
                            <span>Route Control</span>
                        </a>
                        <a href="<?= APP_URL ?>/customerpayment" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#14b8a6"><i class="ph ph-hand-coins"></i></div>
                            <span>Payments</span>
                        </a>
                        <a href="<?= APP_URL ?>/grn" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#8b5cf6"><i class="ph ph-tray-arrow-down"></i></div>
                            <span>GRN</span>
                        </a>
                        <a href="<?= APP_URL ?>/purchase" class="d-qbtn" target="_blank">
                            <div class="d-qbtn-icon" style="color:#ef4444"><i class="ph ph-shopping-cart"></i></div>
                            <span>Purchase Orders</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN 2: To-Do List (Horizontal rows like uploaded image) -->
        <div class="db-col">
            <div class="d-card d-todo">
                <div class="d-todo-header">To-Do List</div>
                <div class="d-todo-list" id="todoListContainer">
                    <!-- Loaded dynamically via AJAX -->
                    <div class="cmd-empty-state" style="padding: 20px 0;">
                        <i class="ph ph-spinner" style="animation: spin 1s linear infinite;"></i>
                        <span>Loading tasks...</span>
                    </div>
                </div>
                <form class="d-todo-add" id="todoAddForm" onsubmit="event.preventDefault(); addNewTodo();">
                    <input type="text" class="d-todo-input" id="todoInput" placeholder="Add a new task..." required autocomplete="off">
                    <button type="submit" class="d-todo-btn">Add</button>
                </form>
            </div>
        </div>

        <!-- COLUMN 3: Profile & Date/Time (Combined) + Notifications -->
        <div class="db-col db-col-right">
            <!-- Combined Profile & Clock Card -->
            <div class="d-card d-profile-combined">
                <div class="d-prof-top">
                    <div class="d-prof-left">
                        <div class="d-profile-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)) ?></div>
                        <div class="d-profile-info">
                            <div class="d-profile-name" title="<?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
                            <span class="d-profile-role"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'Staff')) ?></span>
                        </div>
                    </div>
                    <div class="d-prof-right">
                        <div class="d-time" id="dashTime">--:-- <span>--</span></div>
                        <div class="d-date-info">
                            <div class="d-day" id="dashDay">--</div>
                            <div class="d-date" id="dashDate">--</div>
                        </div>
                    </div>
                </div>
                <div class="d-prof-bottom">
                    <a href="<?= APP_URL ?>/auth/logout" class="d-profile-logout-btn">
                        <i class="ph ph-sign-out"></i> Sign Out
                    </a>
                </div>
            </div>

            <!-- Notifications Card -->
            <div class="d-card d-notif">
                <div class="d-notif-header">
                    <span class="d-notif-title">Notifications</span>
                    <?php if ($notifCount > 0): ?>
                        <span class="d-notif-count"><?= $notifCount ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-notif-list">
                    <?php if ($notifCount === 0): ?>
                        <div class="d-notif-empty">
                            <i class="ph ph-bell-slash"></i>
                            <span>All caught up!</span>
                        </div>
                    <?php else: ?>
                        <div class="d-notif-row">
                            <div class="d-notif-icon-box" style="color: #6366f1;">
                                <i class="ph ph-bell"></i>
                            </div>
                            <div class="d-notif-content-box">
                                <span class="d-notif-text">Pending Alerts</span>
                                <span class="d-notif-sub">You have <?= $notifCount ?> unread notification<?= $notifCount > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="<?= APP_URL ?>/notification" class="d-notif-link">View All Notifications</a>
            </div>
        </div>

    </div>
</div>

<script>
/* ── Force padding and dimensions fallback ── */
function fixMainContentPadding() {
    const mc = document.querySelector(".main-content");
    if (mc) {
        mc.style.padding = "16px 24px";
        mc.style.paddingBottom = "60px";
        mc.style.overflow = "hidden";
        mc.style.height = "calc(100vh - 82px)";
        mc.style.boxSizing = "border-box";
    }
}
document.addEventListener("DOMContentLoaded", fixMainContentPadding);
window.addEventListener("resize", fixMainContentPadding);
fixMainContentPadding();

/* ── Clock ── */
function updateClock() {
    const now = new Date();
    const hh = now.getHours(), mm = now.getMinutes();
    const ampm = hh >= 12 ? 'PM' : 'AM';
    const h12 = hh % 12 || 12;
    const pad = n => String(n).padStart(2,'0');
    const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const el = document.getElementById('dashTime');
    if (el) el.innerHTML = `${pad(h12)}:${pad(mm)} <span>${ampm}</span>`;
    const de = document.getElementById('dashDate');
    if (de) de.textContent = `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    const dy = document.getElementById('dashDay');
    if (dy) dy.textContent = days[now.getDay()];
    
    // Realtime greeting updates
    const greetingEl = document.getElementById('dbGreeting');
    if (greetingEl) {
        let g = "Good Evening";
        if (hh < 12) g = "Good Morning";
        else if (hh < 17) g = "Good Afternoon";
        greetingEl.textContent = g;
    }
}
updateClock(); setInterval(updateClock, 1000);

/* ── Unsplash Wallpaper ── */
(async function loadWallpaper() {
    const KEY = 'curtiss_wp', TIME_KEY = 'curtiss_wp_ts', ONE_HOUR = 3600000;
    const bg = document.getElementById('dashBg');
    if (!bg) return;
    const cached = localStorage.getItem(KEY);
    const ts = parseInt(localStorage.getItem(TIME_KEY) || '0');
    if (cached && (Date.now() - ts) < ONE_HOUR) { applyBg(cached); return; }
    try {
        const queries = ['luxury office interior','modern workspace minimal','architecture interior light','business premium interior'];
        const q = queries[Math.floor(Date.now() / ONE_HOUR) % queries.length];
        const res = await fetch(`https://api.unsplash.com/photos/random?client_id=l9G-Db3ETQ2zxJN1viWaIpgPjshWK2z9gmczP3kWlX4&orientation=landscape&query=${encodeURIComponent(q)}&content_filter=high`);
        const data = await res.json();
        const url = data.urls?.regular || data.urls?.full;
        if (url) { localStorage.setItem(KEY, url); localStorage.setItem(TIME_KEY, Date.now().toString()); applyBg(url); }
    } catch(e) { bg.classList.add('ready'); }
    function applyBg(url) {
        const img = new Image();
        img.onload = () => { bg.style.backgroundImage = `url('${url}')`; bg.classList.add('ready'); };
        img.onerror = () => bg.classList.add('ready');
        img.src = url;
    }
})();

/* ── AJAX To-Do List ── */
async function loadTodos() {
    const container = document.getElementById('todoListContainer');
    try {
        const res = await fetch('<?= APP_URL ?>/dashboard/getTodos');
        const data = await res.json();
        if (data.length === 0) {
            container.innerHTML = `
                <div class="d-notif-empty" style="padding: 20px 0;">
                    <i class="ph ph-check-square"></i>
                    <span>No tasks yet! Add one below.</span>
                </div>
            `;
            return;
        }
        let html = '';
        data.forEach(todo => {
            const completedClass = todo.is_completed == 1 ? 'completed' : '';
            const checkIcon = todo.is_completed == 1 ? 'ph-check-circle' : 'ph-circle';
            html += `
                <div class="d-todo-row ${completedClass}">
                    <div class="d-todo-icon-box" onclick="toggleTodo(${todo.id})">
                        <i class="ph ${checkIcon}"></i>
                    </div>
                    <span class="d-todo-text">${escapeHtml(todo.task)}</span>
                    <button class="d-todo-delete" onclick="deleteTodo(${todo.id})">
                        <i class="ph ph-trash"></i>
                    </button>
                </div>
            `;
        });
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = `
            <div class="d-notif-empty">
                <i class="ph ph-x-circle" style="color: #ef4444;"></i>
                <span>Failed to load tasks.</span>
            </div>
        `;
    }
}

async function addNewTodo() {
    const input = document.getElementById('todoInput');
    const task = input.value.trim();
    if (!task) return;
    try {
        const formData = new FormData();
        formData.append('task', task);
        const res = await fetch('<?= APP_URL ?>/dashboard/addTodo', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (result.success) {
            input.value = '';
            loadTodos();
        } else {
            alert(result.error || 'Failed to add task');
        }
    } catch(e) {
        alert('Failed to connect to the server');
    }
}

async function toggleTodo(id) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        const res = await fetch('<?= APP_URL ?>/dashboard/toggleTodo', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (result.success) {
            loadTodos();
        }
    } catch(e) {
        console.error(e);
    }
}

async function deleteTodo(id) {
    if (!confirm('Are you sure you want to delete this task?')) return;
    try {
        const formData = new FormData();
        formData.append('id', id);
        const res = await fetch('<?= APP_URL ?>/dashboard/deleteTodo', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (result.success) {
            loadTodos();
        }
    } catch(e) {
        console.error(e);
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', loadTodos);
</script>