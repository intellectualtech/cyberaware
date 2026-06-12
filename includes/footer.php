<?php
// includes/footer.php - Reusable Footer Component
?>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <div class="footer-logo">
                <i class="fas fa-shield-alt"></i>
                <span>CyberAware</span>
            </div>
            <p class="footer-tagline">Security Awareness Training Platform</p>
        </div>
        
        <div class="footer-info">
            <p class="footer-compliance">
                <i class="fas fa-check-circle"></i>
                Training-Only Use | ISO 27001 & NIST Compliant
            </p>
        </div>
        
        <div class="footer-links">
            <a href="compliance.php">
                <i class="fas fa-clipboard-check"></i>
                Compliance & Ethics
            </a>
            <span class="footer-divider">|</span>
            <a href="privacy.php">
                <i class="fas fa-shield-alt"></i>
                Privacy Policy
            </a>
            <span class="footer-divider">|</span>
            <a href="contact.php">
                <i class="fas fa-envelope"></i>
                Contact Us
            </a>
        </div>
        
        <div class="footer-copyright">
            <p>
                <i class="far fa-copyright"></i>
                <?php echo date('Y'); ?> CyberAware. All rights reserved.
            </p>
        </div>
    </div>
</footer>

<style>
    .footer {
        background: rgba(255, 255, 255, 0.96);
        color: var(--dark-navy);
        padding: 24px 32px 20px;
        margin-top: 80px;
        border-top: 5px solid var(--cyber-yellow, #FF8C42);
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
        text-align: center;
    }

    .footer-brand {
        margin-bottom: 12px;
    }

    .footer-logo {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-size: 24px;
        font-weight: 700;
        color: var(--orange-600, #ea580c);
        margin-bottom: 4px;
    }

    .footer-logo i {
        font-size: 32px;
    }

    .footer-tagline {
        font-size: 13px;
        color: var(--slate-600);
        font-weight: 500;
    }

    .footer-info {
        margin: 12px 0;
    }

    .footer-compliance {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--dark-navy);
        font-weight: 500;
        padding: 6px 12px;
        background: rgba(251, 146, 60, 0.1);
        border-radius: 20px;
    }

    .footer-compliance i {
        color: var(--orange-500, #f97316);
        font-size: 16px;
    }

    .footer-links {
        margin: 12px 0;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .footer-links a {
        color: var(--dark-navy);
        text-decoration: none;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .footer-links a:hover {
        color: var(--orange-500, #f97316);
        background: rgba(251, 146, 60, 0.08);
    }

    .footer-links a i {
        font-size: 14px;
    }

    .footer-divider {
        color: var(--slate-300);
        margin: 0 4px;
    }

    .footer-copyright {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--slate-200);
    }

    .footer-copyright p {
        font-size: 12px;
        color: var(--slate-600);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .footer-copyright i {
        font-size: 14px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .footer {
            padding: 40px 24px 24px;
            margin-top: 60px;
        }

        .footer-logo {
            font-size: 24px;
        }

        .footer-logo i {
            font-size: 28px;
        }

        .footer-links {
            flex-direction: column;
            gap: 12px;
        }

        .footer-divider {
            display: none;
        }

        .footer-links a {
            padding: 10px 20px;
        }
    }

    @media (max-width: 576px) {
        .footer {
            padding: 32px 16px 20px;
        }

        .footer-compliance {
            font-size: 13px;
            padding: 6px 12px;
        }

        .footer-links a {
            font-size: 13px;
        }
    }

<!-- Scroll-activated Nav Popup (Injected) -->
<div id="nav-popup" class="nav-popup" aria-hidden="true" role="dialog" aria-label="Quick navigation">
    <a class="nav-item" href="/cyberaware/index.php"><i class="fas fa-home"></i><span>Home</span></a>
    <a class="nav-item" href="/cyberaware/training.php"><i class="fas fa-graduation-cap"></i><span>Training</span></a>
    <a class="nav-item" href="/cyberaware/trainee/dashboard.php"><i class="fas fa-chart-line"></i><span>Progress</span></a>
    <a class="nav-item" href="/cyberaware/compliance.php"><i class="fas fa-clipboard-check"></i><span>Compliance</span></a>
    <button id="nav-popup-close" class="nav-popup-close" aria-label="Close quick navigation"><i class="fas fa-times"></i></button>
</div>

<script>
(function(){
    var popup = document.getElementById('nav-popup');
    var closeBtn = document.getElementById('nav-popup-close');
    var timeoutId = null;

    function showPopup() {
        if (!popup) return;
        popup.classList.add('show');
        popup.setAttribute('aria-hidden','false');
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(hidePopup, 2200);
    }

    function hidePopup() {
        if (!popup) return;
        popup.classList.remove('show');
        popup.setAttribute('aria-hidden','true');
    }

    // Show on any wheel movement (up or down)
    window.addEventListener('wheel', function(e){
        try {
            showPopup();
        } catch(err) { /* ignore */ }
    }, { passive: true });

    if (closeBtn) closeBtn.addEventListener('click', hidePopup);

    document.addEventListener('click', function(e){
        if (!popup.classList.contains('show')) return;
        if (!popup.contains(e.target) && !e.target.closest('.sidebar')) {
            hidePopup();
        }
    });
})();
</script>
</style>