<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? 'Curtiss Stationery') ?></title>
    
    <!-- SEO Metadata -->
    <meta name="description" content="<?= htmlspecialchars($data['settings']['seo_meta_desc'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($data['settings']['seo_keywords'] ?? '') ?>">
    
    <!-- Outfit Google Font & Phosphor Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Global CSS Framework Variables and Tokens -->
    <style>
        :root {
            --bg-site: #f4f7fa;
            --card-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(0, 0, 0, 0.08);
            --card-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.04);
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --text-accent: #0076ff;
            --text-accent-hover: #0056cc;
            --mega-bg: rgba(255, 255, 255, 0.95);
            --mega-divider: rgba(0, 0, 0, 0.05);
            --glass-blur: 12px;
            --rounded: 16px;
        }

        [data-theme="dark"] {
            --bg-site: #0d0f12;
            --card-bg: rgba(30, 35, 45, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
            --card-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --text-accent: #0a84ff;
            --text-accent-hover: #0066cc;
            --mega-bg: rgba(22, 28, 38, 0.95);
            --mega-divider: rgba(255, 255, 255, 0.05);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-site);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Premium Navbar Glassmorphism styling */
        header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--card-bg);
            backdrop-filter: blur(var(--glass-blur));
            border-bottom: 1px solid var(--card-border);
            box-shadow: var(--card-shadow);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand-logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-accent);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .nav-links a {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.2s;
        }
        .nav-links a:hover, .nav-links a.active {
            color: var(--text-accent);
        }

        .role-badge {
            font-size: 11px;
            font-weight: 700;
            background: rgba(0, 118, 255, 0.1);
            color: var(--text-accent);
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-transform: uppercase;
        }
        .role-retail {
            background: rgba(175, 82, 222, 0.1);
            color: #af52de;
        }

        /* Cart link and count wrapper */
        .cart-trigger {
            background: rgba(0, 118, 255, 0.08);
            color: var(--text-accent);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14.5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.2s;
        }
        .cart-trigger:hover {
            transform: scale(1.03);
        }
        .cart-counter {
            background: #ff3b30;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            border-radius: 10px;
            padding: 2px 6px;
        }

        .theme-toggle {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            padding: 5px;
            transition: transform 0.2s;
        }
        .theme-toggle:hover {
            transform: rotate(15deg);
        }

        /* Utility Card & Page grids styling */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 25px 20px;
            flex-grow: 1;
            width: 100%;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--rounded);
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(var(--glass-blur));
            padding: 24px;
            box-sizing: border-box;
            transition: transform 0.2s;
        }

        /* Form elements */
        .form-box {
            margin-bottom: 18px;
        }
        .form-box label {
            display: block;
            margin-bottom: 6px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            background: rgba(0,0,0,0.01);
            color: var(--text-main);
            box-sizing: border-box;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        @media (prefers-color-scheme: dark) {
            .form-control { background: rgba(255,255,255,0.02); }
        }
        .form-control:focus {
            outline: none;
            border-color: var(--text-accent);
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-primary {
            background: var(--text-accent);
            color: #fff;
            border: none;
            padding: 11px 24px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-primary:hover {
            background: var(--text-accent-hover);
        }
        .btn-primary:active {
            transform: scale(0.98);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 11px 24px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-secondary:hover {
            background: rgba(0,0,0,0.03);
        }
        @media (prefers-color-scheme: dark) {
            .btn-secondary:hover { background: rgba(255,255,255,0.05); }
        }

        .btn-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }

        /* Pill Badge badges */
        .pill-badge {
            display: inline-block;
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .pill-success { background: rgba(52,199,89,0.12); color: #34c759; }
        .pill-warning { background: rgba(255,149,0,0.12); color: #ff9500; }
        .pill-danger { background: rgba(255,59,48,0.12); color: #ff3b30; }

        /* Alert notifications */
        .alert-box {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        /* Footer styling */
        footer {
            margin-top: auto;
            background: var(--card-bg);
            border-top: 1px solid var(--card-border);
            padding: 30px 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar navigation -->
    <header>
        <div class="nav-container">
            <a href="<?= APP_URL ?>/shop" class="brand-logo">
                <i class="ph ph-shopping-bag"></i> 
                <?= htmlspecialchars($data['settings']['store_name'] ?? 'CURTISS STORE') ?>
            </a>

            <div class="nav-links">
                <a href="<?= APP_URL ?>/shop">Home</a>
                <a href="<?= APP_URL ?>/shop/category">Browse Shop</a>
                <a href="<?= APP_URL ?>/shop/blog">Blog</a>

                <?php if (isset($_SESSION['ec_user_id'])): ?>
                    <a href="<?= APP_URL ?>/portal">Account Dashboard</a>
                    
                    <?php if ($_SESSION['ec_role'] === 'wholesaler'): ?>
                        <span class="role-badge"><i class="ph ph-briefcase"></i> Wholesaler Account</span>
                    <?php else: ?>
                        <span class="role-badge role-retail"><i class="ph ph-user"></i> Retail Account</span>
                    <?php endif; ?>

                    <span style="font-weight:600; font-size:13.5px;"><i class="ph ph-user-circle"></i> <?= htmlspecialchars($_SESSION['ec_name']) ?></span>
                    <a href="<?= APP_URL ?>/shop/logout" style="color: #ff3b30; font-weight:600;"><i class="ph ph-sign-out"></i> Log Out</a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/shop/login"><i class="ph ph-sign-in"></i> Sign In / Register</a>
                <?php endif; ?>

                <!-- Cart trigger -->
                <a href="<?= APP_URL ?>/shop/cart" class="cart-trigger">
                    <i class="ph ph-shopping-cart"></i>
                    <span>Cart</span>
                    <span class="cart-counter"><?= count($_SESSION['ec_cart'] ?? []) ?></span>
                </a>

                <!-- Theme switch button -->
                <button type="button" class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark/Light View">
                    <i class="ph ph-sun" id="theme-icon"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Injection -->
    <main class="container">
        <!-- Injected Sub-View Content -->
        <?php $this->view($data['content_view'], $data); ?>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($data['settings']['store_name'] ?? 'Curtiss Stationery') ?>. All Rights Reserved. Powered by Curtiss ERP.</p>
        <div style="margin-top: 8px; font-size: 11px;">
            <a href="<?= APP_URL ?>/shop/category" style="color:var(--text-muted); text-decoration:none; margin:0 8px;">Shop</a> |
            <a href="<?= APP_URL ?>/shop/blog" style="color:var(--text-muted); text-decoration:none; margin:0 8px;">Blog</a> |
            <a href="#" style="color:var(--text-muted); text-decoration:none; margin:0 8px;">Privacy Policy</a> |
            <a href="#" style="color:var(--text-muted); text-decoration:none; margin:0 8px;">Terms of Service</a>
        </div>
    </footer>

    <!-- Google Analytics Tracker Injected from DB Settings -->
    <?php if(!empty($data['settings']['google_analytics'])): ?>
        <?= $data['settings']['google_analytics'] ?>
    <?php endif; ?>

    <script>
        function toggleTheme() {
            const body = document.documentElement;
            const icon = document.getElementById('theme-icon');
            const currentTheme = body.getAttribute('data-theme');
            
            if (currentTheme === 'light') {
                body.setAttribute('data-theme', 'dark');
                icon.className = 'ph ph-moon';
                localStorage.setItem('ec-theme', 'dark');
            } else {
                body.setAttribute('data-theme', 'light');
                icon.className = 'ph ph-sun';
                localStorage.setItem('ec-theme', 'light');
            }
        }

        // Initialize theme from storage
        const savedTheme = localStorage.getItem('ec-theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        document.getElementById('theme-icon').className = (savedTheme === 'dark') ? 'ph ph-moon' : 'ph ph-sun';
    </script>
</body>
</html>
