/**
 * Vertical Layout JavaScript Fix
 * 
 * Forces vertical layout using JavaScript
 * Use this if CSS doesn't work
 */

(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        // Only on Course edit screen
        if (!document.body.classList.contains('post-type-saw_course')) {
            return;
        }

        console.log('SAW LMS: Applying vertical layout via JavaScript...');

        // Find the main skeleton body
        var skeletonBody = document.querySelector('.interface-interface-skeleton__body');
        
        if (skeletonBody) {
            // Apply flex layout
            skeletonBody.style.display = 'flex';
            skeletonBody.style.flexDirection = 'row';
            skeletonBody.style.width = '100%';
            
            console.log('SAW LMS: Found skeleton body, applying styles');
        } else {
            console.warn('SAW LMS: Could not find .interface-interface-skeleton__body');
        }

        // Find editor content (left panel)
        var editorContent = document.querySelector('.interface-interface-skeleton__content');
        
        if (editorContent) {
            editorContent.style.flex = '0 0 65%';
            editorContent.style.width = '65%';
            editorContent.style.order = '1';
            
            console.log('SAW LMS: Found editor content, applying styles');
        } else {
            console.warn('SAW LMS: Could not find .interface-interface-skeleton__content');
        }

        // Find sidebar (right panel)
        var sidebar = document.querySelector('.interface-navigable-region.interface-interface-skeleton__sidebar');
        
        if (sidebar) {
            sidebar.style.flex = '0 0 35%';
            sidebar.style.width = '35%';
            sidebar.style.order = '2';
            sidebar.style.borderLeft = '1px solid #ddd';
            sidebar.style.background = '#f0f0f1';
            
            console.log('SAW LMS: Found sidebar, applying styles');
        } else {
            console.warn('SAW LMS: Could not find .interface-navigable-region.interface-interface-skeleton__sidebar');
        }

        // Alternative: Try metaboxes area
        var metaboxes = document.querySelector('.edit-post-layout__metaboxes');
        
        if (metaboxes) {
            metaboxes.style.flex = '0 0 35%';
            metaboxes.style.width = '35%';
            metaboxes.style.order = '2';
            
            console.log('SAW LMS: Found metaboxes, applying styles');
        }

        // Remove resize handles
        var handles = document.querySelectorAll('.components-resizable-box__handle');
        handles.forEach(function(handle) {
            handle.style.display = 'none';
        });

        // Add visual confirmation
        var banner = document.createElement('div');
        banner.textContent = '🔧 JS LAYOUT ACTIVE';
        banner.style.position = 'fixed';
        banner.style.bottom = '10px';
        banner.style.right = '10px';
        banner.style.background = '#0073aa';
        banner.style.color = 'white';
        banner.style.padding = '8px 12px';
        banner.style.fontWeight = 'bold';
        banner.style.zIndex = '999999';
        banner.style.borderRadius = '3px';
        document.body.appendChild(banner);

        console.log('SAW LMS: Vertical layout JavaScript applied successfully');
    });

})(jQuery);