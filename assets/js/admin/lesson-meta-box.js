/**
 * Lesson Meta Box JavaScript
 *
 * Handles conditional field display and document uploads for lesson editing.
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/assets/js/admin
 * @since      2.1.0
 * @version    2.1.1
 */

(function($) {
	'use strict';

	/**
	 * Lesson Meta Box Handler
	 */
	const LessonMetaBox = {
		/**
		 * Initialize
		 */
		init: function() {
			this.setupConditionalFields();
			this.setupDocumentUpload();
			this.setupValidation();
		},

		/**
		 * Setup conditional field visibility based on lesson type
		 */
		setupConditionalFields: function() {
			const $lessonType = $('#saw_lms_lesson_type');
			const self = this;

			// Toggle on page load
			self.toggleContentSections($lessonType.val());

			// Toggle on change
			$lessonType.on('change', function() {
				self.toggleContentSections($(this).val());
			});
		},

		/**
		 * Toggle content sections based on lesson type
		 *
		 * @param {string} lessonType Selected lesson type
		 */
		toggleContentSections: function(lessonType) {
			// Hide all content sections
			$('.saw-lms-content-section').hide();

			// Show relevant section based on type
			switch(lessonType) {
				case 'video':
					$('.saw-lms-video-content').show();
					break;
				case 'text':
					$('.saw-lms-text-content').show();
					break;
				case 'document':
					$('.saw-lms-document-content').show();
					break;
				case 'assignment':
					$('.saw-lms-assignment-content').show();
					break;
			}

			// Visual feedback
			this.highlightActiveSection(lessonType);
		},

		/**
		 * Highlight active content section
		 *
		 * @param {string} lessonType Selected lesson type
		 */
		highlightActiveSection: function(lessonType) {
			const $metaBox = $('#saw_lms_lesson_content');
			const typeLabels = {
				'video': '🎥 Video',
				'text': '📝 Text',
				'document': '📄 Document',
				'assignment': '✍️ Assignment'
			};

			// Update meta box title to show active type
			const $title = $metaBox.find('h2.hndle span');
			if ($title.length) {
				const baseTitle = 'Lesson Content';
				const label = typeLabels[lessonType] || '';
				$title.text(baseTitle + (label ? ' - ' + label : ''));
			}
		},

		/**
		 * Setup document upload functionality
		 */
		setupDocumentUpload: function() {
			const self = this;
			let mediaUploader;

			// Upload button click handler
			$(document).on('click', '.saw-lms-upload-document', function(e) {
				e.preventDefault();

				const $button = $(this);

				// If media uploader already exists, open it
				if (mediaUploader) {
					mediaUploader.open();
					return;
				}

				// Create new media uploader
				mediaUploader = wp.media({
					title: sawLmsLesson.i18n.selectDocument,
					button: {
						text: sawLmsLesson.i18n.useDocument
					},
					library: {
						type: ['application/pdf', 'application/msword', 
						       'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
						       'application/vnd.ms-powerpoint',
						       'application/vnd.openxmlformats-officedocument.presentationml.presentation',
						       'application/zip']
					},
					multiple: false
				});

				// When file is selected
				mediaUploader.on('select', function() {
					const attachment = mediaUploader.state().get('selection').first().toJSON();
					
					// Set document URL
					$('#saw_lms_document_url').val(attachment.url);

					// Visual feedback
					self.showSuccess(sawLmsLesson.i18n.documentUploaded);

					// Update button text
					$button.text('✓ ' + $button.text().replace('✓ ', ''));
					setTimeout(function() {
						$button.text($button.text().replace('✓ ', ''));
					}, 2000);
				});

				// Open media uploader
				mediaUploader.open();
			});
		},

		/**
		 * Setup form validation
		 */
		setupValidation: function() {
			const self = this;

			// Validate on form submit
			$('#post').on('submit', function(e) {
				const $lessonType = $('#saw_lms_lesson_type').val();
				const $sectionId = $('#saw_lms_section_id').val();
				let isValid = true;
				let errors = [];

				// Section is required
				if (!$sectionId || $sectionId === '') {
					errors.push('Please select a parent section for this lesson.');
					isValid = false;
				}

				// Type-specific validation
				switch($lessonType) {
					case 'video':
						const videoUrl = $('#saw_lms_video_url').val().trim();
						if (!videoUrl) {
							errors.push('Video URL is required for video lessons.');
							isValid = false;
						}
						break;

					case 'document':
						const documentUrl = $('#saw_lms_document_url').val().trim();
						if (!documentUrl) {
							errors.push('Document file is required for document lessons.');
							isValid = false;
						}
						break;

					case 'assignment':
						const maxPoints = parseInt($('#saw_lms_assignment_max_points').val());
						const passingPoints = parseInt($('#saw_lms_assignment_passing_points').val());
						
						if (passingPoints > maxPoints) {
							errors.push('Passing points cannot be greater than maximum points.');
							isValid = false;
						}
						break;
				}

				// Show errors if validation fails
				if (!isValid) {
					e.preventDefault();
					self.showError(errors.join('<br>'));
					return false;
				}

				return true;
			});
		},

		/**
		 * Show success message
		 *
		 * @param {string} message Success message
		 */
		showSuccess: function(message) {
			this.showNotice(message, 'success');
		},

		/**
		 * Show error message
		 *
		 * @param {string} message Error message
		 */
		showError: function(message) {
			this.showNotice(message, 'error');
		},

		/**
		 * Show admin notice
		 *
		 * @param {string} message Notice message
		 * @param {string} type    Notice type (success, error, warning, info)
		 */
		showNotice: function(message, type) {
			type = type || 'info';

			// Create notice element
			const $notice = $('<div>')
				.addClass('notice notice-' + type + ' is-dismissible')
				.html('<p>' + message + '</p>');

			// Add dismiss button
			$notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>');

			// Insert after h1
			$('.wrap h1').after($notice);

			// Auto dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);

			// Handle manual dismiss
			$notice.find('.notice-dismiss').on('click', function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			});

			// Scroll to notice
			$('html, body').animate({
				scrollTop: $notice.offset().top - 32
			}, 300);
		}
	};

	/**
	 * Initialize when DOM is ready
	 */
	$(document).ready(function() {
		// Only run on lesson edit page
		if ($('#saw_lms_lesson_type').length) {
			LessonMetaBox.init();
		}
	});

})(jQuery);