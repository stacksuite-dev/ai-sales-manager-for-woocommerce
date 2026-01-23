/**
 * Email Templates Page JavaScript
 *
 * Handles state management, AJAX operations, live preview, and UI interactions
 * for the dedicated email templates management page.
 *
 * @package AISales_Sales_Manager
 */

(function ($) {
	'use strict';

	// State management
	const state = {
		view: 'list', // 'list' or 'editor'
		currentTemplate: null,
		currentDevice: 'mobile',
		isDirty: false,
		isLoading: false,
		previewDebounceTimer: null,
		templates: window.aisalesEmailData?.templates || {},
		placeholders: window.aisalesEmailData?.placeholders || {},
	};

	// DOM elements cache
	const elements = {};

	/**
	 * Initialize the page
	 */
	function init() {
		cacheElements();
		bindEvents();
		updateBalanceState();
	}

	/**
	 * Cache DOM elements for performance
	 */
	function cacheElements() {
		elements.listView = $('#aisales-email-list-view');
		elements.editorView = $('#aisales-email-editor-view');
		elements.loading = $('#aisales-email-loading');
		elements.loadingMessage = $('#aisales-loading-message');

		// Editor elements
		elements.editorForm = $('#aisales-email-editor-form');
		elements.typeInput = $('#aisales-editor-type-input');
		elements.typeBadge = $('#aisales-editor-type-badge');
		elements.editorStatus = $('#aisales-editor-status');
		elements.nameInput = $('#aisales-email-name');
		elements.subjectInput = $('#aisales-email-subject');
		elements.headingInput = $('#aisales-email-heading');
		elements.contentInput = $('#aisales-email-content');
		elements.improvePrompt = $('#aisales-email-improve-prompt');

		// Preview elements
		elements.previewSubject = $('#aisales-preview-subject');
		elements.previewWrapper = $('#aisales-preview-wrapper');
		elements.previewIframe = $('#aisales-preview-iframe');
		elements.deviceButtons = $('.aisales-device-btn');

		// Resizer
		elements.resizer = $('#aisales-editor-resizer');
		elements.formPane = $('.aisales-email-editor__form-pane');

		// Placeholders
		elements.placeholderPicker = $('#aisales-placeholder-picker');
		elements.placeholdersList = $('#aisales-placeholders-list');
		elements.placeholdersToggle = $('#aisales-placeholders-toggle');

		// Test email modal
		elements.testModalOverlay = $('#aisales-email-test-modal-overlay');
		elements.testModal = $('#aisales-email-test-modal');
		elements.testModalClose = $('#aisales-email-test-modal-close');
		elements.testModalCancel = $('#aisales-email-test-cancel');
		elements.testModalSend = $('#aisales-email-test-send');
		elements.testRecipient = $('#aisales-email-test-recipient');
	}

	/**
	 * Bind event handlers
	 */
	function bindEvents() {
		// List view actions
		$(document).on('click', '.aisales-email-generate-btn', handleGenerate);
		$(document).on('click', '.aisales-email-edit-btn', handleEdit);
		$(document).on('change', '.aisales-email-toggle', handleToggle);
		$('#aisales-generate-all-btn').on('click', handleGenerateAll);

		// Editor navigation
		$('#aisales-email-back-btn').on('click', handleBack);
		$('#aisales-email-delete-btn').on('click', handleDelete);
		$('#aisales-email-test-btn').on('click', openTestModal);

		// Editor actions
		$('#aisales-email-save-draft').on('click', () => saveTemplate('draft'));
		$('#aisales-email-save-activate').on('click', () => saveTemplate('active'));
		$('#aisales-email-improve-btn').on('click', handleImprove);

		// Regenerate buttons
		$(document).on('click', '.aisales-regenerate-btn', handleRegenerate);

		// Live preview on input change
		elements.subjectInput.on('input', debouncePreview);
		elements.headingInput.on('input', debouncePreview);
		elements.contentInput.on('input', debouncePreview);

		// Track dirty state
		elements.editorForm.on('input change', () => {
			state.isDirty = true;
		});

		// Device switcher
		elements.deviceButtons.on('click', handleDeviceSwitch);

		// Resizer
		elements.resizer.on('mousedown', initResize);

		// Placeholder picker
		$(document).on('click', '.aisales-placeholder-insert-btn', showPlaceholderPicker);
		$(document).on('click', '.aisales-placeholder-picker__item', insertPlaceholder);
		$(document).on('click', '.aisales-placeholder-chip', insertPlaceholderFromChip);
		elements.placeholdersToggle.on('click', togglePlaceholdersList);

		// Close placeholder picker on outside click
		$(document).on('click', function (e) {
			if (!$(e.target).closest('.aisales-placeholder-picker, .aisales-placeholder-insert-btn').length) {
				elements.placeholderPicker.hide();
			}
		});

		// Brand Settings button
		$('#aisales-brand-settings-btn').on('click', function () {
			if (window.aisalesEmailWizard) {
				window.aisalesEmailWizard.open(null, true); // true = settings mode (only Step 1)
			}
		});

		// Warn before leaving with unsaved changes
		$(window).on('beforeunload', function () {
			if (state.isDirty && state.view === 'editor') {
				return 'You have unsaved changes. Are you sure you want to leave?';
			}
		});

		// Test email modal events
		elements.testModalClose.on('click', closeTestModal);
		elements.testModalCancel.on('click', closeTestModal);
		elements.testModalOverlay.on('click', closeTestModal);
		elements.testModalSend.on('click', handleSendTestEmail);

		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' && elements.testModal.hasClass('aisales-modal--active')) {
				closeTestModal();
			}
		});

		// Tab switching (Templates / Settings)
		$('.aisales-email-tab').on('click', handleTabSwitch);
	}

	/**
	 * Switch between list and editor views
	 */
	function switchView(view) {
		state.view = view;

		if (view === 'list') {
			elements.editorView.hide();
			elements.listView.show();
			state.currentTemplate = null;
			state.isDirty = false;
		} else {
			elements.listView.hide();
			elements.editorView.show();
		}
	}

	/**
	 * Handle generate button click
	 */
	async function handleGenerate(e) {
		const templateType = $(this).data('template-type');
		const $card = $(this).closest('.aisales-email-template-card');

		// Check if wizard should be shown first
		if (window.aisalesEmailWizard?.shouldShow()) {
			window.aisalesEmailWizard.open(templateType);
			return;
		}

		showLoading(aisalesEmail.i18n.generating);

		try {
			const response = await $.ajax({
				url: aisalesEmail.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_generate_email_template',
					nonce: aisalesEmail.nonce,
					template_type: templateType,
				},
			});

			if (response.success) {
				// Update state and UI
				state.templates[templateType] = response.data.template;
				updateBalance(response.data.balance);
				showNotice(aisalesEmail.i18n.templateGenerated, 'success');

				// Open editor with generated template
				openEditor(templateType, response.data.template);
			} else {
				showNotice(response.data?.message || aisalesEmail.i18n.error, 'error');
			}
		} catch (error) {
			showNotice(aisalesEmail.i18n.connectionError, 'error');
		} finally {
			hideLoading();
		}
	}

	/**
	 * Handle edit button click
	 */
	function handleEdit(e) {
		const templateType = $(this).data('template-type');
		const template = state.templates[templateType]?.template || state.templates[templateType];

		if (template) {
			openEditor(templateType, template);
		}
	}

	/**
	 * Handle toggle switch change
	 */
	async function handleToggle(e) {
		const $toggle = $(this);
		const templateType = $toggle.data('template-type');
		const isActive = $toggle.is(':checked');

		try {
			const response = await $.ajax({
				url: aisalesEmail.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_toggle_email_template',
					nonce: aisalesEmail.nonce,
					template_type: templateType,
					enabled: isActive ? 1 : 0,
				},
			});

			if (response.success) {
				// Update status badge
				const $card = $toggle.closest('.aisales-email-template-card');
				const $status = $card.find('.aisales-status-badge');

				if (isActive) {
					$status
						.removeClass('aisales-status-badge--draft')
						.addClass('aisales-status-badge--active')
						.html('<span class="dashicons dashicons-yes-alt"></span> ' + aisalesEmail.i18n.active);
				} else {
					$status
						.removeClass('aisales-status-badge--active')
						.addClass('aisales-status-badge--draft')
						.html('<span class="dashicons dashicons-edit"></span> ' + aisalesEmail.i18n.draft);
				}

				updateStats();
				showNotice(isActive ? aisalesEmail.i18n.templateActivated : 'Template deactivated', 'success');
			} else {
				// Revert toggle
				$toggle.prop('checked', !isActive);
				showNotice(response.data?.message || aisalesEmail.i18n.error, 'error');
			}
		} catch (error) {
			$toggle.prop('checked', !isActive);
			showNotice(aisalesEmail.i18n.connectionError, 'error');
		}
	}

	/**
	 * Handle generate all button click
	 */
	async function handleGenerateAll() {
		// Check if wizard should be shown first
		if (window.aisalesEmailWizard?.shouldShow()) {
			window.aisalesEmailWizard.open(null); // null = generate all mode
			return;
		}

		const $btn = $('#aisales-generate-all-btn');
		const missingTypes = [];

		// Find templates that need generation
		$('.aisales-email-template-card').each(function () {
			const $card = $(this);
			const templateType = $card.data('template-type');
			const hasTemplate = state.templates[templateType]?.has_template || state.templates[templateType]?.template;

			if (!hasTemplate) {
				missingTypes.push(templateType);
			}
		});

		if (missingTypes.length === 0) {
			showNotice('All templates already exist!', 'info');
			return;
		}

		$btn.prop('disabled', true);
		let generated = 0;

		for (const templateType of missingTypes) {
			showLoading(`Generating ${templateType.replace('_', ' ')}... (${generated + 1}/${missingTypes.length})`);

			try {
				const response = await $.ajax({
					url: aisalesEmail.ajaxUrl,
					type: 'POST',
					data: {
						action: 'aisales_generate_email_template',
						nonce: aisalesEmail.nonce,
						template_type: templateType,
					},
				});

				if (response.success) {
					state.templates[templateType] = response.data.template;
					updateBalance(response.data.balance);
					generated++;
				}
			} catch (error) {
				console.error('Generate error for', templateType, error);
			}
		}

		hideLoading();
		$btn.prop('disabled', false);

		if (generated > 0) {
			showNotice(`Generated ${generated} template(s)!`, 'success');
			// Refresh the page to show updated templates
			location.reload();
		} else {
			showNotice('Failed to generate templates', 'error');
		}
	}

	/**
	 * Open the editor view for a template
	 */
	function openEditor(templateType, template) {
		state.currentTemplate = templateType;

		// Get template type info
		const templateInfo = state.templates[templateType];
		const label = templateInfo?.label || templateType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

		// Populate form
		elements.typeInput.val(templateType);
		elements.typeBadge.text(label);
		elements.nameInput.val(template?.name || '');
		elements.subjectInput.val(template?.subject || '');
		elements.headingInput.val(template?.heading || '');
		elements.contentInput.val(template?.content || '');

		// Update status indicator
		const status = template?.status || 'draft';
		elements.editorStatus.text(status === 'active' ? aisalesEmail.i18n.active : aisalesEmail.i18n.draft)
			.removeClass('aisales-status--active aisales-status--draft')
			.addClass('aisales-status--' + status);

		// Switch to editor view
		switchView('editor');

		// Initialize preview
		updatePreview();

		// Reset dirty state after populating
		setTimeout(() => {
			state.isDirty = false;
		}, 100);
	}

	/**
	 * Handle back button click
	 */
	function handleBack() {
		if (state.isDirty) {
			if (!confirm('You have unsaved changes. Are you sure you want to leave?')) {
				return;
			}
		}

		switchView('list');
	}

	/**
	 * Handle delete button click
	 */
	async function handleDelete() {
		if (!state.currentTemplate) return;

		if (!confirm(aisalesEmail.i18n.confirmDelete)) {
			return;
		}

		showLoading(aisalesEmail.i18n.deleting);

		try {
			const response = await $.ajax({
				url: aisalesEmail.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_delete_email_template',
					nonce: aisalesEmail.nonce,
					template_type: state.currentTemplate,
				},
			});

			if (response.success) {
				showNotice(aisalesEmail.i18n.templateDeleted, 'success');
				state.isDirty = false;
				// Refresh page to update list
				location.reload();
			} else {
				showNotice(response.data?.message || aisalesEmail.i18n.error, 'error');
			}
		} catch (error) {
			showNotice(aisalesEmail.i18n.connectionError, 'error');
		} finally {
			hideLoading();
		}
	}

	/**
	 * Save template (draft or active)
	 */
	async function saveTemplate(status) {
		if (!state.currentTemplate) return;

		showLoading(aisalesEmail.i18n.saving);

		try {
			const response = await $.ajax({
				url: aisalesEmail.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_save_email_template',
					nonce: aisalesEmail.nonce,
					template_type: state.currentTemplate,
					name: elements.nameInput.val(),
					subject: elements.subjectInput.val(),
					heading: elements.headingInput.val(),
					content: elements.contentInput.val(),
					status: status,
				},
			});

			if (response.success) {
				state.isDirty = false;
				state.templates[state.currentTemplate] = {
					...state.templates[state.currentTemplate],
					template: {
						name: elements.nameInput.val(),
						subject: elements.subjectInput.val(),
						heading: elements.headingInput.val(),
						content: elements.contentInput.val(),
						status: status,
					},
					has_template: true,
					is_active: status === 'active',
				};

				elements.editorStatus.text(status === 'active' ? aisalesEmail.i18n.active : aisalesEmail.i18n.draft);

				showNotice(aisalesEmail.i18n.templateSaved, 'success');

				// Return to list view if activating
				if (status === 'active') {
					location.reload();
				}
			} else {
				showNotice(response.data?.message || aisalesEmail.i18n.error, 'error');
			}
		} catch (error) {
			showNotice(aisalesEmail.i18n.connectionError, 'error');
		} finally {
			hideLoading();
		}
	}

	/**
	 * Handle improve with AI
	 */
	async function handleImprove() {
		const prompt = elements.improvePrompt.val().trim();
		if (!prompt) {
			showNotice('Please enter an improvement prompt', 'warning');
			return;
		}

		showLoading('Improving template...');

		try {
			const response = await $.ajax({
				url: aisalesEmail.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_generate_email_template',
					nonce: aisalesEmail.nonce,
					template_type: state.currentTemplate,
					improvement_prompt: prompt,
					current_template: {
						subject: elements.subjectInput.val(),
						heading: elements.headingInput.val(),
						content: elements.contentInput.val(),
					},
				},
			});

			if (response.success) {
				const template = response.data.template;
				elements.subjectInput.val(template.subject || '');
				elements.headingInput.val(template.heading || '');
				elements.contentInput.val(template.content || '');
				elements.improvePrompt.val('');

				updateBalance(response.data.balance);
				updatePreview();
				state.isDirty = true;

				showNotice('Template improved!', 'success');
			} else {
				showNotice(response.data?.message || aisalesEmail.i18n.error, 'error');
			}
		} catch (error) {
			showNotice(aisalesEmail.i18n.connectionError, 'error');
		} finally {
			hideLoading();
		}
	}

	/**
	 * Handle regenerate button click
	 */
	async function handleRegenerate(e) {
		const field = $(this).data('field');
		const $btn = $(this);

		$btn.find('.dashicons').addClass('aisales-spin');

		try {
			const response = await $.ajax({
				url: aisalesEmail.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_generate_email_template',
					nonce: aisalesEmail.nonce,
					template_type: state.currentTemplate,
					regenerate_field: field,
				},
			});

			if (response.success) {
				const template = response.data.template;

				if (field === 'subject' && template.subject) {
					elements.subjectInput.val(template.subject);
				} else if (field === 'heading' && template.heading) {
					elements.headingInput.val(template.heading);
				} else if (field === 'content' && template.content) {
					elements.contentInput.val(template.content);
				}

				updateBalance(response.data.balance);
				updatePreview();
				state.isDirty = true;
			} else {
				showNotice(response.data?.message || aisalesEmail.i18n.error, 'error');
			}
		} catch (error) {
			showNotice(aisalesEmail.i18n.connectionError, 'error');
		} finally {
			$btn.find('.dashicons').removeClass('aisales-spin');
		}
	}

	/**
	 * Debounce preview update
	 */
	function debouncePreview() {
		clearTimeout(state.previewDebounceTimer);
		state.previewDebounceTimer = setTimeout(updatePreview, 300);
	}

	/**
	 * Update live preview
	 */
	async function updatePreview() {
		const subject = elements.subjectInput.val();
		const heading = elements.headingInput.val();
		const content = elements.contentInput.val();

		// Update subject preview
		elements.previewSubject.text(replacePlaceholdersWithSample(subject) || 'Your subject line will appear here...');

		// Get preview HTML from server
		try {
			const response = await $.ajax({
				url: aisalesEmail.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_preview_email_template',
					nonce: aisalesEmail.nonce,
					template_type: state.currentTemplate,
					subject: subject,
					heading: heading,
					content: content,
				},
			});

			if (response.success && response.data?.preview?.html) {
				const iframe = elements.previewIframe[0];
				const doc = iframe.contentDocument || iframe.contentWindow.document;
				doc.open();
				doc.write(response.data.preview.html);
				doc.close();
			}
		} catch (error) {
			// Preview errors are silent to avoid disrupting the editing experience
		}
	}

	/**
	 * Open test email modal
	 */
	function openTestModal() {
		if (!state.currentTemplate) {
			return;
		}

		const defaultEmail = aisalesEmail.adminEmail || '';
		if (!elements.testRecipient.val()) {
			elements.testRecipient.val(defaultEmail);
		}

		elements.testModalOverlay.addClass('aisales-modal-overlay--active');
		elements.testModal.addClass('aisales-modal--active');
		$('body').addClass('aisales-modal-open');
		setTimeout(() => elements.testRecipient.trigger('focus'), 50);
	}

	/**
	 * Close test email modal
	 */
	function closeTestModal() {
		elements.testModalOverlay.removeClass('aisales-modal-overlay--active');
		elements.testModal.removeClass('aisales-modal--active');
		$('body').removeClass('aisales-modal-open');
	}

	/**
	 * Send test email
	 */
	async function handleSendTestEmail() {
		const recipient = (elements.testRecipient.val() || '').trim();
		if (!recipient || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(recipient)) {
			showNotice(aisalesEmail.i18n.invalidEmail, 'error');
			return;
		}

		elements.testModalSend.prop('disabled', true);
		showLoading(aisalesEmail.i18n.sendingTest);

		try {
			const response = await $.ajax({
				url: aisalesEmail.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_send_test_email',
					nonce: aisalesEmail.nonce,
					template_type: state.currentTemplate,
					recipient: recipient,
					subject: elements.subjectInput.val(),
					heading: elements.headingInput.val(),
					content: elements.contentInput.val(),
				},
			});

			if (response.success) {
				showNotice(aisalesEmail.i18n.testSendSuccess, 'success');
				closeTestModal();
			} else {
				showNotice(response.data?.message || aisalesEmail.i18n.error, 'error');
			}
		} catch (error) {
			showNotice(aisalesEmail.i18n.connectionError, 'error');
		} finally {
			elements.testModalSend.prop('disabled', false);
			hideLoading();
		}
	}

	/**
	 * Replace placeholders with sample data for preview
	 */
	function replacePlaceholdersWithSample(text) {
		if (!text) return '';

		const sampleData = {
			'{customer_name}': 'Sarah Johnson',
			'{customer_first_name}': 'Sarah',
			'{customer_last_name}': 'Johnson',
			'{customer_email}': 'sarah@example.com',
			'{order_number}': '#1234',
			'{order_date}': new Date().toLocaleDateString(),
			'{order_total}': '$124.97',
			'{store_name}': 'Your Store',
			'{tracking_number}': '1Z999AA10123456784',
		};

		let result = text;
		Object.keys(sampleData).forEach(placeholder => {
			result = result.replace(new RegExp(placeholder.replace(/[{}]/g, '\\$&'), 'g'), sampleData[placeholder]);
		});

		return result;
	}

	/**
	 * Handle device switch
	 */
	function handleDeviceSwitch(e) {
		const $btn = $(this);
		const device = $btn.data('device');

		elements.deviceButtons.removeClass('aisales-device-btn--active');
		$btn.addClass('aisales-device-btn--active');

		elements.previewWrapper.attr('data-device', device);
		state.currentDevice = device;
	}

	/**
	 * Initialize resizer drag
	 */
	function initResize(e) {
		e.preventDefault();

		const startX = e.clientX;
		const startWidth = elements.formPane.width();

		elements.resizer.addClass('is-dragging');

		$(document).on('mousemove.resize', function (e) {
			const diff = e.clientX - startX;
			const newWidth = Math.max(380, Math.min(startWidth + diff, window.innerWidth * 0.6));
			elements.formPane.css('width', newWidth + 'px');
		});

		$(document).on('mouseup.resize', function () {
			elements.resizer.removeClass('is-dragging');
			$(document).off('mousemove.resize mouseup.resize');
		});
	}

	/**
	 * Show placeholder picker dropdown
	 */
	function showPlaceholderPicker(e) {
		const $btn = $(this);
		const targetId = $btn.data('target');

		state.currentPlaceholderTarget = targetId;

		// Position picker near button
		const btnOffset = $btn.offset();
		const btnHeight = $btn.outerHeight();

		elements.placeholderPicker.css({
			top: btnOffset.top + btnHeight + 5,
			left: btnOffset.left - elements.placeholderPicker.outerWidth() + $btn.outerWidth(),
		}).show();
	}

	/**
	 * Insert placeholder from picker
	 */
	function insertPlaceholder(e) {
		const placeholder = $(this).data('placeholder');
		insertPlaceholderToTarget(placeholder);
		elements.placeholderPicker.hide();
	}

	/**
	 * Insert placeholder from chip
	 */
	function insertPlaceholderFromChip(e) {
		const placeholder = $(this).data('placeholder');

		// Determine target - prefer content textarea if visible
		const activeElement = document.activeElement;
		if (activeElement && (activeElement.id === 'aisales-email-subject' ||
			activeElement.id === 'aisales-email-heading' ||
			activeElement.id === 'aisales-email-content')) {
			insertAtCursor(activeElement, placeholder);
		} else {
			// Default to content
			insertAtCursor(elements.contentInput[0], placeholder);
		}

		debouncePreview();
	}

	/**
	 * Insert placeholder at current cursor position
	 */
	function insertPlaceholderToTarget(placeholder) {
		const targetId = state.currentPlaceholderTarget;
		if (!targetId) return;

		const $target = $('#' + targetId);
		if (!$target.length) return;

		insertAtCursor($target[0], placeholder);
		debouncePreview();
	}

	/**
	 * Insert text at cursor position
	 */
	function insertAtCursor(element, text) {
		const start = element.selectionStart;
		const end = element.selectionEnd;
		const value = element.value;

		element.value = value.substring(0, start) + text + value.substring(end);
		element.selectionStart = element.selectionEnd = start + text.length;
		element.focus();

		// Trigger input event for live preview
		$(element).trigger('input');
	}

	/**
	 * Toggle placeholders list visibility
	 */
	function togglePlaceholdersList() {
		const $toggle = elements.placeholdersToggle;
		const $list = elements.placeholdersList;

		$toggle.toggleClass('is-expanded');
		$list.slideToggle(200);
	}

	/**
	 * Show loading overlay
	 */
	function showLoading(message) {
		state.isLoading = true;
		elements.loadingMessage.text(message || aisalesEmail.i18n.loading);
		elements.loading.show();
	}

	/**
	 * Hide loading overlay
	 */
	function hideLoading() {
		state.isLoading = false;
		elements.loading.hide();
	}

	/**
	 * Show admin notice
	 */
	function showNotice(message, type = 'info') {
		// Remove existing notices
		$('.aisales-email-notice').remove();

		const noticeClass = type === 'error' ? 'notice-error' :
			type === 'success' ? 'notice-success' :
				type === 'warning' ? 'notice-warning' : 'notice-info';

		const $notice = $(`
			<div class="notice ${noticeClass} is-dismissible aisales-email-notice">
				<p>${message}</p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text">Dismiss</span>
				</button>
			</div>
		`);

		$('.aisales-notices-anchor').after($notice);

		// Auto-dismiss after 5 seconds
		setTimeout(() => {
			$notice.fadeOut(300, function () {
				$(this).remove();
			});
		}, 5000);

		// Manual dismiss
		$notice.find('.notice-dismiss').on('click', function () {
			$notice.fadeOut(300, function () {
				$(this).remove();
			});
		});
	}

	/**
	 * Update balance display
	 */
	function updateBalance(newBalance) {
		if (typeof newBalance !== 'undefined') {
			aisalesEmail.balance = newBalance;
			$('#aisales-balance-display').text(newBalance.toLocaleString());
			updateBalanceState();
		}
	}

	/**
	 * Update balance indicator state (low/normal)
	 */
	function updateBalanceState() {
		const $indicator = $('.aisales-balance-indicator');
		$indicator.removeClass('aisales-balance-indicator--low');
	}

	/**
	 * Update stats counters
	 */
	function updateStats() {
		let active = 0, draft = 0, notCreated = 0;

		Object.keys(state.templates).forEach(type => {
			const template = state.templates[type];
			if (template.is_active) {
				active++;
			} else if (template.has_template) {
				draft++;
			} else {
				notCreated++;
			}
		});

		$('.aisales-email-stat--active .aisales-email-stat__count').text(active);
		$('.aisales-email-stat--draft .aisales-email-stat__count').text(draft);
		$('.aisales-email-stat--none .aisales-email-stat__count').text(notCreated);
	}

	/**
	 * Handle tab switching between Templates and Settings
	 */
	function handleTabSwitch(e) {
		var $tab = $(e.currentTarget);
		var tabId = $tab.data('tab');

		// Update tab states
		$('.aisales-email-tab').removeClass('aisales-email-tab--active');
		$tab.addClass('aisales-email-tab--active');

		// Update panel visibility
		$('.aisales-email-tab-panel').removeClass('aisales-email-tab-panel--active');
		$('.aisales-email-tab-panel[data-tab-panel="' + tabId + '"]').addClass('aisales-email-tab-panel--active');
	}

	// Initialize when document is ready
	$(document).ready(init);

	// CSS for spinning animation
	$('<style>')
		.text('.aisales-spin { animation: aisales-spin 0.8s linear infinite; }')
		.appendTo('head');

})(jQuery);
