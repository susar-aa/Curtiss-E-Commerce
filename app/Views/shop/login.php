<style>
    .login-container-card {
        max-width: 600px;
        margin: 40px auto;
        padding: 30px;
    }
    
    .tab-nav {
        display: flex;
        border-bottom: 2px solid var(--card-border);
        margin-bottom: 25px;
        gap: 15px;
    }
    .tab-nav-btn {
        background: none;
        border: none;
        padding: 10px 5px;
        font-family: inherit;
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
    }
    .tab-nav-btn.active {
        color: var(--text-accent);
        border-bottom-color: var(--text-accent);
    }

    .form-panel {
        display: none;
    }
    .form-panel.active {
        display: block;
    }
</style>

<?php if(!empty($data['error'])): ?>
    <div class="alert-box pill-danger" style="background: rgba(255,59,48,0.1); color: #ff3b30; max-width: 600px; margin: 0 auto 20px auto;">
        <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

<?php if(!empty($data['success'])): ?>
    <div class="alert-box pill-success" style="background: rgba(52,199,89,0.1); color: #34c759; max-width: 600px; margin: 0 auto 20px auto;">
        <i class="ph ph-check-circle"></i> <?= htmlspecialchars($data['success']) ?>
    </div>
<?php endif; ?>

<div class="card login-container-card">
    <div class="tab-nav">
        <button type="button" class="tab-nav-btn active" onclick="switchTab('signin')">Sign In</button>
        <button type="button" class="tab-nav-btn" onclick="switchTab('register')">Register Retail</button>
        <button type="button" class="tab-nav-btn" onclick="switchTab('wholesaler')">Request Wholesale B2B</button>
    </div>

    <!-- 1. Form: Sign In -->
    <div class="form-panel active" id="panel-signin">
        <h3 style="font-size: 18px; font-weight:700; margin-bottom: 20px; color:var(--text-main);">Access Your Account</h3>
        <form action="<?= APP_URL ?>/shop/login" method="POST">
            <input type="hidden" name="action" value="login">

            <div class="form-box">
                <label>Username or Email Address *</label>
                <input type="text" name="username_or_email" class="form-control" required placeholder="e.g. johndoe">
            </div>

            <div class="form-box">
                <label>Password *</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; height:42px; margin-top: 10px;">
                <i class="ph ph-sign-in"></i> Sign In to Account
            </button>
        </form>
    </div>

    <!-- 2. Form: Register Retail -->
    <div class="form-panel" id="panel-register">
        <h3 style="font-size: 18px; font-weight:700; margin-bottom: 20px; color:var(--text-main);">Create a Retail Account</h3>
        <form action="<?= APP_URL ?>/shop/login" method="POST">
            <input type="hidden" name="action" value="register_retail">

            <div class="form-box">
                <label>Full Name *</label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe">
            </div>

            <div class="form-box">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control" required placeholder="e.g. john@example.com">
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Choose Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="e.g. johndoe">
                </div>
                <div class="form-box">
                    <label>Choose Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Mobile Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g. +94 77 123 4567">
                </div>
                <div class="form-box">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" placeholder="e.g. Colombo">
                </div>
            </div>

            <div class="form-box">
                <label>Delivery Address</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Street number, town address..."></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; height:42px; margin-top: 10px;">
                <i class="ph ph-user-plus"></i> Create Account
            </button>
        </form>
    </div>

    <!-- 3. Form: Wholesaler Application -->
    <div class="form-panel" id="panel-wholesaler">
        <h3 style="font-size: 18px; font-weight:700; margin-bottom: 10px; color:var(--text-main);">Request B2B Wholesaler Partnership</h3>
        <p style="font-size:12.5px; color:var(--text-muted); margin-bottom: 20px;">Onboard your organization. Once details are audited, our admin team will activate custom pricing tiers for your login username.</p>
        
        <form action="<?= APP_URL ?>/shop/login" method="POST">
            <input type="hidden" name="action" value="submit_wholesaler_request">

            <div class="form-box">
                <label>Business Entity Name *</label>
                <input type="text" name="business_name" class="form-control" required placeholder="e.g. Candent Paper PLC">
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Contact Phone Number *</label>
                    <input type="text" name="contact_number" class="form-control" required placeholder="e.g. +94 11 123 4567">
                </div>
                <div class="form-box">
                    <label>Business Email Address *</label>
                    <input type="email" name="email_address" class="form-control" required placeholder="e.g. procurement@candent.lk">
                </div>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Requested Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="e.g. candent_b2b">
                </div>
                <div class="form-box">
                    <label>Requested Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
            </div>

            <div class="form-box">
                <label>Business Headquarters City *</label>
                <input type="text" name="city" class="form-control" required placeholder="e.g. Colombo">
            </div>

            <div class="form-box">
                <label>Official Business Address *</label>
                <textarea name="address" class="form-control" rows="2" required placeholder="Street address, district..."></textarea>
            </div>

            <div class="form-box">
                <label>Verification notes / Special Instructions</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Business registration document numbers, BRN-12345..."></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; height:42px; margin-top: 10px;">
                <i class="ph ph-briefcase"></i> Submit Partnership Request
            </button>
        </form>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-nav-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.form-panel').forEach(panel => panel.classList.remove('active'));

        if (tabId === 'signin') {
            document.querySelector('.tab-nav-btn:nth-child(1)').classList.add('active');
            document.getElementById('panel-signin').classList.add('active');
        } else if (tabId === 'register') {
            document.querySelector('.tab-nav-btn:nth-child(2)').classList.add('active');
            document.getElementById('panel-register').classList.add('active');
        } else if (tabId === 'wholesaler') {
            document.querySelector('.tab-nav-btn:nth-child(3)').classList.add('active');
            document.getElementById('panel-wholesaler').classList.add('active');
        }
    }
</script>
