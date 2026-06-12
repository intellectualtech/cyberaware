<?php
/**
 * Theme Initialization Snippet
 * Include this in the <head> of every trainee page
 * Usage: <?php include 'theme-init.php'; ?>
 */
require_once '../includes/theme-helper.php';
?>
<script>
// Apply theme immediately from localStorage to prevent flash
(function() {
    const theme = localStorage.getItem('theme') || 'light';
    if (theme === 'dark') {
        document.documentElement.classList.add('dark-mode-init');
    }
})();
</script>
