/**
 * SAW LMS Section Meta Box JavaScript
 *
 * Handles media uploader and document management for Section Optional Content.
 * NEW in Phase 2.2.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/assets/js/admin
 * @since      2.2.0
 */

(function($) {
	'use strict';

	/**
	 * Section Meta Box Handler
	 */
	const SAW_Section_MetaBox = {

		/**
		 * Media uploader instance
		 */
		mediaUploader: null,

		/**
		 * Selected documents array
		 */
		selectedDocuments: [],

		/**
		 * Initialize
		 */
		init: function() {
			this.setupDocumentUpload();
			this.setupDocumentRemoval();
			this.setupVideoValidation();
			this.loadExistingDocuments();
		},

		/**
		 * Load existing documents into selectedDocuments array
		 */
		loadExistingDocuments: function() {
			const self = this;
			self.selectedDocuments = [];

			$('.saw-lms-document-item').each(function() {
				const docId = $(this).data('id');
				if (docId) {
					self.selectedDocuments.push(parseInt(docId));
				}
			});

			console.log('SAW LMS: Loaded existing documents:', self.selectedDocuments);
		},

		/**
		 * Setup document upload functionality
		 */
		setupDocumentUpload: function() {
			const self = this;

			$('#saw_section_upload_documents').on('click', function(e) {
				e.preventDefault();

				// If media uploader exists, open it
				if (self.mediaUploader) {
					self.mediaUploader.open();
					return;
				}

				// Create new media uploader
				self.mediaUploader = wp.media({
					title: sawLmsSection.i18n.selectDocuments,
					button: {
						text: sawLmsSection.i18n.useDocuments
					},
					library: {
						type: ['application/pdf', 
						       'application/msword', 
						       'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
						       'application/vnd.ms-powerpoint',
						       'application/vnd.openxmlformats-officedocument.presentationml.presentation',
						       'application/vnd.ms-excel',
						       'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
						       'application/zip',
						       'text/plain']
					},
					multiple: true
				});

				// When files are selected
				self.mediaUploader.on('select', function() {
					const selection = self.mediaUploader.state().get('selection');
					
					selection.each(function(attachment) {
						attachment = attachment.toJSON();
						self.addDocument(attachment);
					});

					self.showSuccess(sawLmsSection.i18n.documentsUploaded);
				});

				// Open media uploader
				self.mediaUploader.open();
			});
		},

		/**
		 * Add document to the list
		 *
		 * @param {Object} attachment WordPress attachment object
		 */
		addDocument: function(attachment) {
			const self = this;

			// Check if already added
			if (self.selectedDocuments.includes(attachment.id)) {
				console.log('SAW LMS: Document already added:', attachment.id);
				return;
			}

			// Add to array
			self.selectedDocuments.push(attachment.id);

			// Hide "no documents" message
			$('.saw-lms-no-documents').hide();

			// Ensure documents list exists
			if (!$('.saw-lms-documents-list').length) {
				$('#saw_section_documents_container').prepend('<ul class="saw-lms-documents-list" style="margin-bottom: 15px;"></ul>');
			}

			// Get file size in readable format
			const fileSize = self.formatFileSize(attachment.filesizeInBytes || 0);

			// Get file name
			const fileName = attachment.filename || attachment.title;

			// Create document item HTML
			const documentHtml = `
				<li class="saw-lms-document-item" data-id="${attachment.id}" style="padding: 10px; background: #f9f9f9; border: 1px solid #ddd; margin-bottom: 5px; border-radius: 4px;">
					<span class="dashicons dashicons-media-document" style="color: #2196F3;"></span>
					<strong>${fileName}</strong>
					<span class="saw-lms-file-size" style="color: #666; font-size: 12px;">(${fileSize})</span>
					<a href="${attachment.url}" target="_blank" class="button button-small" style="margin-left: 10px;">
						${sawLmsSection.i18n.view || 'View'}
					</a>
					<button type="button" class="button button-small saw-lms-remove-document" data-id="${attachment.id}" style="margin-left: 5px; color: #d63638;">
						${sawLmsSection.i18n.remove || 'Remove'}
					</button>
					<input type="hidden" name="saw_section_documents[]" value="${attachment.id}" />
				</li>
			`;

			// Append to list
			$('.saw-lms-documents-list').append(documentHtml);

			// Rebind removal event
			self.setupDocumentRemoval();

			console.log('SAW LMS: Document added:', attachment.id, fileName);
		},

		/**
		 * Setup document removal
		 */
		setupDocumentRemoval: function() {
			const self = this;

			$('.saw-lms-remove-document').off('click').on('click', function(e) {
				e.preventDefault();

				const $button = $(this);
				const docId = $button.data('id');

				// Confirm removal
				if (!confirm(sawLmsSection.i18n.confirmRemove)) {
					return;
				}

				// Remove from array
				self.selectedDocuments = self.selectedDocuments.filter(function(id) {
					return id !== docId;
				});

				// Remove from DOM
				$button.closest('.saw-lms-document-item').fadeOut(300, function() {
					$(this).remove();

					// Show "no documents" message if empty
					if (!$('.saw-lms-document-item').length) {
						if (!$('.saw-lms-no-documents').length) {
							$('#saw_section_documents_container').prepend(
								'<p class="saw-lms-no-documents" style="color: #666; font-style: italic;">' +
								sawLmsSection.i18n.noDocuments +
								'</p>'
							);
						} else {
							$('.saw-lms-no-documents').show();
						}
					}
				});

				console.log('SAW LMS: Document removed:', docId);
			});
		},

		/**
		 * Setup video URL validation
		 */
		setupVideoValidation: function() {
			$('#saw_section_video_url').on('blur', function() {
				const url = $(this).val().trim();

				if (url && !SAW_Section_MetaBox.isValidUrl(url)) {
					alert('Please enter a valid URL (e.g., https://www.youtube.com/watch?v=...)');
					$(this).focus();
				}
			});
		},

		/**
		 * Validate URL
		 *
		 * @param {string} url URL to validate
		 * @return {boolean} True if valid
		 */
		isValidUrl: function(url) {
			try {
				new URL(url);
				return true;
			} catch (e) {
				return false;
			}
		},

		/**
		 * Format file size to human readable
		 *
		 * @param {number} bytes File size in bytes
		 * @return {string} Formatted size
		 */
		formatFileSize: function(bytes) {
			if (bytes === 0) return '0 Bytes';

			const k = 1024;
			const sizes = ['Bytes', 'KB', 'MB', 'GB'];
			const i = Math.floor(Math.log(bytes) / Math.log(k));

			return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
		},

		/**
		 * Show success message
		 *
		 * @param {string} message Message to display
		 */
		showSuccess: function(message) {
			const $msg = $('<div class="saw-lms-upload-success">' + message + '</div>');
			$('#saw_section_documents_container').append($msg);

			setTimeout(function() {
				$msg.fadeOut(300, function() {
					$(this).remove();
				});
			}, 3000);
		},

		/**
		 * Show error message
		 *
		 * @param {string} message Message to display
		 */
		showError: function(message) {
			const $msg = $('<div class="saw-lms-upload-error">' + message + '</div>');
			$('#saw_section_documents_container').append($msg);

			setTimeout(function() {
				$msg.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
		}
	};

	/**
	 * Initialize when DOM is ready
	 */
	$(document).ready(function() {
		// Check if we're on section edit screen
		if ($('#saw_section_upload_documents').length) {
			SAW_Section_MetaBox.init();
			console.log('SAW LMS: Section meta box initialized');
		}
	});

	// Expose to global scope if needed
	window.SAW_Section_MetaBox = SAW_Section_MetaBox;

})(jQuery);