/**
 * Course Settings Tab in Gutenberg Editor
 * 
 * Adds a "Settings" tab next to Gutenberg's native interface
 * 
 * @package SAW_LMS
 * @since 3.3.0
 */

(function($) {
    'use strict';

    // Wait for Gutenberg to load
    $(window).on('load', function() {
        // Only on Course edit screen
        if (!document.body.classList.contains('post-type-saw_course')) {
            return;
        }

        console.log('SAW LMS: Adding Course Settings tab to Gutenberg...');

        // Wait a bit for Gutenberg to fully render
        setTimeout(function() {
            addSettingsTab();
        }, 1000);
    });

    function addSettingsTab() {
        // Find Gutenberg header toolbar
        var toolbar = document.querySelector('.edit-post-header__toolbar');
        
        if (!toolbar) {
            console.warn('SAW LMS: Gutenberg toolbar not found');
            return;
        }

        // Get post ID from URL
        var urlParams = new URLSearchParams(window.location.search);
        var postId = urlParams.get('post');

        if (!postId) {
            console.warn('SAW LMS: Post ID not found in URL');
            return;
        }

        // Create Settings button
        var settingsBtn = document.createElement('a');
        settingsBtn.href = 'admin.php?page=saw-course-settings&post=' + postId;
        settingsBtn.className = 'saw-lms-settings-tab components-button is-tertiary';
        settingsBtn.innerHTML = '⚙️ Course Settings';
        settingsBtn.style.cssText = 'margin-left: 16px; padding: 6px 12px; height: auto; border-radius: 4px; background: #fff; border: 1px solid #ddd;';

        // Highlight on hover
        settingsBtn.addEventListener('mouseenter', function() {
            this.style.background = '#f0f0f1';
        });
        settingsBtn.addEventListener('mouseleave', function() {
            this.style.background = '#fff';
        });

        // Insert after the main toolbar section
        var toolbarStart = toolbar.querySelector('.edit-post-header__toolbar-start') || toolbar;
        toolbarStart.appendChild(settingsBtn);

        console.log('✅ SAW LMS: Course Settings tab added to Gutenberg toolbar');
    }

})(jQuery);