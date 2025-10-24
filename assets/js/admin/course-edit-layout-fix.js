/**
 * Course Edit Layout Fix JavaScript
 *
 * Resets Gutenberg localStorage preferences that may cause split-screen layout.
 *
 * PROBLEM: Gutenberg stores editor preferences in localStorage:
 * - isFullscreenMode
 * - fixedToolbar
 * - distractionFree
 * - welcomeGuide
 * These can persist and cause layout issues even after CSS fixes.
 *
 * SOLUTION:
 * - Reset problematic preferences on course edit screen
 * - Run ONCE per session using sessionStorage flag
 * - Silent operation (no console spam)
 *
 * @package    SAW_LMS
 * @subpackage Assets/JS/Admin
 * @since      3.2.7
 * @version    3.2.7
 */

(function() {
	'use strict';

	/**
	 * Check if we're on Course edit screen
	 *
	 * @return {boolean}
	 */
	function isCourseEditScreen() {
		// Check body class
		if (document.body.classList.contains('post-type-saw_course')) {
			return true;
		}

		// Fallback: Check URL
		const url = window.location.href;
		return url.indexOf('post_type=saw_course') !== -1 ||
		       (url.indexOf('post.php') !== -1 && url.indexOf('post=') !== -1);
	}

	/**
	 * Reset Gutenberg localStorage preferences
	 *
	 * Runs ONCE per session to avoid repeated resets.
	 *
	 * @return {void}
	 */
	function resetGutenbergPreferences() {
		// Check if already reset this session
		const sessionKey = 'saw_lms_gutenberg_reset_v327';
		if (sessionStorage.getItem(sessionKey)) {
			return; // Already reset, skip
		}

		// Keys to reset (Gutenberg 5.9+ uses these)
		const preferencesToReset = [
			// Editor mode preferences
			'WP_DATA_USER_PREFERENCES_0', // Main preferences object
			'WP_DATA_USER_PREFERENCES_1', // Backup preferences
			
			// Specific flags (for older Gutenberg versions)
			'wp-preferences-persistence',
			'wp-preferences',
			
			// Individual flags
			'isFullscreenMode',
			'fixedToolbar',
			'distractionFree',
			'welcomeGuide',
			
			// Interface skeleton preferences
			'interfaceEnableComplementaryArea',
			'interfaceEnablePinnedItems',
		];

		let resetCount = 0;

		// Reset each preference
		preferencesToReset.forEach(function(key) {
			if (localStorage.getItem(key) !== null) {
				try {
					// For WP_DATA preferences, we need to modify the object
					if (key.startsWith('WP_DATA_USER_PREFERENCES')) {
						const data = JSON.parse(localStorage.getItem(key));
						
						if (data && data['core/edit-post']) {
							// Force unified layout preferences
							data['core/edit-post'].isFullscreenMode = false;
							data['core/edit-post'].fixedToolbar = false;
							data['core/edit-post'].distractionFree = false;
							data['core/edit-post'].welcomeGuide = false;
							
							localStorage.setItem(key, JSON.stringify(data));
							resetCount++;
						}
					} else {
						// For simple flags, just remove them
						localStorage.removeItem(key);
						resetCount++;
					}
				} catch(e) {
					// Silent fail - don't break page if JSON parse fails
				}
			}
		});

		// Mark as reset this session
		sessionStorage.setItem(sessionKey, 'true');

		// Optional: Log to console if WP_DEBUG is active
		// (Check for wp object existence - only available when WP_DEBUG is true)
		if (typeof wp !== 'undefined' && wp.data && resetCount > 0) {
			console.log('✅ SAW LMS: Gutenberg editor flow reset completed (' + resetCount + ' preferences fixed)');
		}
	}

	/**
	 * Force disable Gutenberg fullscreen mode
	 *
	 * Some versions of Gutenberg ignore localStorage and use Redux store.
	 * This is a backup method to force disable fullscreen mode via API.
	 *
	 * @return {void}
	 */
	function disableFullscreenMode() {
		// Wait for Gutenberg to load
		if (typeof wp === 'undefined' || !wp.data) {
			// Not loaded yet, try again in 100ms
			setTimeout(disableFullscreenMode, 100);
			return;
		}

		// Check if edit-post store exists
		const editPost = wp.data.select('core/edit-post');
		if (!editPost || typeof editPost.isFeatureActive !== 'function') {
			return; // Store not available
		}

		// If fullscreen mode is active, disable it
		if (editPost.isFeatureActive('fullscreenMode')) {
			wp.data.dispatch('core/edit-post').toggleFeature('fullscreenMode');
		}

		// Force fixed toolbar off (unified scroll needs this)
		if (editPost.isFeatureActive('fixedToolbar')) {
			wp.data.dispatch('core/edit-post').toggleFeature('fixedToolbar');
		}
	}

	/**
	 * Initialize fixes
	 *
	 * Run on DOMContentLoaded to ensure body classes are loaded.
	 *
	 * @return {void}
	 */
	function init() {
		// Only run on Course edit screen
		if (!isCourseEditScreen()) {
			return;
		}

		// Reset localStorage preferences
		resetGutenbergPreferences();

		// Disable fullscreen mode via API (backup method)
		if (document.readyState === 'complete') {
			disableFullscreenMode();
		} else {
			window.addEventListener('load', disableFullscreenMode);
		}
	}

	// Run when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		// DOM already loaded
		init();
	}

})();