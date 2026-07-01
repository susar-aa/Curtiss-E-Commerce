<?php

// Enable error reporting to prevent blank 500 errors in the future
// But suppress for AJAX requests to prevent HTML in JSON responses
if (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (!function_exists('hasPermission')) {
    function hasPermission($module, $action = 'view') {
        if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
            return true;
        }
        if (!isset($_SESSION['permissions'])) {
            return false;
        }
        $perms = $_SESSION['permissions'];
        if (!isset($perms[$module])) {
            return false;
        }
        if ($action === 'view') {
            return (bool)($perms[$module]['can_view'] ?? false);
        } elseif ($action === 'create_edit') {
            return (bool)($perms[$module]['can_create_edit'] ?? false);
        } elseif ($action === 'delete') {
            return (bool)($perms[$module]['can_delete'] ?? false);
        }
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?? 'Dashboard' ?></title>
    
    <!-- PWA Manifest & Mobile App Support -->
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="theme-color" content="#0066cc">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/icon-192.png">
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= APP_URL ?>/service-worker.js')
                    .then((reg) => console.log('PWA Service Worker registered successfully:', reg.scope))
                    .catch((err) => console.log('PWA Service Worker registration failed:', err));
            });
        }
    </script>
    
    <!-- Phosphor Icons for a clean, modern look (replacing emojis) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <style>
        :root {
            /* Glassmorphism Core Variables */
            --glass-bg: rgba(255, 255, 255, 0.72);
            --glass-border: rgba(255, 255, 255, 0.35);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            --glass-blur: 24px;
            
            --bg-color: #f0f2f6;
            --text-main: #1a1a2e;
            --text-muted: #6b7280;
            --text-accent: #4f46e5;
            --text-accent-light: #818cf8;
            
            /* Mega Menu Variables */
            --mega-bg: #ffffff;
            --mega-card-bg: rgba(249, 250, 251, 0.8);
            --mega-card-hover: rgba(238, 242, 255, 0.9);
            --mega-icon-border: rgba(229, 231, 235, 0.6);
            --mega-icon-bg: rgba(255, 255, 255, 0.7);
            --mega-divider: rgba(0, 0, 0, 0.06);
            --mega-hover: rgba(79, 70, 229, 0.06);
            
            /* Dashboard Variables */
            --card-bg: rgba(255, 255, 255, 0.78);
            --card-border: rgba(255, 255, 255, 0.4);
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }
        
        @media (prefers-color-scheme: dark) {
            :root {
                --glass-bg: rgba(20, 20, 35, 0.78);
                --glass-border: rgba(255, 255, 255, 0.10);
                --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                
                --bg-color: #0f0f1a;
                --text-main: #e8e8f0;
                --text-muted: #9ca3af;
                --text-accent: #818cf8;
                --text-accent-light: #a5b4fc;
                
                --mega-bg: #141426;
                --mega-card-bg: rgba(35, 35, 55, 0.8);
                --mega-card-hover: rgba(55, 55, 80, 0.9);
                --mega-icon-border: rgba(55, 55, 80, 0.6);
                --mega-icon-bg: rgba(35, 35, 55, 0.7);
                --mega-divider: rgba(255, 255, 255, 0.06);
                --mega-hover: rgba(129, 140, 248, 0.08);
                
                --card-bg: rgba(20, 20, 38, 0.82);
                --card-border: rgba(255, 255, 255, 0.08);
                --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(ellipse at 10% 20%, rgba(79, 70, 229, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 80%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* --- GLASSMORPHISM FLOATING NAV BAR (Redesigned) --- */
        .glass-nav {
            position: fixed;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2000;
            display: flex;
            align-items: center;
            height: 54px;
            padding: 0 12px;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 28px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08), 0 1px 4px rgba(0,0,0,0.04);
            width: calc(100% - 40px);
            max-width: 1400px;
            transition: box-shadow 0.3s ease;
            gap: 8px;
        }
        @media (prefers-color-scheme: dark) {
            .glass-nav {
                background: rgba(22, 22, 38, 0.88);
                border-color: rgba(255,255,255,0.10);
                box-shadow: 0 4px 24px rgba(0,0,0,0.35);
            }
        }
        .glass-nav:hover {
            box-shadow: 0 8px 36px rgba(0, 0, 0, 0.13);
        }

        /* Brand section */
        .nav-back-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: var(--text-main);
            transition: background-color 0.2s, transform 0.2s;
            text-decoration: none;
            cursor: pointer;
        }
        .nav-back-btn:hover {
            background-color: rgba(0, 0, 0, 0.08);
            transform: translateX(-2px);
        }
        @media (prefers-color-scheme: dark) {
            .nav-back-btn:hover {
                background-color: rgba(255, 255, 255, 0.12);
            }
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            flex-shrink: 0;
            padding: 0 6px;
        }
        .nav-brand-logo {
            width: 30px;
            height: 30px;
            background: var(--text-main);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -0.5px;
            flex-shrink: 0;
        }
        @media (prefers-color-scheme: dark) {
            .nav-brand-logo { background: #e8e8f0; color: #1a1a2e; }
        }
        .nav-brand-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.3px;
        }

        /* Vertical divider */
        .glass-nav-divider {
            width: 1px;
            height: 24px;
            background: rgba(0,0,0,0.08);
            flex-shrink: 0;
            margin: 0 4px;
        }
        @media (prefers-color-scheme: dark) {
            .glass-nav-divider { background: rgba(255,255,255,0.08); }
        }

        /* Nav menu items (left area) */
        .glass-nav-left {
            display: flex;
            align-items: center;
            height: 100%;
            gap: 1px;
            flex-shrink: 0;
        }
        .glass-nav-item {
            cursor: pointer;
            padding: 0 11px;
            display: flex;
            align-items: center;
            gap: 5px;
            height: 36px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 500;
            transition: color 0.18s ease;
            white-space: nowrap;
            position: relative;
        }
        .glass-nav-item:hover {
            color: var(--text-accent);
        }

        /* Center search bar */
        .nav-search-wrap {
            flex: 1;
            min-width: 0;
            display: flex;
            justify-content: center;
            padding: 0 8px;
        }
        .nav-search {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            max-width: 480px;
            height: 36px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 20px;
            padding: 0 14px 0 14px;
            transition: background 0.2s, box-shadow 0.2s;
            cursor: text;
        }
        @media (prefers-color-scheme: dark) {
            .nav-search { background: rgba(255,255,255,0.07); }
        }
        .nav-search:hover, .nav-search:focus-within {
            background: rgba(0,0,0,0.08);
            box-shadow: 0 0 0 2px rgba(79,70,229,0.18);
        }
        @media (prefers-color-scheme: dark) {
            .nav-search:hover, .nav-search:focus-within { background: rgba(255,255,255,0.11); }
        }
        .nav-search i {
            color: var(--text-muted);
            font-size: 15px;
            flex-shrink: 0;
        }
        .nav-search input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 13px;
            color: var(--text-main);
            width: 100%;
            font-family: inherit;
        }
        .nav-search input::placeholder { color: var(--text-muted); }
        .nav-search-shortcut {
            width: 26px;
            height: 18px;
            background: rgba(0,0,0,0.07);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0;
            flex-shrink: 0;
            font-family: inherit;
            cursor: pointer;
        }
        @media (prefers-color-scheme: dark) {
            .nav-search-shortcut { background: rgba(255,255,255,0.1); }
        }

        /* Right action cluster */
        .glass-nav-right {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }
        .nav-icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 17px;
            text-decoration: none;
            transition: background 0.18s, color 0.18s;
            position: relative;
            cursor: pointer;
        }
        .nav-icon-btn:hover {
            background: rgba(79, 70, 229, 0.08);
            color: var(--text-accent);
        }
        .nav-icon-btn.active-red { color: #ef4444; }
        .nav-icon-btn.active-red:hover { background: rgba(239,68,68,0.08); color: #ef4444; }

        /* User pill */
        .nav-user-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 3px 10px 3px 12px;
            border-radius: 20px;
            cursor: default;
            transition: background 0.18s;
        }
        .nav-user-pill:hover {
            background: rgba(79,70,229,0.06);
        }
        .nav-user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            white-space: nowrap;
        }
        .nav-user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
            letter-spacing: 0;
        }

        /* Badge */
        .badge-alert {
            background: #ef4444;
            color: #fff;
            border-radius: 10px;
            padding: 2px 5px;
            font-size: 9px;
            font-weight: 700;
            position: absolute;
            top: 3px;
            right: 3px;
            border: 2px solid rgba(255,255,255,0.9);
            line-height: 1.2;
            min-width: 16px;
            text-align: center;
        }

        /* --- MEGA MENU (Glassmorphism) --- */
        .glass-menu-container {
            height: 100%;
            display: flex;
            align-items: center;
            position: relative;
        }
        .glass-menu-container:not(:last-child)::after {
            content: '';
            width: 1px;
            height: 14px;
            background: rgba(0, 0, 0, 0.08);
            align-self: center;
            margin-left: 6px;
            margin-right: 6px;
            flex-shrink: 0;
        }
        @media (prefers-color-scheme: dark) {
            .glass-menu-container:not(:last-child)::after {
                background: rgba(255, 255, 255, 0.12);
            }
        }
        .glass-menu-container .mega-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 8px;
            background: var(--mega-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
            padding: 8px 0;
            z-index: 3000;
            flex-direction: row;
            cursor: default;
            min-width: 200px;
            animation: megaFadeIn 0.2s ease;
        }
        @keyframes megaFadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .glass-menu-container:hover .mega-menu { display: flex; }
        .align-right .mega-menu { left: auto; right: 0; }
        .glass-menu-container:hover .mega-menu .glass-nav-item {
            background: transparent;
            color: var(--text-main);
        }
        .mega-menu-col {
            padding: 12px 20px;
            display: flex;
            flex-direction: column;
            min-width: 220px;
        }
        .mega-menu-col:not(:first-child) { border-left: 1px solid var(--mega-divider); }
        .mega-menu-header {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            font-weight: 600;
            padding: 0 6px;
        }
        .mega-cards-grid { display: flex; gap: 10px; }
        .mega-card {
            background: var(--mega-card-bg);
            border-radius: 12px;
            padding: 16px;
            width: 130px;
            height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-decoration: none;
            transition: background 0.2s ease, transform 0.2s ease;
            box-sizing: border-box;
            border: 1px solid transparent;
        }
        .mega-card:hover {
            background: var(--mega-card-hover);
            transform: translateY(-2px);
            border-color: var(--glass-border);
        }
        .mega-card .icon { font-size: 22px; color: var(--text-main); }
        .mega-card-text {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .mega-card-text .title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
        }
        .mega-card-text .desc {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.3;
        }
        
        /* List Items */
        .mega-list-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 10px;
            margin: 0 -6px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .mega-list-item:hover {
            background: var(--mega-hover);
        }
        .mega-list-item .icon-wrapper {
            width: 34px;
            height: 34px;
            border: 1px solid var(--mega-icon-border);
            background: var(--mega-icon-bg);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            color: var(--text-main);
            backdrop-filter: blur(8px);
        }
        .mega-list-item-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-top: 1px;
        }
        .mega-list-item-content .title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
        }
        .mega-list-item-content .desc {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        /* Overrides for specific colors */
        .text-danger { color: #ef4444 !important; }
        .text-warning { color: #f59e0b !important; }
        .text-primary { color: var(--text-accent) !important; }

        /* Notification Badge */
        .badge-alert {
            background: #ef4444;
            color: #fff;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            position: absolute;
            top: 2px;
            right: -6px;
            border: 2px solid var(--glass-bg);
            line-height: 1.2;
            min-width: 18px;
            text-align: center;
        }

        /* --- MAIN APP CONTAINER --- */
        .app-container {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
            margin-top: 82px; /* Space for floating nav bar (12px top + 54px height + 16px gap) */
        }
        
        .main-content {
            flex: 1;
            padding: 28px 32px;
            padding-bottom: 80px;
            overflow-y: auto;
        }
        
        /* Glass Card Component */
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            transition: box-shadow 0.3s ease, transform 0.2s ease;
        }
        
        .glass-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        /* --- RECENT BAR (Glassmorphism) --- */
        .mac-recent-bar {
            position: fixed;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            box-shadow: var(--glass-shadow);
            height: 42px;
            z-index: 2000;
            display: flex;
            align-items: center;
            padding: 0 12px;
            box-sizing: border-box;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
            min-width: 200px;
            max-width: calc(100% - 64px);
            gap: 10px;
        }
        
        .mac-recent-bar.collapsed {
            transform: translateX(-50%) translateY(calc(100% + 20px));
            opacity: 0;
            pointer-events: none;
        }
        
        .recent-toggle-tab {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px 10px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
            flex-shrink: 0;
            background: rgba(0,0,0,0.03);
        }
        @media (prefers-color-scheme: dark) {
            .recent-toggle-tab { background: rgba(255,255,255,0.05); }
        }
        .recent-toggle-tab:hover {
            background: rgba(79, 70, 229, 0.1);
            color: var(--text-accent);
        }
        
        .recent-links-container {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
            flex: 1;
            align-items: center;
            padding: 0 4px;
        }
        .recent-links-container::-webkit-scrollbar { display: none; }
        
        .recent-link {
            font-size: 12px;
            color: var(--text-main);
            text-decoration: none;
            background: rgba(0, 0, 0, 0.04);
            padding: 4px 12px;
            border-radius: 8px;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s, color 0.2s;
            flex-shrink: 0;
        }
        @media (prefers-color-scheme: dark) { .recent-link { background: rgba(255,255,255,0.06); } }
        .recent-link:hover { 
            background: var(--text-accent); 
            color: #fff; 
        }
        
        /* Live Clock in Recent Bar */
        .recent-clock {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            flex-shrink: 0;
            padding: 0 6px;
            border-left: 1px solid var(--mega-divider);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Utility */
        .hidden { display: none !important; }

        /* Hamburger menu button */
        .nav-menu-btn {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 4px; cursor: pointer; flex-shrink: 0;
            border: none; background: transparent;
            transition: background 0.18s;
            padding: 0;
        }
        .nav-menu-btn:hover { background: rgba(79,70,229,0.08); }
        .nav-menu-btn span {
            display: block; width: 16px; height: 2px;
            background: var(--text-muted); border-radius: 2px;
            transition: background 0.18s;
        }
        .nav-menu-btn:hover span { background: var(--text-accent); }

        /* Full-screen menu overlay */
        .fs-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(8,8,20,0.94);
            backdrop-filter: blur(32px);
            -webkit-backdrop-filter: blur(32px);
            display: none; opacity: 0;
            transition: opacity 0.3s ease;
            overflow-y: auto;
        }
        .fs-overlay.open { display: block; }
        .fs-overlay.visible { opacity: 1; }
        .fs-inner {
            max-width: 960px; margin: 0 auto;
            padding: 60px 40px 80px;
        }
        .fs-close {
            position: fixed; top: 20px; right: 24px;
            width: 44px; height: 44px; border-radius: 50%;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.8); font-size: 22px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 10000;
            transition: background 0.18s, color 0.18s;
        }
        .fs-close:hover { background: rgba(239,68,68,0.2); color: #ff8080; }
        .fs-title {
            font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
            text-transform: uppercase; color: rgba(255,255,255,0.35);
            margin-bottom: 28px;
        }
        .fs-sections {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 28px;
        }
        .fs-section { display: flex; flex-direction: column; gap: 6px; }
        .fs-section-label {
            font-size: 10px; font-weight: 700; letter-spacing: 1.2px;
            text-transform: uppercase; color: rgba(255,255,255,0.3);
            margin-bottom: 4px; padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .fs-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .fs-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border-radius: 12px;
            text-decoration: none; color: rgba(255,255,255,0.82);
            font-size: 14px; font-weight: 500;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.07);
            transition: background 0.18s, color 0.18s, border-color 0.18s;
            white-space: nowrap;
        }
        .fs-link:hover {
            background: rgba(79,70,229,0.2);
            border-color: rgba(79,70,229,0.4);
            color: #fff;
        }
        .fs-link i { font-size: 16px; opacity: 0.75; }
        .fs-arrow { color: rgba(255,255,255,0.2); font-size: 18px; flex-shrink: 0; }

        /* Nav page heading (right side of navbar) */
        .nav-page-heading {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 8px 0 12px;
            flex-shrink: 0;
        }
        .nav-page-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--text-accent);
            flex-shrink: 0;
            animation: pulse-dot 2.5s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }
        .nav-page-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: -0.2px;
            white-space: nowrap;
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Dashboard top bar (search + user actions row) */
        .dashboard-topbar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .dashboard-search {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 220px;
            max-width: 480px;
            height: 42px;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 22px;
            padding: 0 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: box-shadow 0.2s, border-color 0.2s;
            cursor: text;
        }
        @media (prefers-color-scheme: dark) {
            .dashboard-search { background: rgba(30,30,50,0.85); border-color: rgba(255,255,255,0.08); }
        }
        .dashboard-search:hover, .dashboard-search:focus-within {
            box-shadow: 0 4px 20px rgba(79,70,229,0.15);
            border-color: rgba(79,70,229,0.3);
        }
        .dashboard-search i { color: var(--text-muted); font-size: 16px; flex-shrink: 0; }
        .dashboard-search input {
            border: none; background: transparent; outline: none;
            font-size: 13.5px; color: var(--text-main); width: 100%; font-family: inherit;
        }
        .dashboard-search input::placeholder { color: var(--text-muted); }
        .dashboard-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
        }
        .dash-icon-btn {
            width: 42px; height: 42px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); font-size: 18px; text-decoration: none;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            position: relative;
            cursor: pointer;
        }
        @media (prefers-color-scheme: dark) {
            .dash-icon-btn { background: rgba(30,30,50,0.85); border-color: rgba(255,255,255,0.08); }
        }
        .dash-icon-btn:hover {
            background: rgba(79,70,229,0.1);
            color: var(--text-accent);
            box-shadow: 0 4px 16px rgba(79,70,229,0.15);
        }
        .dash-icon-btn.danger { color: #ef4444; }
        .dash-icon-btn.danger:hover { background: rgba(239,68,68,0.1); box-shadow: 0 4px 16px rgba(239,68,68,0.15); }
        .dash-user-pill {
            display: flex; align-items: center; gap: 10px;
            padding: 5px 14px 5px 5px;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0,0,0,0.07);
            border-radius: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            cursor: default;
            transition: background 0.18s;
        }
        @media (prefers-color-scheme: dark) {
            .dash-user-pill { background: rgba(30,30,50,0.85); border-color: rgba(255,255,255,0.08); }
        }
        .dash-user-pill:hover { background: rgba(79,70,229,0.06); }
        .dash-user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 700; flex-shrink: 0;
        }
        .dash-user-info { display: flex; flex-direction: column; gap: 1px; }
        .dash-user-name { font-size: 13px; font-weight: 600; color: var(--text-main); white-space: nowrap; }
        .dash-user-role { font-size: 11px; color: var(--text-muted); font-weight: 400; }
        .dash-notif-badge {
            background: #ef4444; color: #fff;
            border-radius: 10px; padding: 2px 5px; font-size: 9px; font-weight: 700;
            position: absolute; top: 3px; right: 3px;
            border: 2px solid rgba(255,255,255,0.9); line-height: 1.2; min-width: 16px; text-align: center;
        }

        /* ── COMMAND PALETTE ── */
        .cmd-palette-overlay {
            position: fixed; inset: 0; z-index: 10000;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            display: none; align-items: flex-start; justify-content: center;
            padding: 80px 20px 20px;
            opacity: 0; transition: opacity 0.25s ease;
        }
        .cmd-palette-overlay.open { display: flex; }
        .cmd-palette-overlay.visible { opacity: 1; }
        .cmd-palette-container {
            width: 100%; max-width: 760px;
            background: rgba(30, 41, 59, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            display: flex; flex-direction: column;
            transform: translateY(-20px); transition: transform 0.25s ease;
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        }
        @media (prefers-color-scheme: light) {
            .cmd-palette-container {
                background: rgba(255, 255, 255, 0.9);
                border-color: rgba(0, 0, 0, 0.08);
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            }
        }
        .cmd-palette-overlay.visible .cmd-palette-container {
            transform: translateY(0);
        }
        .cmd-palette-header {
            display: flex; align-items: center; gap: 14px;
            padding: 18px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        @media (prefers-color-scheme: light) {
            .cmd-palette-header { border-bottom-color: rgba(0, 0, 0, 0.06); }
        }
        .cmd-palette-header i { font-size: 20px; color: var(--text-muted); }
        .cmd-palette-input {
            border: none; background: transparent; outline: none;
            font-size: 16px; color: var(--text-main); width: 100%;
            font-family: inherit;
        }
        .cmd-palette-input::placeholder { color: var(--text-muted); }
        .cmd-palette-content {
            display: grid; grid-template-columns: 1fr 260px;
            min-height: 280px; max-height: 440px;
        }
        @media (max-width: 600px) {
            .cmd-palette-content { grid-template-columns: 1fr; }
            .cmd-palette-sidebar { display: none; }
        }
        .cmd-palette-results {
            padding: 16px; overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        @media (prefers-color-scheme: light) {
            .cmd-palette-results { border-right-color: rgba(0, 0, 0, 0.06); }
        }
        .cmd-palette-sidebar {
            padding: 20px; overflow-y: auto;
            background: rgba(0, 0, 0, 0.12);
        }
        @media (prefers-color-scheme: light) {
            .cmd-palette-sidebar { background: rgba(0, 0, 0, 0.02); }
        }
        .cmd-palette-sidebar-title {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: var(--text-muted); margin-bottom: 12px;
        }
        .cmd-palette-tags {
            display: flex; flex-direction: column; gap: 8px;
        }
        .cmd-tag-item {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 12px; color: var(--text-main); text-decoration: none;
            padding: 6px 10px; border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.06);
            cursor: pointer; transition: all 0.15s ease;
        }
        @media (prefers-color-scheme: light) {
            .cmd-tag-item {
                background: rgba(0, 0, 0, 0.03); border-color: rgba(0, 0, 0, 0.04);
            }
        }
        .cmd-tag-item:hover {
            background: var(--text-accent); color: #fff;
            border-color: var(--text-accent);
        }
        .cmd-tag-item-code {
            font-family: monospace; font-size: 11px; font-weight: 600;
            background: rgba(255, 255, 255, 0.15); padding: 1px 5px; border-radius: 4px;
        }
        .cmd-tag-item:hover .cmd-tag-item-code {
            background: rgba(255, 255, 255, 0.25);
        }
        .cmd-result-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px; border-radius: 12px;
            color: var(--text-main); text-decoration: none;
            cursor: pointer; transition: background 0.15s ease;
            margin-bottom: 6px;
        }
        .cmd-result-item:last-child { margin-bottom: 0; }
        .cmd-result-item:hover, .cmd-result-item.selected {
            background: rgba(79, 70, 229, 0.15);
        }
        .cmd-result-icon {
            width: 36px; height: 36px; border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-accent); font-size: 18px; flex-shrink: 0;
        }
        @media (prefers-color-scheme: light) {
            .cmd-result-icon { background: rgba(0, 0, 0, 0.04); }
        }
        .cmd-result-item:hover .cmd-result-icon, .cmd-result-item.selected .cmd-result-icon {
            background: var(--text-accent); color: #fff;
        }
        .cmd-result-details {
            display: flex; flex-direction: column; gap: 2px;
            overflow: hidden;
        }
        .cmd-result-title {
            font-size: 14px; font-weight: 600;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .cmd-result-subtitle {
            font-size: 11px; color: var(--text-muted);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .cmd-result-item:hover .cmd-result-subtitle, .cmd-result-item.selected .cmd-result-subtitle {
            color: var(--text-main); opacity: 0.8;
        }
        .cmd-result-tag {
            margin-left: auto; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
            padding: 2px 6px; border-radius: 4px;
            background: rgba(255, 255, 255, 0.1); color: var(--text-muted);
            flex-shrink: 0;
        }
        .cmd-result-item:hover .cmd-result-tag, .cmd-result-item.selected .cmd-result-tag {
            background: rgba(255, 255, 255, 0.2); color: #fff;
        }
        .cmd-empty-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 40px 20px; color: var(--text-muted); text-align: center;
            gap: 10px;
        }
        .cmd-empty-state i { font-size: 32px; opacity: 0.6; }
        .cmd-empty-state span { font-size: 13px; }
        .cmd-palette-footer {
            padding: 10px 24px;
            background: rgba(0, 0, 0, 0.15);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 11px; color: var(--text-muted);
            display: flex; align-items: center; justify-content: space-between;
        }
        @media (prefers-color-scheme: light) {
            .cmd-palette-footer {
                background: rgba(0, 0, 0, 0.02); border-top-color: rgba(0, 0, 0, 0.05);
            }
        }
        .cmd-kbd-guide { display: flex; align-items: center; gap: 4px; }
        .cmd-kbd {
            font-family: monospace; font-size: 9px; font-weight: 700;
            background: rgba(255, 255, 255, 0.12); padding: 2px 4px; border-radius: 3px;
            color: var(--text-main); border: 1px solid rgba(255, 255, 255, 0.08);
        }
        @media (prefers-color-scheme: light) {
            .cmd-kbd {
                background: rgba(0, 0, 0, 0.05); border-color: rgba(0, 0, 0, 0.08);
            }
        }
    </style>
</head>
<body>

    <?php 
        $db = new Database();
        $notifCount = 0;
        $storeUrl = 'http://localhost/Curtiss%20E%20Commerce';
        
        if (isset($_SESSION['user_id'])) {
            try {
                $db->query("SELECT COUNT(*) as unread FROM notifications WHERE user_id = :uid AND is_read = 0");
                $db->bind(':uid', $_SESSION['user_id']);
                $row = $db->single();
                if ($row) {
                    $notifCount = $row->unread;
                }
            } catch (Exception $e) {
                $notifCount = 0;
            }

            try {
                $db->query("SELECT ecommerce_store_url FROM company_settings LIMIT 1");
                $sett = $db->single();
                if ($sett && !empty($sett->ecommerce_store_url)) {
                    $storeUrl = $sett->ecommerce_store_url;
                }
            } catch (Exception $e) {
                // Keep default
            }
        }
        
        // Determine page title for the indicator
        $currentPageTitle = $data['title'] ?? 'Dashboard';
        // Try to extract a clean page name from URL
        $currentPageSlug = $_GET['url'] ?? 'dashboard';
        $pageParts = explode('/', $currentPageSlug);
        $currentPageName = !empty($pageParts[0]) ? ucwords(str_replace(['-', '_'], ' ', $pageParts[0])) : 'Dashboard';
        if (!empty($pageParts[1])) {
            $currentPageName .= ' &middot; ' . ucwords(str_replace(['-', '_'], ' ', $pageParts[1]));
        }
    ?>

    <!-- FLOATING NAV BAR -->
    <nav class="glass-nav">

        <!-- Back Button -->
        <a href="javascript:history.back()" class="nav-back-btn" title="Go Back">
            <i class="ph ph-arrow-left" style="font-size: 20px;"></i>
        </a>

        <!-- Brand: Logo only -->
        <a href="<?= APP_URL ?>/dashboard" class="nav-brand">
            <img src="<?= APP_URL ?>/Curtiss-bg-removed.png" alt="Curtiss" style="height: 32px; width: 32px; object-fit: contain; display: block;">
        </a>

        <div class="glass-nav-divider"></div>

        <!-- Left Nav Items (Mega Menus) -->
        <div class="glass-nav-left">
            
            <!-- 1. Sales & CRM -->
            <?php 
            $showSalesCRM = hasPermission('crm') || hasPermission('customer') || hasPermission('estimate') || hasPermission('sales') || hasPermission('creditnote') || hasPermission('dunning') || hasPermission('discount') || hasPermission('reptracking') || hasPermission('delivery') || hasPermission('territory');
            if ($showSalesCRM): 
            ?>
            <div class="glass-menu-container">
                <div class="glass-nav-item">Sales & CRM</div>
                <div class="mega-menu">
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Explore</div>
                        <div class="mega-cards-grid">
                            <?php if (hasPermission('crm')): ?>
                            <a href="<?= APP_URL ?>/crm" class="mega-card">
                                <div class="icon"><i class="ph ph-briefcase"></i></div>
                                <div class="mega-card-text">
                                    <div class="title">Leads & CRM</div>
                                    <div class="desc">Manage pipelines</div>
                                </div>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('customer')): ?>
                            <a href="<?= APP_URL ?>/customer" class="mega-card">
                                <div class="icon"><i class="ph ph-users"></i></div>
                                <div class="mega-card-text">
                                    <div class="title">Customer Center</div>
                                    <div class="desc">Client profiles</div>
                                </div>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Billing & AR</div>
                        <?php if (hasPermission('estimate')): ?>
                        <a href="<?= APP_URL ?>/estimate" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-file-text"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Quotes & Estimates</div>
                                <div class="desc">Send tailored pricing</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('sales')): ?>
                        <a href="<?= APP_URL ?>/sales/create" class="mega-list-item text-primary">
                            <div class="icon-wrapper text-primary"><i class="ph ph-pencil-simple"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-primary">Billing Creator</div>
                                <div class="desc">Create new Invoices or Sales Orders</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/salesorder" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-list-bullets"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Sales Order Center</div>
                                <div class="desc">Manage standard and route orders</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/sales" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-credit-card"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Invoices & AR</div>
                                <div class="desc">Manage receivables</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('creditnote')): ?>
                        <a href="<?= APP_URL ?>/creditnote" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-money"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Credit Notes</div>
                                <div class="desc">Issue client refunds</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('dunning')): ?>
                        <a href="<?= APP_URL ?>/dunning" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-clock"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Dunning Reminders</div>
                                <div class="desc">Automate follow-ups</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('discount')): ?>
                        <a href="<?= APP_URL ?>/discount" class="mega-list-item text-primary">
                            <div class="icon-wrapper text-primary"><i class="ph ph-tag"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-primary">Discount Feed</div>
                                <div class="desc">Configure rules & tiers</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Operations</div>
                        <?php if (hasPermission('reptracking')): ?>
                        <a href="<?php echo APP_URL; ?>/RepTracking/index" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-map-pin"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Master Route Control Panel</div>
                                <div class="desc">Unified route lifecycle & deliveries</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('territory')): ?>
                        <a href="<?= APP_URL ?>/territory" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-map-trifold"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Territory & Routing</div>
                                <div class="desc">Map sales zones</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('sales')): ?>
                        <a href="<?= APP_URL ?>/sales/deleted_list" class="mega-list-item">
                            <div class="icon-wrapper text-danger"><i class="ph ph-trash"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-danger">Deleted Invoices</div>
                                <div class="desc">View removed records</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
 
            <!-- 2. Supply Chain -->
            <?php 
            $showSupplyChain = hasPermission('inventory') || hasPermission('supplier') || hasPermission('category') || hasPermission('variation') || hasPermission('warehouse') || hasPermission('purchase') || hasPermission('grn') || hasPermission('supplier_return') || hasPermission('expenses');
            if ($showSupplyChain):
            ?>
            <div class="glass-menu-container">
                <div class="glass-nav-item">Supply Chain</div>
                <div class="mega-menu">
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Explore</div>
                        <div class="mega-cards-grid">
                            <?php if (hasPermission('inventory')): ?>
                            <a href="<?= APP_URL ?>/inventory" class="mega-card">
                                <div class="icon"><i class="ph ph-package"></i></div>
                                <div class="mega-card-text">
                                    <div class="title">Products</div>
                                    <div class="desc">Inventory catalog</div>
                                </div>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('supplier')): ?>
                            <a href="<?= APP_URL ?>/supplier" class="mega-card">
                                <div class="icon"><i class="ph ph-factory"></i></div>
                                <div class="mega-card-text">
                                    <div class="title">Supplier Center</div>
                                    <div class="desc">Manage suppliers</div>
                                </div>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Catalog Setup</div>
                        <?php if (hasPermission('category')): ?>
                        <a href="<?= APP_URL ?>/category" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-tag"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Product Categories</div>
                                <div class="desc">Organize your items</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('variation')): ?>
                        <a href="<?= APP_URL ?>/variation" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-sparkle"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Variations</div>
                                <div class="desc">Colors, sizes, types</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('warehouse')): ?>
                        <a href="<?= APP_URL ?>/warehouse" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-buildings"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Warehouse Mgmt</div>
                                <div class="desc">Locations and bins</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/warehouse/transfer" class="mega-list-item text-primary">
                            <div class="icon-wrapper text-primary"><i class="ph ph-arrows-left-right"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-primary">Stock Transfer</div>
                                <div class="desc">Move stock between depots</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('inventory')): ?>
                        <a href="<?= APP_URL ?>/inventory/reserved" class="mega-list-item text-primary">
                            <div class="icon-wrapper text-primary"><i class="ph ph-shield-check"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-primary">Reserved Stock</div>
                                <div class="desc">View all active stock holds</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/inventory/history" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-chart-bar"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Pricing History</div>
                                <div class="desc">Track cost changes</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/stockledger" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-receipt"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Stock Ledger</div>
                                <div class="desc">Inventory audit trail</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('creditnote')): ?>
                        <a href="<?= APP_URL ?>/creditnote/damaged" class="mega-list-item">
                            <div class="icon-wrapper text-warning"><i class="ph ph-warning-circle"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-warning">Damaged Log</div>
                                <div class="desc">Faulty stock reports</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Purchasing</div>
                        <?php if (hasPermission('purchase')): ?>
                        <a href="<?= APP_URL ?>/purchase" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-shopping-cart"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Purchase Orders</div>
                                <div class="desc">Send stock requests</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('grn')): ?>
                        <a href="<?= APP_URL ?>/grn" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-tray-arrow-down"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Goods Receipts (GRN)</div>
                                <div class="desc">Receive inventory</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('supplier_return')): ?>
                        <a href="<?= APP_URL ?>/supplier-return" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-arrow-counter-clockwise"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Supplier Returns</div>
                                <div class="desc">RTV processing</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('expenses')): ?>
                        <a href="<?= APP_URL ?>/expenses" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-receipt"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Expenses & AP</div>
                                <div class="desc">Payable tracking</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
 
            <!-- 3. Operations -->
            <?php 
            $showOperations = hasPermission('hrm') || hasPermission('project') || hasPermission('vehicle') || hasPermission('cheque');
            if ($showOperations):
            ?>
            <div class="glass-menu-container">
                <div class="glass-nav-item">Operations</div>
                <div class="mega-menu">
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Human Resources</div>
                        <?php if (hasPermission('hrm')): ?>
                        <a href="<?= APP_URL ?>/user" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-user-circle-gear"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Employee Directory</div>
                                <div class="desc">Staff directory & login access</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/payroll" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-bank"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Run Payroll</div>
                                <div class="desc">Process salaries & post ledger</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/leave" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-calendar-blank"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Leave Management</div>
                                <div class="desc">Requests & approval logs</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/attendance" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-fingerprint"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Attendance Tracking</div>
                                <div class="desc">Shift log registry & clock-in</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/performance" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-trend-up"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Performance Reviews</div>
                                <div class="desc">Manager feedback & rating cards</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Management</div>
                        <?php if (hasPermission('project')): ?>
                        <a href="<?= APP_URL ?>/project" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-clipboard-text"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Projects & Tasks</div>
                                <div class="desc">Team assignments</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('vehicle')): ?>
                        <a href="<?= APP_URL ?>/vehicle" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-car-profile"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Vehicle Management</div>
                                <div class="desc">Fleet maintenance</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('cheque')): ?>
                        <a href="<?= APP_URL ?>/cheque" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-signature"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Cheque Management</div>
                                <div class="desc">Track issuing</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
 
            <!-- 4. Accounting -->
            <?php 
            $showAccounting = hasPermission('accounting') || hasPermission('customerpayment') || hasPermission('supplierpayment') || hasPermission('asset');
            if ($showAccounting):
            ?>
            <div class="glass-menu-container">
                <div class="glass-nav-item">Accounting</div>
                <div class="mega-menu">
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Explore</div>
                        <div class="mega-cards-grid">
                            <?php if (hasPermission('accounting')): ?>
                            <a href="<?= APP_URL ?>/accounting/coa" class="mega-card">
                                <div class="icon"><i class="ph ph-notebook"></i></div>
                                <div class="mega-card-text">
                                    <div class="title">Chart of Accts</div>
                                    <div class="desc">General ledger base</div>
                                </div>
                            </a>
                            <a href="<?= APP_URL ?>/accounting/journal" class="mega-card">
                                <div class="icon"><i class="ph ph-pen-nib"></i></div>
                                <div class="mega-card-text">
                                    <div class="title">Journal Entries</div>
                                    <div class="desc">Manual adjustments</div>
                                </div>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Banking & Assets</div>
                        <?php if (hasPermission('accounting')): ?>
                        <a href="<?= APP_URL ?>/banking" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-bank"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Banking & Registers</div>
                                <div class="desc">Accounts and recons</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('customerpayment')): ?>
                        <a href="<?= APP_URL ?>/customerpayment" class="mega-list-item">
                            <div class="icon-wrapper text-primary"><i class="ph ph-hand-coins"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-primary">Customer Payments</div>
                                <div class="desc">AR Collections & Allocations</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('supplierpayment')): ?>
                        <a href="<?= APP_URL ?>/supplierpayment" class="mega-list-item">
                            <div class="icon-wrapper text-warning"><i class="ph ph-hand-deposit"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-warning">Supplier Payments</div>
                                <div class="desc">AP Payouts & GRN Allocations</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('asset')): ?>
                        <a href="<?= APP_URL ?>/asset" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-buildings"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Fixed Assets Register</div>
                                <div class="desc">Depreciation tracking</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
 
            <!-- 5. Analytics -->
            <?php if (hasPermission('report')): ?>
            <div class="glass-menu-container align-right">
                <div class="glass-nav-item">Analytics</div>
                <div class="mega-menu">
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Insights</div>
                        <a href="<?= APP_URL ?>/report" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-chart-line-up"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Financial Reports Hub</div>
                                <div class="desc">Statements & summaries</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/budget" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-target"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Budgets vs Actuals</div>
                                <div class="desc">Performance tracking</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
 
            <!-- 5.5 E-Commerce Operations -->
            <?php if (hasPermission('ecommerce')): ?>
            <div class="glass-menu-container align-right">
                <div class="glass-nav-item">E-Commerce</div>
                <div class="mega-menu">
                    <div class="mega-menu-col" style="min-width: 260px;">
                        <div class="mega-menu-header">Store Settings & Setup</div>
                        <a href="<?= APP_URL ?>/ecommerce" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-squares-four"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">E-Commerce Dashboard</div>
                                <div class="desc">Key sales metrics & statistics</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/settings" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-gear"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Website Settings</div>
                                <div class="desc">Store name, policies, SEO</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/homepage_builder" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-layout"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Homepage Builder</div>
                                <div class="desc">Drag-and-drop homepage sections</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/banners" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-image"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Banner Management</div>
                                <div class="desc">Desktop, mobile and popup banners</div>
                            </div>
                        </a>
                    </div>
                    <div class="mega-menu-col" style="min-width: 260px;">
                        <div class="mega-menu-header">Catalogue & Marketing</div>
                        <a href="<?= APP_URL ?>/ecommerce/products" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-package"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Product Catalogue</div>
                                <div class="desc">Publish, feature and price items</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/categories" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-tag"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Categories Structure</div>
                                <div class="desc">Parent/Child and custom icons</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/coupons" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-ticket"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Discount Coupons</div>
                                <div class="desc">Manage promotion discount codes</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/blog" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-article"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Blog & Content</div>
                                <div class="desc">Write articles & news updates</div>
                            </div>
                        </a>
                    </div>
                    <div class="mega-menu-col" style="min-width: 260px;">
                        <div class="mega-menu-header">Operations & Reporting</div>
                        <a href="<?= APP_URL ?>/ecommerce/requests" class="mega-list-item text-primary">
                            <div class="icon-wrapper text-primary"><i class="ph ph-handshake"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-primary">Wholesaler Requests</div>
                                <div class="desc">Approve and link wholesale profiles</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/retail" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-users"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Retail Directory</div>
                                <div class="desc">Manage registered retail buyers</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/reviews" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-chats-teardrop"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Customer Reviews</div>
                                <div class="desc">Moderate product ratings & reviews</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/ecommerce/reports" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-chart-line-up"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Analytics Reports</div>
                                <div class="desc">Online sales, cart conversions</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
 
            <!-- 6. Admin (Conditional) -->
            <?php 
            $showAdmin = hasPermission('settings') || hasPermission('user') || hasPermission('tax') || hasPermission('paymentterm') || hasPermission('audit');
            if ($showAdmin):
            ?>
            <div class="glass-menu-container align-right">
                <div class="glass-nav-item">Admin</div>
                <div class="mega-menu">
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Explore</div>
                        <div class="mega-cards-grid">
                            <?php if (hasPermission('settings')): ?>
                            <a href="<?= APP_URL ?>/settings" class="mega-card">
                                <div class="icon"><i class="ph ph-gear"></i></div>
                                <div class="mega-card-text">
                                    <div class="title">Settings</div>
                                    <div class="desc">Company config</div>
                                </div>
                            </a>
                            <a href="<?= APP_URL ?>/release" class="mega-card">
                                <div class="icon"><i class="ph ph-upload-simple"></i></div>
                                <div class="mega-card-text">
                                    <div class="title">App Releases</div>
                                    <div class="desc">APK updates</div>
                                </div>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Compliance</div>
                        <?php if (hasPermission('tax')): ?>
                        <a href="<?= APP_URL ?>/tax" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-scales"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Tax Rates & Rules</div>
                                <div class="desc">Manage VAT/GST</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('paymentterm')): ?>
                        <a href="<?= APP_URL ?>/paymentterm" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-handshake"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Payment Terms</div>
                                <div class="desc">Standard & Date-Driven</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('accounting')): ?>
                        <a href="<?= APP_URL ?>/accounting/close_year" class="mega-list-item">
                            <div class="icon-wrapper text-danger"><i class="ph ph-lock"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title text-danger">Close Financial Year</div>
                                <div class="desc">Lock historical data</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('audit')): ?>
                        <a href="<?= APP_URL ?>/audit" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-shield-check"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">System Audit Trail</div>
                                <div class="desc">Monitor user activity</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="mega-menu-col">
                        <div class="mega-menu-header">Maintenance</div>
                        <?php if (hasPermission('settings')): ?>
                        <a href="<?= APP_URL ?>/backup" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-database"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">Backup &amp; Restore</div>
                                <div class="desc">Database dumps &amp; states</div>
                            </div>
                        </a>
                        <a href="<?= APP_URL ?>/diagnostics" class="mega-list-item">
                            <div class="icon-wrapper"><i class="ph ph-heartbeat"></i></div>
                            <div class="mega-list-item-content">
                                <div class="title">System Health</div>
                                <div class="desc">Engine metrics &amp; logs</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- /.glass-nav-left -->

        <!-- Page Heading + Menu Button -->
        <div class="nav-page-heading">
            <span class="nav-page-dot"></span>
            <span class="nav-page-title"><?= htmlspecialchars($currentPageTitle) ?></span>
        </div>

        <button class="nav-menu-btn" onclick="openFsMenu()" title="Open Menu">
            <span></span><span></span><span></span>
        </button>

    </nav>

    <!-- FULL-SCREEN MENU OVERLAY -->
    <div class="fs-overlay" id="fsOverlay">
        <button class="fs-close" onclick="closeFsMenu()"><i class="ph ph-x"></i></button>
        <div class="fs-inner">
            <div class="fs-title">Navigation Menu</div>
            <div class="fs-sections">

                <!-- 1. Sales & CRM -->
                <?php 
                $fsSalesCRM = hasPermission('crm') || hasPermission('customer') || hasPermission('estimate') || hasPermission('sales') || hasPermission('creditnote') || hasPermission('dunning') || hasPermission('discount') || hasPermission('reptracking') || hasPermission('delivery') || hasPermission('territory');
                if ($fsSalesCRM): 
                ?>
                <div class="fs-section">
                    <div class="fs-section-label">Sales &amp; CRM</div>
                    <div class="fs-row">
                        <?php if (hasPermission('crm')): ?><a href="<?= APP_URL ?>/crm" class="fs-link"><i class="ph ph-briefcase"></i> Leads &amp; CRM</a><?php endif; ?>
                        <?php if (hasPermission('customer')): ?><a href="<?= APP_URL ?>/customer" class="fs-link"><i class="ph ph-users"></i> Customers</a><?php endif; ?>
                        <?php if (hasPermission('estimate')): ?><a href="<?= APP_URL ?>/estimate" class="fs-link"><i class="ph ph-file-text"></i> Estimates</a><?php endif; ?>
                        <?php if (hasPermission('sales')): ?>
                            <a href="<?= APP_URL ?>/sales/create" class="fs-link"><i class="ph ph-pencil-simple"></i> Billing Creator</a>
                            <a href="<?= APP_URL ?>/salesorder" class="fs-link"><i class="ph ph-list-bullets"></i> Sales Orders</a>
                            <a href="<?= APP_URL ?>/sales" class="fs-link"><i class="ph ph-credit-card"></i> Invoices &amp; AR</a>
                        <?php endif; ?>
                        <?php if (hasPermission('creditnote')): ?><a href="<?= APP_URL ?>/creditnote" class="fs-link"><i class="ph ph-money"></i> Refunds</a><?php endif; ?>
                        <?php if (hasPermission('dunning')): ?><a href="<?= APP_URL ?>/dunning" class="fs-link"><i class="ph ph-clock"></i> Dunning</a><?php endif; ?>
                        <?php if (hasPermission('discount')): ?><a href="<?= APP_URL ?>/discount" class="fs-link"><i class="ph ph-tag"></i> Discounts</a><?php endif; ?>
                        <?php if (hasPermission('reptracking')): ?><a href="<?= APP_URL ?>/RepTracking/index" class="fs-link"><i class="ph ph-map-pin"></i> Route Control</a><?php endif; ?>
                        <?php if (hasPermission('territory')): ?><a href="<?= APP_URL ?>/territory" class="fs-link"><i class="ph ph-map-trifold"></i> Territory</a><?php endif; ?>
                        <?php if (hasPermission('sales')): ?><a href="<?= APP_URL ?>/sales/deleted_list" class="fs-link text-danger"><i class="ph ph-trash"></i> Deleted Invoices</a><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 2. Supply Chain -->
                <?php 
                $fsSupplyChain = hasPermission('inventory') || hasPermission('supplier') || hasPermission('category') || hasPermission('variation') || hasPermission('warehouse') || hasPermission('purchase') || hasPermission('grn') || hasPermission('supplier_return') || hasPermission('expenses');
                if ($fsSupplyChain):
                ?>
                <div class="fs-section">
                    <div class="fs-section-label">Supply Chain</div>
                    <div class="fs-row">
                        <?php if (hasPermission('inventory')): ?><a href="<?= APP_URL ?>/inventory" class="fs-link"><i class="ph ph-package"></i> Products</a><?php endif; ?>
                        <?php if (hasPermission('supplier')): ?><a href="<?= APP_URL ?>/supplier" class="fs-link"><i class="ph ph-factory"></i> Suppliers</a><?php endif; ?>
                        <?php if (hasPermission('category')): ?><a href="<?= APP_URL ?>/category" class="fs-link"><i class="ph ph-tag"></i> Categories</a><?php endif; ?>
                        <?php if (hasPermission('variation')): ?><a href="<?= APP_URL ?>/variation" class="fs-link"><i class="ph ph-sparkle"></i> Variations</a><?php endif; ?>
                        <?php if (hasPermission('warehouse')): ?>
                            <a href="<?= APP_URL ?>/warehouse" class="fs-link"><i class="ph ph-buildings"></i> Warehouses</a>
                            <a href="<?= APP_URL ?>/warehouse/transfer" class="fs-link"><i class="ph ph-arrows-left-right"></i> Stock Transfer</a>
                        <?php endif; ?>
                        <?php if (hasPermission('inventory')): ?>
                            <a href="<?= APP_URL ?>/inventory/reserved" class="fs-link"><i class="ph ph-shield-check"></i> Reserved Stock</a>
                            <a href="<?= APP_URL ?>/inventory/history" class="fs-link"><i class="ph ph-chart-bar"></i> Pricing History</a>
                            <a href="<?= APP_URL ?>/stockledger" class="fs-link"><i class="ph ph-receipt"></i> Stock Ledger</a>
                        <?php endif; ?>
                        <?php if (hasPermission('purchase')): ?><a href="<?= APP_URL ?>/purchase" class="fs-link"><i class="ph ph-shopping-cart"></i> Purchase Orders</a><?php endif; ?>
                        <?php if (hasPermission('grn')): ?><a href="<?= APP_URL ?>/grn" class="fs-link"><i class="ph ph-tray-arrow-down"></i> GRN</a><?php endif; ?>
                        <?php if (hasPermission('supplier_return')): ?><a href="<?= APP_URL ?>/supplier-return" class="fs-link"><i class="ph ph-arrow-counter-clockwise"></i> Returns</a><?php endif; ?>
                        <?php if (hasPermission('expenses')): ?><a href="<?= APP_URL ?>/expenses" class="fs-link"><i class="ph ph-receipt"></i> Expenses &amp; AP</a><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 3. Operations -->
                <?php 
                $fsOperations = hasPermission('hrm') || hasPermission('project') || hasPermission('vehicle') || hasPermission('cheque');
                if ($fsOperations):
                ?>
                <div class="fs-section">
                    <div class="fs-section-label">Operations</div>
                    <div class="fs-row">
                        <?php if (hasPermission('hrm')): ?>
                            <a href="<?= APP_URL ?>/user" class="fs-link"><i class="ph ph-user-circle-gear"></i> HRM &amp; Employees</a>
                            <a href="<?= APP_URL ?>/payroll" class="fs-link"><i class="ph ph-bank"></i> Run Payroll</a>
                        <?php endif; ?>
                        <?php if (hasPermission('project')): ?><a href="<?= APP_URL ?>/project" class="fs-link"><i class="ph ph-clipboard-text"></i> Projects</a><?php endif; ?>
                        <?php if (hasPermission('vehicle')): ?><a href="<?= APP_URL ?>/vehicle" class="fs-link"><i class="ph ph-car-profile"></i> Vehicles</a><?php endif; ?>
                        <?php if (hasPermission('cheque')): ?><a href="<?= APP_URL ?>/cheque" class="fs-link"><i class="ph ph-signature"></i> Cheques</a><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 4. Accounting -->
                <?php 
                $fsAccounting = hasPermission('accounting') || hasPermission('customerpayment') || hasPermission('supplierpayment') || hasPermission('asset');
                if ($fsAccounting):
                ?>
                <div class="fs-section">
                    <div class="fs-section-label">Accounting</div>
                    <div class="fs-row">
                        <?php if (hasPermission('accounting')): ?>
                            <a href="<?= APP_URL ?>/accounting/coa" class="fs-link"><i class="ph ph-notebook"></i> Chart of Accts</a>
                            <a href="<?= APP_URL ?>/accounting/journal" class="fs-link"><i class="ph ph-pen-nib"></i> Journal Entries</a>
                            <a href="<?= APP_URL ?>/banking" class="fs-link"><i class="ph ph-bank"></i> Banking</a>
                        <?php endif; ?>
                        <?php if (hasPermission('customerpayment')): ?><a href="<?= APP_URL ?>/customerpayment" class="fs-link"><i class="ph ph-hand-coins"></i> Customer Payments</a><?php endif; ?>
                        <?php if (hasPermission('supplierpayment')): ?><a href="<?= APP_URL ?>/supplierpayment" class="fs-link"><i class="ph ph-hand-deposit"></i> Supplier Payments</a><?php endif; ?>
                        <?php if (hasPermission('asset')): ?><a href="<?= APP_URL ?>/asset" class="fs-link"><i class="ph ph-buildings"></i> Fixed Assets</a><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 5. Analytics -->
                <?php if (hasPermission('report')): ?>
                <div class="fs-section">
                    <div class="fs-section-label">Analytics</div>
                    <div class="fs-row">
                        <a href="<?= APP_URL ?>/report" class="fs-link"><i class="ph ph-chart-line-up"></i> Reports Hub</a>
                        <a href="<?= APP_URL ?>/budget" class="fs-link"><i class="ph ph-target"></i> Budgets</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 6. E-Commerce -->
                <?php if (hasPermission('ecommerce')): ?>
                <div class="fs-section">
                    <div class="fs-section-label">E-Commerce Management</div>
                    <div class="fs-row">
                        <a href="<?= APP_URL ?>/ecommerce" class="fs-link"><i class="ph ph-squares-four"></i> Dashboard</a>
                        <a href="<?= APP_URL ?>/ecommerce/settings" class="fs-link"><i class="ph ph-gear"></i> Settings</a>
                        <a href="<?= APP_URL ?>/ecommerce/homepage_builder" class="fs-link"><i class="ph ph-layout"></i> Homepage Builder</a>
                        <a href="<?= APP_URL ?>/ecommerce/banners" class="fs-link"><i class="ph ph-image"></i> Banners</a>
                        <a href="<?= APP_URL ?>/ecommerce/products" class="fs-link"><i class="ph ph-package"></i> Products</a>
                        <a href="<?= APP_URL ?>/ecommerce/categories" class="fs-link"><i class="ph ph-tag"></i> Categories</a>
                        <a href="<?= APP_URL ?>/ecommerce/coupons" class="fs-link"><i class="ph ph-ticket"></i> Coupons</a>
                        <a href="<?= APP_URL ?>/ecommerce/blog" class="fs-link"><i class="ph ph-article"></i> Blog &amp; News</a>
                        <a href="<?= APP_URL ?>/ecommerce/requests" class="fs-link"><i class="ph ph-handshake"></i> Wholesaler Requests</a>
                        <a href="<?= APP_URL ?>/ecommerce/retail" class="fs-link"><i class="ph ph-users"></i> Retail Customers</a>
                        <a href="<?= APP_URL ?>/ecommerce/reviews" class="fs-link"><i class="ph ph-chats-teardrop"></i> Reviews</a>
                        <a href="<?= APP_URL ?>/ecommerce/reports" class="fs-link"><i class="ph ph-chart-line-up"></i> Reports</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 7. Admin -->
                <?php 
                $fsAdmin = hasPermission('settings') || hasPermission('user') || hasPermission('tax') || hasPermission('paymentterm') || hasPermission('audit');
                if ($fsAdmin):
                ?>
                <div class="fs-section">
                    <div class="fs-section-label">Admin &amp; Settings</div>
                    <div class="fs-row">
                        <?php if (hasPermission('settings')): ?>
                            <a href="<?= APP_URL ?>/settings" class="fs-link"><i class="ph ph-gear"></i> Settings</a>
                            <a href="<?= APP_URL ?>/settings/releases" class="fs-link"><i class="ph ph-cloud-arrow-down"></i> Releases</a>
                            <a href="<?= APP_URL ?>/backup" class="fs-link"><i class="ph ph-database"></i> Backup &amp; Restore</a>
                            <a href="<?= APP_URL ?>/diagnostics" class="fs-link"><i class="ph ph-heartbeat"></i> System Health</a>
                        <?php endif; ?>
                        <?php if (hasPermission('user')): ?><a href="<?= APP_URL ?>/user" class="fs-link"><i class="ph ph-lock-key"></i> Users &amp; Roles</a><?php endif; ?>
                        <?php if (hasPermission('tax')): ?><a href="<?= APP_URL ?>/tax" class="fs-link"><i class="ph ph-scales"></i> Tax Rates</a><?php endif; ?>
                        <?php if (hasPermission('paymentterm')): ?><a href="<?= APP_URL ?>/paymentterm" class="fs-link"><i class="ph ph-handshake"></i> Payment Terms</a><?php endif; ?>
                        <?php if (hasPermission('accounting')): ?><a href="<?= APP_URL ?>/accounting/close_year" class="fs-link text-danger"><i class="ph ph-lock"></i> Close Year</a><?php endif; ?>
                        <?php if (hasPermission('audit')): ?><a href="<?= APP_URL ?>/audit" class="fs-link"><i class="ph ph-shield-check"></i> Audit Trail</a><?php endif; ?>
                        <a href="<?= APP_URL ?>/auth/logout" class="fs-link" style="color:rgba(255,120,120,0.85);"><i class="ph ph-sign-out"></i> Logout</a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <script>
    function openFsMenu() {
        const o = document.getElementById('fsOverlay');
        o.classList.add('open');
        requestAnimationFrame(() => o.classList.add('visible'));
        document.body.style.overflow = 'hidden';
    }
    function closeFsMenu() {
        const o = document.getElementById('fsOverlay');
        o.classList.remove('visible');
        setTimeout(() => { o.classList.remove('open'); document.body.style.overflow = ''; }, 300);
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFsMenu(); });
    
    // Force top menu bar, navigation menu, and quick access buttons to open in a new tab
    document.addEventListener("DOMContentLoaded", function() {
        const selectors = [
            '.glass-nav-left a',
            '.fs-overlay a',
            '.d-quick-grid a'
        ];
        selectors.forEach(sel => {
            document.querySelectorAll(sel).forEach(a => {
                const href = a.getAttribute('href');
                if (href && !href.startsWith('javascript:') && !href.includes('logout')) {
                    a.setAttribute('target', '_blank');
                }
            });
        });
    });
    </script>

    <div class="app-container">
        <main class="main-content">
            <?php 
            if (isset($data['content_view'])) {
                require_once '../app/Views/' . $data['content_view'] . '.php';
            } else {
                echo "<p>View not found.</p>";
            }
            ?>
        </main>
    </div>

    <!-- GLASSMORPHISM RECENT BAR -->
    <div id="recentBar" class="mac-recent-bar">
        <div class="recent-toggle-tab" onclick="toggleRecentBar()">
            <span id="recentToggleIcon"><i class="ph ph-clock-counter-clockwise"></i></span>
            Recent
        </div>
        <div class="recent-links-container" id="recentLinksContainer">
            <!-- JavaScript will populate this dynamically -->
        </div>
        <div class="recent-clock">
            <i class="ph ph-clock" style="font-size: 13px;"></i>
            <span id="clock"></span>
        </div>
    </div>

    <script>
        // --- Session Expiration and Inactivity Monitoring ---
        if (typeof window.sessionExpiredHandled === 'undefined') {
            window.sessionExpiredHandled = false;
        }
        function handleSessionExpiration() {
            if (window.sessionExpiredHandled) return;
            window.sessionExpiredHandled = true;

            // Save the current location (path + query parameters)
            const currentUrl = window.location.pathname + window.location.search;
            sessionStorage.setItem('redirect_url', currentUrl);

            // Clear local session variables
            sessionStorage.removeItem('curtiss_history');
            sessionStorage.removeItem('report_breadcrumbs');

            alert('Session Expired\n\nPlease login again.');
            window.location.href = '<?= APP_URL ?>/auth/login';
        }

        // Global AJAX / API Interceptors
        // 1. Intercept Fetch API
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args).then(response => {
                if (response.status === 401 || response.status === 419 || response.status === 403) {
                    handleSessionExpiration();
                }
                return response;
            });
        };

        // 2. Intercept XMLHttpRequest (AJAX)
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._url = url;
            return originalOpen.apply(this, [method, url, ...args]);
        };
        XMLHttpRequest.prototype.send = function(...args) {
            const onreadystatechange = this.onreadystatechange;
            this.onreadystatechange = function() {
                if (this.readyState === 4) {
                    if (this.status === 401 || this.status === 419 || this.status === 403) {
                        handleSessionExpiration();
                    }
                }
                if (onreadystatechange) {
                    return onreadystatechange.apply(this, arguments);
                }
            };
            return originalSend.apply(this, args);
        };

        // 3. Inactivity Timeout (60 Minutes - Shared across all tabs via localStorage)
        const TIMEOUT_DURATION = 60 * 60 * 1000; // 60 minutes
        
        if (!localStorage.getItem('lastActiveTime')) {
            localStorage.setItem('lastActiveTime', Date.now().toString());
        }

        function resetInactivityTimer() {
            if (window.sessionExpiredHandled) return;
            localStorage.setItem('lastActiveTime', Date.now().toString());
        }

        function checkInactivityTimeout() {
            if (window.sessionExpiredHandled) return;
            const lastActive = parseInt(localStorage.getItem('lastActiveTime') || '0');
            if (Date.now() - lastActive > TIMEOUT_DURATION) {
                window.sessionExpiredHandled = true;
                originalFetch('<?= APP_URL ?>/auth/timeout_logout')
                    .finally(() => {
                        window.sessionExpiredHandled = false;
                        handleSessionExpiration();
                    });
            }
        }

        // Run check every 5 seconds
        setInterval(checkInactivityTimeout, 5000);

        window.addEventListener('load', resetInactivityTimer);
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keypress', resetInactivityTimer);
        document.addEventListener('click', resetInactivityTimer);
        document.addEventListener('scroll', resetInactivityTimer);
        document.addEventListener('touchstart', resetInactivityTimer);

        // 4. Browser Multi-Tab Check (Poller runs every 10 seconds)
        setInterval(() => {
            if (window.sessionExpiredHandled) return;
            originalFetch('<?= APP_URL ?>/auth/check_session')
                .then(res => res.json())
                .then(data => {
                    if (!data.logged_in) {
                        handleSessionExpiration();
                    }
                })
                .catch(err => {
                    // Ignore transient network errors
                });
        }, 10000);

        // Clear redirect_url if we are currently on the page that was successfully loaded
        const loadCheckUrl = window.location.pathname + window.location.search;
        if (sessionStorage.getItem('redirect_url') === loadCheckUrl) {
            sessionStorage.removeItem('redirect_url');
        }

        // Clock Logic
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', hour: 'numeric', minute: '2-digit' };
            document.getElementById('clock').innerText = now.toLocaleDateString('en-US', options).replace(',', '');
        }
        setInterval(updateClock, 1000);
        updateClock();

        // --- Recent History Tracker Logic ---
        document.addEventListener("DOMContentLoaded", function() {
            const currentUrl = window.location.href;
            const currentTitle = "<?= addslashes($data['title'] ?? 'Dashboard') ?>";
            
            // Exclude the login/logout pages from history
            if(currentUrl.includes('/auth/')) return;

            // Fetch existing history from Session Storage
            let history = JSON.parse(sessionStorage.getItem('curtiss_history')) || [];
            
            // Remove current URL if it already exists in the array
            history = history.filter(item => item.url !== currentUrl);
            
            // Add the current page to the absolute front of the array
            history.unshift({ url: currentUrl, title: currentTitle });
            
            // Keep a maximum of 8 recent pages
            if(history.length > 8) history.pop();
            
            // Save it back to session storage
            sessionStorage.setItem('curtiss_history', JSON.stringify(history));

            // Render the pills to the HTML bar (skipping index 0)
            const container = document.getElementById('recentLinksContainer');
            if (history.length <= 1) {
                container.innerHTML = '<span style="font-size:12px; color:var(--text-muted);">No recent pages yet.</span>';
            } else {
                for(let i = 1; i < history.length; i++) {
                    let a = document.createElement('a');
                    a.href = history[i].url;
                    a.className = 'recent-link';
                    a.innerHTML = `<i class="ph ph-file-text" style="font-size: 13px;"></i> ${history[i].title}`;
                    container.appendChild(a);
                }
            }

            // Restore the Collapse state from local storage
            const bar = document.getElementById('recentBar');
            const icon = document.getElementById('recentToggleIcon');
            let isCollapsed = localStorage.getItem('curtiss_recent_collapsed') === 'true';
            
            if(isCollapsed) {
                bar.classList.add('collapsed');
                icon.innerHTML = '<i class="ph ph-clock"></i>';
            }
        });

        function toggleRecentBar() {
            const bar = document.getElementById('recentBar');
            const icon = document.getElementById('recentToggleIcon');
            
            bar.classList.toggle('collapsed');
            const collapsedNow = bar.classList.contains('collapsed');
            
            // Save preference to local storage
            localStorage.setItem('curtiss_recent_collapsed', collapsedNow);
            icon.innerHTML = collapsedNow ? '<i class="ph ph-clock"></i>' : '<i class="ph ph-clock-counter-clockwise"></i>';
        }
    </script>

    <!-- COMMAND PALETTE MODAL -->
    <div id="cmdPaletteModal" class="cmd-palette-overlay">
        <div class="cmd-palette-container">
            <div class="cmd-palette-header">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="cmdPaletteInput" class="cmd-palette-input" placeholder="Search customers, invoices, products or modules (e.g. @customer, @invoice)..." autocomplete="off">
            </div>
            <div class="cmd-palette-content">
                <div class="cmd-palette-results" id="cmdPaletteResults">
                    <div class="cmd-empty-state">
                        <i class="ph ph-magnifying-glass"></i>
                        <span>Type something to search Curtiss ERP</span>
                    </div>
                </div>
                <div class="cmd-palette-sidebar">
                    <div class="cmd-palette-sidebar-title">Search Tags</div>
                    <div class="cmd-palette-tags">
                        <div class="cmd-tag-item" onclick="insertCmdTag('@customer')">
                            <span>Customers</span>
                            <span class="cmd-tag-item-code">@customer</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@supplier')">
                            <span>Suppliers</span>
                            <span class="cmd-tag-item-code">@supplier</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@product')">
                            <span>Products</span>
                            <span class="cmd-tag-item-code">@product</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@invoice')">
                            <span>Invoices</span>
                            <span class="cmd-tag-item-code">@invoice</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@sales-order')">
                            <span>Sales Orders</span>
                            <span class="cmd-tag-item-code">@sales-order</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@estimate')">
                            <span>Estimates</span>
                            <span class="cmd-tag-item-code">@estimate</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@payment')">
                            <span>Payments</span>
                            <span class="cmd-tag-item-code">@payment</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@grn')">
                            <span>GRN</span>
                            <span class="cmd-tag-item-code">@grn</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@po')">
                            <span>Purchase Orders</span>
                            <span class="cmd-tag-item-code">@po</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@route')">
                            <span>Routes</span>
                            <span class="cmd-tag-item-code">@route</span>
                        </div>
                        <div class="cmd-tag-item" onclick="insertCmdTag('@report')">
                            <span>Reports</span>
                            <span class="cmd-tag-item-code">@report</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cmd-palette-footer">
                <div class="cmd-kbd-guide">
                    <span class="cmd-kbd">↑↓</span> navigate &nbsp;
                    <span class="cmd-kbd">Enter</span> open &nbsp;
                    <span class="cmd-kbd">Esc</span> close
                </div>
                <div>Press <span class="cmd-kbd">/</span> or <span class="cmd-kbd">Ctrl+K</span> to search from anywhere</div>
            </div>
        </div>
    </div>

    <script>
        // --- Command Palette Engine ---
        const cmdModal = document.getElementById('cmdPaletteModal');
        const cmdInput = document.getElementById('cmdPaletteInput');
        const cmdResults = document.getElementById('cmdPaletteResults');
        let cmdSelectedIdx = -1;
        let cmdSearchTimeout = null;

        function openCmdPalette(initialVal = '') {
            if (!cmdModal) return;
            cmdModal.classList.add('open');
            // Force redraw/reflow for CSS transition
            cmdModal.offsetHeight;
            cmdModal.classList.add('visible');
            document.body.style.overflow = 'hidden';
            cmdInput.value = initialVal;
            cmdInput.focus();
            if (initialVal) {
                performCmdSearch(initialVal);
            } else {
                renderCmdDefaultState();
            }
        }

        function closeCmdPalette() {
            if (!cmdModal) return;
            cmdModal.classList.remove('visible');
            document.body.style.overflow = '';
            setTimeout(() => {
                cmdModal.classList.remove('open');
            }, 250);
        }

        function insertCmdTag(tag) {
            cmdInput.value = tag + ' ';
            cmdInput.focus();
            performCmdSearch(cmdInput.value);
        }

        function renderCmdDefaultState() {
            cmdResults.innerHTML = `
                <div class="cmd-empty-state">
                    <i class="ph ph-magnifying-glass"></i>
                    <span>Type something to search Curtiss ERP</span>
                </div>
            `;
            cmdSelectedIdx = -1;
        }

        function performCmdSearch(query) {
            query = query.trim();
            if (!query) {
                renderCmdDefaultState();
                return;
            }
            cmdResults.innerHTML = `
                <div class="cmd-empty-state">
                    <i class="ph ph-spinner" style="animation: spin 1s linear infinite;"></i>
                    <span>Searching...</span>
                </div>
            `;
            
            // Add spin animation dynamically if not present in style
            if (!document.getElementById('cmdSpinAnim')) {
                const s = document.createElement('style');
                s.id = 'cmdSpinAnim';
                s.innerHTML = '@keyframes spin { 100% { transform: rotate(360deg); } }';
                document.head.appendChild(s);
            }

            clearTimeout(cmdSearchTimeout);
            cmdSearchTimeout = setTimeout(() => {
                fetch('<?= APP_URL ?>/dashboard/search?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.length === 0) {
                            cmdResults.innerHTML = `
                                <div class="cmd-empty-state">
                                    <i class="ph ph-warning-circle"></i>
                                    <span>No results found for "${escapeHtml(query)}"</span>
                                </div>
                            `;
                            cmdSelectedIdx = -1;
                            return;
                        }
                        
                        let html = '';
                        data.forEach((item, index) => {
                            let icon = 'ph-file-text';
                            if (item.tag === 'customer') icon = 'ph-users';
                            else if (item.tag === 'supplier') icon = 'ph-factory';
                            else if (item.tag === 'product' || item.tag === 'stock') icon = 'ph-package';
                            else if (item.tag === 'invoice') icon = 'ph-credit-card';
                            else if (item.tag === 'sales-order') icon = 'ph-list-bullets';
                            else if (item.tag === 'estimate' || item.tag === 'quotation') icon = 'ph-file-text';
                            else if (item.tag === 'payment' || item.tag === 'collection') icon = 'ph-hand-coins';
                            else if (item.tag === 'grn') icon = 'ph-tray-arrow-down';
                            else if (item.tag === 'po') icon = 'ph-shopping-cart';
                            else if (item.tag === 'route') icon = 'ph-map-pin';
                            else if (item.tag === 'report') icon = 'ph-chart-line-up';
                            else if (item.tag === 'module') icon = 'ph-squares-four';
                            
                            html += `
                                <div class="cmd-result-item" data-index="${index}" onclick="handleCmdResultClick('${escapeUrl(item.url)}')">
                                    <div class="cmd-result-icon">
                                        <i class="ph ${icon}"></i>
                                    </div>
                                    <div class="cmd-result-details">
                                        <div class="cmd-result-title">${escapeHtml(item.title)}</div>
                                        <div class="cmd-result-subtitle">${escapeHtml(item.subtitle)}</div>
                                    </div>
                                    <div class="cmd-result-tag">${escapeHtml(item.tag)}</div>
                                </div>
                            `;
                        });
                        cmdResults.innerHTML = html;
                        cmdSelectedIdx = 0;
                        updateCmdHighlight();
                    })
                    .catch(err => {
                        cmdResults.innerHTML = `
                            <div class="cmd-empty-state">
                                <i class="ph ph-x-circle" style="color:var(--text-danger);"></i>
                                <span>Failed to fetch results.</span>
                            </div>
                        `;
                        cmdSelectedIdx = -1;
                    });
            }, 200);
        }

        function updateCmdHighlight() {
            const items = cmdResults.querySelectorAll('.cmd-result-item');
            items.forEach((item, idx) => {
                if (idx === cmdSelectedIdx) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('selected');
                }
            });
        }

        function handleCmdResultClick(url) {
            closeCmdPalette();
            window.location.href = url;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
        
        function escapeUrl(str) {
            if (!str) return '#';
            return str.replace(/"/g, '&quot;');
        }

        // Global Keydown Listeners for Command Palette
        document.addEventListener('keydown', e => {
            // Ctrl+K or / to open (when not inside inputs)
            const activeEl = document.activeElement;
            const isInput = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.contentEditable === 'true');
            
            if ((e.ctrlKey && e.key.toLowerCase() === 'k') || (e.key === '/' && !isInput)) {
                e.preventDefault();
                openCmdPalette();
            }
            
            if (cmdModal && cmdModal.classList.contains('visible')) {
                if (e.key === 'Escape') {
                    closeCmdPalette();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const items = cmdResults.querySelectorAll('.cmd-result-item');
                    if (items.length > 0) {
                        cmdSelectedIdx = (cmdSelectedIdx + 1) % items.length;
                        updateCmdHighlight();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const items = cmdResults.querySelectorAll('.cmd-result-item');
                    if (items.length > 0) {
                        cmdSelectedIdx = (cmdSelectedIdx - 1 + items.length) % items.length;
                        updateCmdHighlight();
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const selected = cmdResults.querySelector('.cmd-result-item.selected');
                    if (selected) {
                        selected.click();
                    }
                }
            }
        });

        if (cmdInput) {
            cmdInput.addEventListener('input', e => {
                performCmdSearch(e.target.value);
            });
        }

        if (cmdModal) {
            cmdModal.addEventListener('click', e => {
                if (e.target === cmdModal) {
                    closeCmdPalette();
                }
            });
        }
        
        // Connect Dashboard Search if we are on Dashboard
        document.addEventListener('DOMContentLoaded', () => {
            const dashSearch = document.getElementById('dashSearch');
            if (dashSearch) {
                // Intercept input on dashboard search to open command palette
                dashSearch.addEventListener('focus', e => {
                    dashSearch.blur();
                    openCmdPalette();
                });
            }
        });
    </script>
    
    <!-- Automatic CSRF Injections for forms and AJAX requests -->
    <script>
        (function() {
            const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
            if (!csrfToken) return;

            // 1. Function to inject into forms
            function injectCsrf(container) {
                const forms = (container || document).querySelectorAll('form[method="POST"], form[method="post"]');
                forms.forEach(form => {
                    if (!form.querySelector('input[name="csrf_token"]')) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'csrf_token';
                        input.value = csrfToken;
                        form.appendChild(input);
                    }
                });
            }

            // Run on initial load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => injectCsrf());
            } else {
                injectCsrf();
            }

            // 2. Observe DOM mutations to inject CSRF into dynamically loaded/created forms
            const observer = new MutationObserver((mutations) => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.tagName === 'FORM' && (node.getAttribute('method') || '').toUpperCase() === 'POST') {
                                if (!node.querySelector('input[name="csrf_token"]')) {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'csrf_token';
                                    input.value = csrfToken;
                                    node.appendChild(input);
                                }
                            } else {
                                injectCsrf(node);
                            }
                        }
                    });
                });
            });
            observer.observe(document.documentElement, { childList: true, subtree: true });

            // 3. Setup jQuery AJAX global header if jQuery is loaded
            if (window.jQuery) {
                window.jQuery.ajaxSetup({
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
            } else {
                // Wait for jQuery just in case it loads later
                document.addEventListener('DOMContentLoaded', () => {
                    if (window.jQuery) {
                        window.jQuery.ajaxSetup({
                            headers: { 'X-CSRF-TOKEN': csrfToken }
                        });
                    }
                });
            }

            // 4. Intercept standard fetch requests
            const originalFetch = window.fetch;
            window.fetch = function(input, init) {
                init = init || {};
                const method = init.method ? init.method.toUpperCase() : 'GET';
                if (method === 'POST') {
                    init.headers = init.headers || {};
                    if (init.headers instanceof Headers) {
                        init.headers.set('X-CSRF-TOKEN', csrfToken);
                    } else if (Array.isArray(init.headers)) {
                        let exists = false;
                        for (let i = 0; i < init.headers.length; i++) {
                            if (init.headers[i][0].toUpperCase() === 'X-CSRF-TOKEN') {
                                exists = true;
                                break;
                            }
                        }
                        if (!exists) init.headers.push(['X-CSRF-TOKEN', csrfToken]);
                    } else {
                        init.headers['X-CSRF-TOKEN'] = csrfToken;
                    }
                }
                return originalFetch.call(this, input, init);
            };

            // 5. Intercept XMLHttpRequests (like native AJAX, Axios, etc.)
            const originalOpen = XMLHttpRequest.prototype.open;
            const originalSend = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
                this._method = method;
                return originalOpen.apply(this, arguments);
            };
            XMLHttpRequest.prototype.send = function(body) {
                if ((this._method || '').toUpperCase() === 'POST') {
                    this.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                }
                return originalSend.apply(this, arguments);
            };
        })();
    </script>
    
    <?php include '../app/Views/layouts/resilient_loader.php'; ?>
</body>
</html>