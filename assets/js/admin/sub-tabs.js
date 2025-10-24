/**
 * Tabs Functionality
 *
 * @package    SAW_LMS
 * @subpackage Assets/JS/Admin
 * @since      3.1.0
 * @version    3.1.3
 */

(function ($) {
	'use strict';

	/**
	 * Initialize tabs
	 */
	function initTabs() {
		// Tab button click handler
		$('.saw-tab-button').on('click', function () {
			const $button = $(this);
			const tabId = $button.data('tab');
			const $wrapper = $button.closest('.saw-tabs-wrapper');

			// Remove active class from all buttons in this wrapper
			$wrapper.find('.saw-tab-button').removeClass('saw-tab-active');
			$button.addClass('saw-tab-active');

			// Hide all tab content in this wrapper
			$wrapper.find('.saw-tab-content').removeClass('saw-tab-content-active');

			// Show selected tab content
			$wrapper.find('[data-tab-content="' + tabId + '"]').addClass('saw-tab-content-active');
		});
	}

	/**
	 * Initialize when DOM is ready
	 */
	$(document).ready(function () {
		if ($('.saw-tabs-wrapper').length > 0) {
			console.log('SAW LMS: Initializing tabs...');
			initTabs();
		}
	});
})(jQuery);