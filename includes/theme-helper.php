<?php
/**
 * Theme Helper - Manages theme persistence across the application
 * Handles both session and localStorage synchronization
 */

// Initialize theme in session if not already set
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

/**
 * Get the current theme
 * @return string 'light' or 'dark'
 */
function getCurrentTheme() {
    return isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';
}

/**
 * Set the theme in session
 * @param string $theme 'light' or 'dark'
 */
function setThemeSession($theme) {
    $_SESSION['theme'] = ($theme === 'dark') ? 'dark' : 'light';
}

/**
 * Get the theme class for body tag
 * @return string 'dark-mode' or empty string
 */
function getThemeClass() {
    return getCurrentTheme() === 'dark' ? 'dark-mode' : '';
}
?>
