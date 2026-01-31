<?php
/*
 *  Copyright (C) 2018 Muhammad Andi.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();

include_once('./include/business_config.php');
$loginLogo = getLogoPath('', './');
?>

<style>
/* ========================================
   MODERN LOGIN PAGE - Glass Morphism Style
   ======================================== */
.login-page-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 30%, #4338ca 60%, #6366f1 100%);
    position: relative;
    overflow: hidden;
    padding: 20px;
}

/* Animated Background Shapes */
.login-page-wrapper::before {
    content: '';
    position: absolute;
    width: 600px;
    height: 600px;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.3) 0%, rgba(251, 146, 60, 0.2) 100%);
    border-radius: 50%;
    top: -200px;
    right: -200px;
    animation: float 8s ease-in-out infinite;
}

.login-page-wrapper::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(139, 92, 246, 0.2) 100%);
    border-radius: 50%;
    bottom: -150px;
    left: -150px;
    animation: float 10s ease-in-out infinite reverse;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(5deg); }
}

/* Floating Particles */
.particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
}

.particle {
    position: absolute;
    width: 10px;
    height: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: rise 15s infinite ease-in;
}

.particle:nth-child(1) { left: 10%; animation-delay: 0s; width: 8px; height: 8px; }
.particle:nth-child(2) { left: 20%; animation-delay: 2s; width: 12px; height: 12px; }
.particle:nth-child(3) { left: 35%; animation-delay: 4s; width: 6px; height: 6px; }
.particle:nth-child(4) { left: 50%; animation-delay: 6s; width: 14px; height: 14px; }
.particle:nth-child(5) { left: 65%; animation-delay: 8s; width: 10px; height: 10px; }
.particle:nth-child(6) { left: 80%; animation-delay: 10s; width: 8px; height: 8px; }
.particle:nth-child(7) { left: 90%; animation-delay: 12s; width: 12px; height: 12px; }

@keyframes rise {
    0% { bottom: -50px; opacity: 0; transform: translateX(0) scale(1); }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { bottom: 100%; opacity: 0; transform: translateX(100px) scale(0.5); }
}

/* Login Card - Glass Morphism */
.login-card-modern {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 
        0 25px 50px rgba(0, 0, 0, 0.25),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    width: 100%;
    max-width: 420px;
    padding: 50px 40px;
    position: relative;
    z-index: 10;
    animation: fadeInUp 0.8s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Logo Container */
.login-logo-container {
    text-align: center;
    margin-bottom: 75px;
}

.login-logo-wrapper {
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
    position: relative;
}

.login-logo-wrapper::before {
    content: '';
    position: absolute;
    inset: -8px;
    background: linear-gradient(135deg, #f97316 0%, #fb923c 50%, #fbbf24 100%);
    border-radius: 28px;
    opacity: 0.6;
    animation: pulse-glow 2s ease-in-out infinite;
    z-index: -1;
}

@keyframes pulse-glow {
    0%, 100% { opacity: 0.4; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.05); }
}

.login-logo-img {
    width: 120px;
    height: 120px;
    border-radius: 24px;
    object-fit: contain;
    background: linear-gradient(145deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
    box-shadow: 
        0 10px 40px rgba(0, 0, 0, 0.3),
        inset 0 2px 0 rgba(255, 255, 255, 0.5);
    padding: 15px;
    transition: all 0.4s ease;
}

.login-logo-img:hover {
    transform: scale(1.05) rotate(3deg);
    box-shadow: 
        0 15px 50px rgba(0, 0, 0, 0.35),
        inset 0 2px 0 rgba(255, 255, 255, 0.5);
}

.login-logo-fallback {
    width: 120px;
    height: 120px;
    border-radius: 24px;
    background: linear-gradient(135deg, #FFFFFF 0%, #f8fafc 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 56px;
    font-weight: 800;
    color: #4D44B5;
    box-shadow: 
        0 10px 40px rgba(0, 0, 0, 0.3),
        inset 0 2px 0 rgba(255, 255, 255, 0.5);
    transition: all 0.4s ease;
}

.login-logo-fallback:hover {
    transform: scale(1.05) rotate(3deg);
}

/* Brand Name */
.login-brand-name {
    font-size: 32px;
    font-weight: 800;
    letter-spacing: 6px;
    color: #FFFFFF;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    margin: 0;
}

.login-brand-tagline {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 8px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* Form Container */
.login-form-container {
    margin-top: 15px;
}

/* Input Groups */
.login-input-group {
    position: relative;
    margin-bottom: 20px;
}

.login-input-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.5);
    font-size: 18px;
    transition: all 0.3s ease;
    z-index: 2;
}

.login-input {
    width: 100%;
    padding: 16px 20px 16px 50px;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    font-size: 15px;
    color: #FFFFFF;
    transition: all 0.3s ease;
    outline: none;
    box-sizing: border-box;
}

.login-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.login-input:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(249, 115, 22, 0.6);
    box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.15);
}

