/**
 * SAW LMS Admin Design System - JavaScript Utilities
 *
 * Pomocné JavaScript funkce pro admin rozhraní.
 * Vanilla JavaScript (bez jQuery závislostí).
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/assets/js/admin
 * @since      1.0.0
 * @version    1.9.0
 */

(function() {
	'use strict';

	/**
	 * Main SAW LMS Admin object
	 * Namespace pro všechny admin utility funkce
	 */
	window.SAW_LMS_Admin = window.SAW_LMS_Admin || {};

	/**
	 * ============================================
	 * AJAX HELPER
	 * ============================================
	 */
	SAW_LMS_Admin.ajax = {
		/**
		 * Perform WordPress AJAX request
		 *
		 * @param {string} action WordPress AJAX action name
		 * @param {Object} data Data to send
		 * @param {Object} options Additional options
		 * @returns {Promise}
		 */
		request: function(action, data, options) {
			const defaults = {
				method: 'POST',
				nonce: sawLmsAdmin.nonce || '', // From wp_localize_script
			};
			
			options = Object.assign({}, defaults, options);
			
			// Prepare form data
			const formData = new FormData();
			formData.append('action', action);
			formData.append('nonce', options.nonce);
			
			// Add data to form
			for (const key in data) {
				if (data.hasOwnProperty(key)) {
					formData.append(key, data[key]);
				}
			}
			
			// Return promise
			return fetch(sawLmsAdmin.ajaxUrl, {
				method: options.method,
				body: formData,
				credentials: 'same-origin',
			})
			.then(response => {
				if (!response.ok) {
					throw new Error('Network response was not ok');
				}
				return response.json();
			})
			.then(data => {
				if (!data.success && data.data && data.data.message) {
					throw new Error(data.data.message);
				}
				return data;
			});
		},

		/**
		 * Perform GET request
		 */
		get: function(action, data) {
			return this.request(action, data, { method: 'GET' });
		},

		/**
		 * Perform POST request
		 */
		post: function(action, data) {
			return this.request(action, data, { method: 'POST' });
		},
	};

	/**
	 * ============================================
	 * NOTIFICATION SYSTEM
	 * ============================================
	 */
	SAW_LMS_Admin.notify = {
		/**
		 * Container for notifications
		 */
		container: null,

		/**
		 * Initialize notification system
		 */
		init: function() {
			if (!this.container) {
				this.container = document.createElement('div');
				this.container.id = 'saw-notifications';
				this.container.style.position = 'fixed';
				this.container.style.top = '32px'; // Below WP admin bar
				this.container.style.right = '20px';
				this.container.style.zIndex = '9999';
				this.container.style.display = 'flex';
				this.container.style.flexDirection = 'column';
				this.container.style.gap = '10px';
				this.container.style.maxWidth = '400px';
				document.body.appendChild(this.container);
			}
		},

		/**
		 * Show notification
		 *
		 * @param {string} message Message text
		 * @param {string} type Type (success, error, warning, info)
		 * @param {number} duration Duration in ms (0 = persistent)
		 */
		show: function(message, type, duration) {
			this.init();
			
			type = type || 'info';
			duration = duration || 5000;
			
			// Create notification element
			const notification = document.createElement('div');
			notification.className = `saw-alert saw-alert-${type}`;
			notification.style.minWidth = '300px';
			notification.style.boxShadow = 'var(--saw-shadow-lg)';
			
			// Icon based on type
			const icons = {
				success: '✓',
				error: '✕',
				warning: '⚠',
				info: 'ℹ',
			};
			
			notification.innerHTML = `
				<span class="saw-alert-icon">${icons[type]}</span>
				<div class="saw-alert-content">
					<div class="saw-alert-message">${message}</div>
				</div>
				<button type="button" class="saw-alert-close" aria-label="Close">×</button>
			`;
			
			// Add to container
			this.container.appendChild(notification);
			
			// Close button handler
			const closeBtn = notification.querySelector('.saw-alert-close');
			closeBtn.addEventListener('click', () => {
				this.remove(notification);
			});
			
			// Auto remove after duration
			if (duration > 0) {
				setTimeout(() => {
					this.remove(notification);
				}, duration);
			}
			
			return notification;
		},

		/**
		 * Remove notification with animation
		 */
		remove: function(notification) {
			notification.style.transition = 'opacity 200ms, transform 200ms';
			notification.style.opacity = '0';
			notification.style.transform = 'translateX(400px)';
			
			setTimeout(() => {
				if (notification.parentNode) {
					notification.parentNode.removeChild(notification);
				}
			}, 200);
		},

		/**
		 * Shorthand methods
		 */
		success: function(message, duration) {
			return this.show(message, 'success', duration);
		},

		error: function(message, duration) {
			return this.show(message, 'error', duration);
		},

		warning: function(message, duration) {
			return this.show(message, 'warning', duration);
		},

		info: function(message, duration) {
			return this.show(message, 'info', duration);
		},
	};

	/**
	 * ============================================
	 * MODAL SYSTEM
	 * ============================================
	 */
	SAW_LMS_Admin.modal = {
		/**
		 * Show modal
		 *
		 * @param {Object} options Modal options
		 * @returns {Object} Modal instance
		 */
		show: function(options) {
			const defaults = {
				title: '',
				content: '',
				size: '', // sm, lg, xl
				buttons: [],
				closeOnOverlay: true,
				onOpen: null,
				onClose: null,
			};
			
			options = Object.assign({}, defaults, options);
			
			// Create overlay
			const overlay = document.createElement('div');
			overlay.className = 'saw-modal-overlay';
			
			// Create modal
			const modal = document.createElement('div');
			modal.className = 'saw-modal';
			if (options.size) {
				modal.classList.add(`saw-modal-${options.size}`);
			}
			
			// Modal HTML
			let buttonsHTML = '';
			if (options.buttons.length > 0) {
				buttonsHTML = '<div class="saw-modal-footer">';
				options.buttons.forEach(btn => {
					const btnClass = btn.class || 'saw-btn-secondary';
					buttonsHTML += `<button type="button" class="saw-btn ${btnClass}" data-action="${btn.action || ''}">${btn.text}</button>`;
				});
				buttonsHTML += '</div>';
			}
			
			modal.innerHTML = `
				<div class="saw-modal-header">
					<h2 class="saw-modal-title">${options.title}</h2>
					<button type="button" class="saw-modal-close" aria-label="Close">×</button>
				</div>
				<div class="saw-modal-body">
					${options.content}
				</div>
				${buttonsHTML}
			`;
			
			overlay.appendChild(modal);
			document.body.appendChild(overlay);
			
			// Prevent body scroll
			document.body.style.overflow = 'hidden';
			
			// Close handlers
			const close = () => {
				document.body.style.overflow = '';
				overlay.style.opacity = '0';
				modal.style.opacity = '0';
				modal.style.transform = 'translateY(2rem)';
				
				setTimeout(() => {
					if (overlay.parentNode) {
						overlay.parentNode.removeChild(overlay);
					}
				}, 200);
				
				if (options.onClose) {
					options.onClose();
				}
			};
			
			// Close button
			const closeBtn = modal.querySelector('.saw-modal-close');
			closeBtn.addEventListener('click', close);
			
			// Close on overlay click
			if (options.closeOnOverlay) {
				overlay.addEventListener('click', (e) => {
					if (e.target === overlay) {
						close();
					}
				});
			}
			
			// Button handlers
			options.buttons.forEach(btn => {
				if (btn.handler) {
					const btnElement = modal.querySelector(`[data-action="${btn.action}"]`);
					if (btnElement) {
						btnElement.addEventListener('click', () => {
							btn.handler(close);
						});
					}
				}
			});
			
			// ESC key to close
			const escHandler = (e) => {
				if (e.key === 'Escape') {
					close();
					document.removeEventListener('keydown', escHandler);
				}
			};
			document.addEventListener('keydown', escHandler);
			
			// On open callback
			if (options.onOpen) {
				options.onOpen(modal);
			}
			
			return {
				element: modal,
				overlay: overlay,
				close: close,
			};
		},

		/**
		 * Show confirmation dialog
		 */
		confirm: function(message, title, onConfirm) {
			title = title || 'Confirm';
			
			return this.show({
				title: title,
				content: `<p>${message}</p>`,
				buttons: [
					{
						text: 'Cancel',
						class: 'saw-btn-secondary',
						action: 'cancel',
						handler: (close) => close(),
					},
					{
						text: 'Confirm',
						class: 'saw-btn-primary',
						action: 'confirm',
						handler: (close) => {
							if (onConfirm) {
								onConfirm();
							}
							close();
						},
					},
				],
			});
		},

		/**
		 * Show alert dialog
		 */
		alert: function(message, title, onClose) {
			title = title || 'Alert';
			
			return this.show({
				title: title,
				content: `<p>${message}</p>`,
				buttons: [
					{
						text: 'OK',
						class: 'saw-btn-primary',
						action: 'ok',
						handler: (close) => {
							if (onClose) {
								onClose();
							}
							close();
						},
					},
				],
			});
		},
	};

	/**
	 * ============================================
	 * LOADING STATE
	 * ============================================
	 */
	SAW_LMS_Admin.loading = {
		/**
		 * Show loading overlay on element
		 */
		show: function(element) {
			if (typeof element === 'string') {
				element = document.querySelector(element);
			}
			
			if (!element) return;
			
			const overlay = document.createElement('div');
			overlay.className = 'saw-loading-overlay';
			overlay.innerHTML = '<div class="saw-spinner"></div>';
			
			element.style.position = 'relative';
			element.appendChild(overlay);
			
			return overlay;
		},

		/**
		 * Hide loading overlay
		 */
		hide: function(element) {
			if (typeof element === 'string') {
				element = document.querySelector(element);
			}
			
			if (!element) return;
			
			const overlay = element.querySelector('.saw-loading-overlay');
			if (overlay) {
				overlay.parentNode.removeChild(overlay);
			}
		},
	};

	/**
	 * ============================================
	 * TABS FUNCTIONALITY
	 * ============================================
	 */
	SAW_LMS_Admin.tabs = {
		/**
		 * Initialize tabs
		 */
		init: function(tabsElement) {
			if (typeof tabsElement === 'string') {
				tabsElement = document.querySelector(tabsElement);
			}
			
			if (!tabsElement) return;
			
			const links = tabsElement.querySelectorAll('.saw-tabs-link');
			const contents = document.querySelectorAll('.saw-tabs-content');
			
			links.forEach(link => {
				link.addEventListener('click', (e) => {
					e.preventDefault();
					
					const targetId = link.getAttribute('data-tab');
					
					// Remove active from all
					links.forEach(l => l.classList.remove('is-active'));
					contents.forEach(c => c.classList.remove('is-active'));
					
					// Add active to clicked
					link.classList.add('is-active');
					const targetContent = document.getElementById(targetId);
					if (targetContent) {
						targetContent.classList.add('is-active');
					}
				});
			});
		},
	};

	/**
	 * ============================================
	 * COLLAPSIBLE PANELS
	 * ============================================
	 */
	SAW_LMS_Admin.panels = {
		/**
		 * Initialize collapsible panels
		 */
		init: function() {
			const panels = document.querySelectorAll('.saw-panel');
			
			panels.forEach(panel => {
				const header = panel.querySelector('.saw-panel-header');
				if (!header) return;
				
				header.addEventListener('click', () => {
					panel.classList.toggle('is-open');
				});
			});
		},
	};

	/**
	 * ============================================
	 * UTILITY FUNCTIONS
	 * ============================================
	 */
	SAW_LMS_Admin.utils = {
		/**
		 * Debounce function
		 */
		debounce: function(func, wait) {
			let timeout;
			return function executedFunction(...args) {
				const later = () => {
					clearTimeout(timeout);
					func(...args);
				};
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
			};
		},

		/**
		 * Format number with thousands separator
		 */
		formatNumber: function(num) {
			return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		},

		/**
		 * Copy text to clipboard
		 */
		copyToClipboard: function(text) {
			if (navigator.clipboard) {
				return navigator.clipboard.writeText(text);
			} else {
				// Fallback for older browsers
				const textarea = document.createElement('textarea');
				textarea.value = text;
				textarea.style.position = 'fixed';
				textarea.style.opacity = '0';
				document.body.appendChild(textarea);
				textarea.select();
				document.execCommand('copy');
				document.body.removeChild(textarea);
				return Promise.resolve();
			}
		},

		/**
		 * Escape HTML
		 */
		escapeHtml: function(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},
	};

	/**
	 * ============================================
	 * INITIALIZATION
	 * ============================================
	 */
	document.addEventListener('DOMContentLoaded', function() {
		// Initialize tabs if present
		const tabsElements = document.querySelectorAll('.saw-tabs');
		tabsElements.forEach(tabs => {
			SAW_LMS_Admin.tabs.init(tabs);
		});
		
		// Initialize panels if present
		SAW_LMS_Admin.panels.init();
		
		// Log that utilities are ready
		if (window.console && window.console.log) {
			console.log('SAW LMS Admin Utilities loaded');
		}
	});

})();

/**
 * SAW Tabs Component
 *
 * Handles tab switching in admin meta boxes.
 *
 * @since 3.1.0
 */
SAW_LMS.tabs = {
    /**
     * Initialize tabs
     */
    init: function() {
        jQuery(document).on('click', '.saw-tab-button', function(e) {
            e.preventDefault();

            var $button = jQuery(this);
            var tabId = $button.data('tab');
            var $wrapper = $button.closest('.saw-tabs-wrapper');

            // Update button states
            $wrapper.find('.saw-tab-button').removeClass('saw-tab-active');
            $button.addClass('saw-tab-active');

            // Update content visibility
            $wrapper.find('.saw-tab-content').removeClass('saw-tab-content-active');
            $wrapper.find('.saw-tab-content[data-tab-content="' + tabId + '"]').addClass('saw-tab-content-active');
        });
    }
};

// Initialize tabs on document ready
jQuery(document).ready(function() {
    SAW_LMS.tabs.init();
});