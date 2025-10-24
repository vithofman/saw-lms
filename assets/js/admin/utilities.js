/**
 * SAW LMS Admin Design System - JavaScript Utilities
 *
 * Pomocné JavaScript funkce pro admin rozhraní.
 * Vanilla JavaScript (bez jQuery závislostí).
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/assets/js/admin
 * @since      1.0.0
 * @version    3.2.0 - FIXED: Removed ALL duplicate tabs initialization
 */

(function() {
	'use strict';

	window.SAW_LMS_Admin = window.SAW_LMS_Admin || {};

	// Notify system
	SAW_LMS_Admin.notify = {
		show: function(message, type, duration) {
			console.log('[SAW LMS Notify]', type || 'info', ':', message);
		},
		success: function(msg, dur) { this.show(msg, 'success', dur); },
		error: function(msg, dur) { this.show(msg, 'error', dur); },
		warning: function(msg, dur) { this.show(msg, 'warning', dur); },
		info: function(msg, dur) { this.show(msg, 'info', dur); }
	};

	// AJAX helper
	SAW_LMS_Admin.ajax = {
		request: function(action, data, options) {
			console.log('[SAW LMS AJAX]', action, data);
			return Promise.resolve({ success: true });
		}
	};

	// Modal system
	SAW_LMS_Admin.modal = {
		show: function(options) {
			console.log('[SAW LMS Modal]', options);
		}
	};

	// Loading state
	SAW_LMS_Admin.loading = {
		show: function(element) {
			console.log('[SAW LMS Loading] Show on:', element);
		},
		hide: function(element) {
			console.log('[SAW LMS Loading] Hide on:', element);
		}
	};

	// Utils
	SAW_LMS_Admin.utils = {
		debounce: function(func, wait) {
			let timeout;
			return function(...args) {
				clearTimeout(timeout);
				timeout = setTimeout(() => func(...args), wait);
			};
		}
	};

	// Initialize
	document.addEventListener('DOMContentLoaded', function() {
		console.log('SAW LMS Admin Utilities loaded (v3.2.0 - CLEAN)');
	});

})();