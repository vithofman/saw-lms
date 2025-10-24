/**
 * Sub-Tabs Functionality for Settings Tab
 *
 * Handles vertical sub-tabs navigation within the Settings tab.
 * This is DIFFERENT from main tabs (tabs.js) - it uses data-panel attributes.
 *
 * @package    SAW_LMS
 * @subpackage Assets/JS/Admin
 * @since      3.2.4
 * @version    3.2.5
 */

(function ($) {
	'use strict';

	/**
	 * Initialize sub-tabs
	 *
	 * Sub-tabs structure:
	 * - Container: .saw-sub-tabs-container
	 * - Menu: .saw-sub-tabs-menu
	 * - Buttons: .saw-sub-tab-button with data-panel="panel-id"
	 * - Panels: .saw-sub-tab-panel with data-panel-content="panel-id"
	 */
	function initSubTabs() {
		// Sub-tab button click handler
		$('.saw-sub-tab-button').on('click', function (e) {
			e.preventDefault();
			
			const $button = $(this);
			const panelId = $button.data('panel');
			const $container = $button.closest('.saw-sub-tabs-container');

			// Debug logging
			console.log('SAW LMS Sub-tabs: Button clicked', {
				panelId: panelId,
				button: $button[0],
				container: $container[0]
			});

			// Validation
			if (!panelId) {
				console.error('SAW LMS Sub-tabs: No data-panel attribute found on button');
				return;
			}

			if ($container.length === 0) {
				console.error('SAW LMS Sub-tabs: No .saw-sub-tabs-container parent found');
				return;
			}

			// Remove active class from all buttons in this container
			$container.find('.saw-sub-tab-button').removeClass('saw-sub-tab-active');
			$button.addClass('saw-sub-tab-active');

			// Hide all sub-tab panels in this container
			$container.find('.saw-sub-tab-panel').removeClass('saw-sub-tab-panel-active');

			// Show selected sub-tab panel
			const $targetPanel = $container.find('[data-panel-content="' + panelId + '"]');
			
			if ($targetPanel.length === 0) {
				console.error('SAW LMS Sub-tabs: No panel found with data-panel-content="' + panelId + '"');
				return;
			}

			$targetPanel.addClass('saw-sub-tab-panel-active');

			// Debug logging
			console.log('SAW LMS Sub-tabs: Panel switched to', panelId);
		});
	}

	/**
	 * Initialize when DOM is ready
	 */
	$(document).ready(function () {
		// Check if sub-tabs container exists
		if ($('.saw-sub-tabs-container').length > 0) {
			console.log('SAW LMS: Initializing sub-tabs...', {
				containers: $('.saw-sub-tabs-container').length,
				buttons: $('.saw-sub-tab-button').length,
				panels: $('.saw-sub-tab-panel').length
			});
			
			initSubTabs();
			
			// Ensure first panel is visible on page load
			$('.saw-sub-tabs-container').each(function() {
				const $container = $(this);
				const $activeButton = $container.find('.saw-sub-tab-button.saw-sub-tab-active');
				
				if ($activeButton.length > 0) {
					const activePanelId = $activeButton.data('panel');
					const $activePanel = $container.find('[data-panel-content="' + activePanelId + '"]');
					
					if ($activePanel.length > 0 && !$activePanel.hasClass('saw-sub-tab-panel-active')) {
						$activePanel.addClass('saw-sub-tab-panel-active');
						console.log('SAW LMS Sub-tabs: Activated initial panel:', activePanelId);
					}
				}
			});
		} else {
			console.log('SAW LMS: No sub-tabs containers found on this page');
		}
	});

})(jQuery);