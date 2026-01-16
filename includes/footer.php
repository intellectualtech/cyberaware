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
        background: var(--gray-800, #1e293b);
        color: var(--gray-200, #e2e8f0);
        padding: 48px 32px 32px;
        margin-top: 80px;
        border-top: 4px solid var(--primary, #FF8C42);
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
        text-align: center;
    }

    .footer-brand {
        margin-bottom: 24px;
    }

    .footer-logo {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-size: 28px;
        font-weight: 700;
        color: var(--primary, #FF8C42);
        margin-bottom: 12px;
    }

    .footer-logo i {
        font-size: 32px;
    }

    .footer-tagline {
        font-size: 15px;
        color: var(--gray-400, #B8B8B8);
        font-weight: 500;
    }

    .footer-info {
        margin: 24px 0;
    }

    .footer-compliance {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-300, #D4D4D4);
        font-weight: 500;
        padding: 8px 16px;
        background: rgba(255, 140, 66, 0.1);
        border-radius: 20px;
    }

    .footer-compliance i {
        color: var(--primary, #FF8C42);
        font-size: 16px;
    }

    .footer-links {
        margin: 28px 0;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .footer-links a {
        color: var(--gray-300, #D4D4D4);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: color 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 4px;
    }

    .footer-links a:hover {
        color: var(--primary, #FF8C42);
        background: rgba(255, 140, 66, 0.1);
    }

    .footer-links a i {
        font-size: 14px;
    }

    .footer-divider {
        color: var(--gray-600, #475569);
        margin: 0 4px;
    }

    .footer-copyright {
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid var(--gray-700, #334155);
    }

    .footer-copyright p {
        font-size: 13px;
        color: var(--gray-500, #9E9E9E);
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
</style>