.login-input:focus + .login-input-icon,
.login-input-group:focus-within .login-input-icon {
    color: #f97316;
}

/* Login Button */
.login-btn-modern {
    width: 100%;
    padding: 16px 30px;
    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
    border: none;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 700;
    color: #FFFFFF;
    cursor: pointer;
    transition: all 0.4s ease;
    text-transform: uppercase;
    letter-spacing: 2px;
    position: relative;
    overflow: hidden;
    margin-top: 10px;
    box-shadow: 0 10px 30px rgba(249, 115, 22, 0.4);
}

.login-btn-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.login-btn-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(249, 115, 22, 0.5);
}

.login-btn-modern:hover::before {
    left: 100%;
}

.login-btn-modern:active {
    transform: translateY(0);
}

/* Error Message */
.login-error-message {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.4);
    border-radius: 12px;
    padding: 14px 18px;
    margin-top: 20px;
    color: #fecaca;
    font-size: 14px;
    text-align: center;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.login-error-message i {
    margin-right: 8px;
}

/* Footer */
.login-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.login-footer-text {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
}

.login-footer-text a {
    color: #fb923c;
    text-decoration: none;
    transition: color 0.3s ease;
}

.login-footer-text a:hover {
    color: #fbbf24;
}

/* Responsive */
@media screen and (max-width: 480px) {
    .login-card-modern {
        padding: 40px 25px;
        border-radius: 20px;
    }
    
    .login-logo-wrapper {
        width: 100px;
        height: 100px;
    }
    
    .login-logo-img,
    .login-logo-fallback {
        width: 100px;
        height: 100px;
        font-size: 44px;
    }
    
    .login-brand-name {
        font-size: 26px;
        letter-spacing: 4px;
    }
    
    .login-input {
        padding: 14px 18px 14px 45px;
    }
    
    .login-btn-modern {
        padding: 14px 25px;
    }
}
</style>

<div class="login-page-wrapper">
    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <!-- Login Card -->
    <div class="login-card-modern">
        <!-- Logo Section -->
        <div class="login-logo-container">
            <div class="login-logo-wrapper">
                <img src="./img/favicon.png?v=<?= time() ?>" alt="MIKPAY Logo" class="login-logo-img">
            </div>
        </div>
        
        <!-- Login Form -->
        <div class="login-form-container">
            <form autocomplete="off" action="" method="post">
                <div class="login-input-group">
                    <input type="text" name="user" id="_username" class="login-input" placeholder="Username" required autofocus>
                    <i class="fa fa-user login-input-icon"></i>
                </div>
                
                <div class="login-input-group">
                    <input type="password" name="pass" class="login-input" placeholder="Password" required>
                    <i class="fa fa-lock login-input-icon"></i>
                </div>
                
                <button type="submit" name="login" class="login-btn-modern">
                    <i class="fa fa-sign-in" style="margin-right: 10px;"></i> Login
                </button>
                
                <?php if (!empty($error)): ?>
                <div class="login-error-message">
                    <i class="fa fa-exclamation-circle"></i>
                    Invalid username or password
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="login-footer">
            <p class="login-footer-text">
                Powered by <a href="#">MIKPAY</a> &copy; <?= date('Y') ?>
            </p>
            <p class="login-footer-text" style="margin-top: 10px;">
                Belum punya akun? <a href="./register.php" style="color: #fb923c; font-weight: 600;">Daftar di sini</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
