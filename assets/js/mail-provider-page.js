/**
 * Mail Provider Settings Page
 *
 * @package AISales_Sales_Manager
 */
(function($) {
	'use strict';

	const MailProvider = {
		init: function() {
			this.cacheElements();
			this.bindEvents();
			this.syncProviderState();
			this.applyProviderSelection();
		},

		cacheElements: function() {
			this.$enabledToggle = $('#aisales-mail-provider-enabled');
			this.$settingsWrapper = $('#aisales-mail-provider-settings');
			this.$providerInputs = $('input[name="aisales-mail-provider"]');
			this.$panels = $('.aisales-mail-provider-panel');
			this.$authToggle = $('#aisales-mail-provider-auth');
			this.$saveButton = $('#aisales-mail-provider-save');
			this.$testButton = $('#aisales-mail-provider-test');

			this.$host = $('#aisales-mail-provider-host');
			this.$port = $('#aisales-mail-provider-port');
			this.$encryption = $('#aisales-mail-provider-encryption');
			this.$username = $('#aisales-mail-provider-username');
			this.$password = $('#aisales-mail-provider-password');
			this.$fromName = $('#aisales-mail-provider-from-name');
			this.$fromEmail = $('#aisales-mail-provider-from-email');

			this.$sendgridKey = $('#aisales-mail-provider-sendgrid-key');
			this.$sendgridFromEmail = $('#aisales-mail-provider-sendgrid-from-email');
			this.$sendgridFromName = $('#aisales-mail-provider-sendgrid-from-name');

			this.$resendKey = $('#aisales-mail-provider-resend-key');
			this.$resendDomain = $('#aisales-mail-provider-resend-domain');
			this.$resendFromEmail = $('#aisales-mail-provider-resend-from-email');
			this.$resendFromName = $('#aisales-mail-provider-resend-from-name');

			this.$mailgunKey = $('#aisales-mail-provider-mailgun-key');
			this.$mailgunDomain = $('#aisales-mail-provider-mailgun-domain');
			this.$mailgunRegion = $('#aisales-mail-provider-mailgun-region');
			this.$mailgunFromEmail = $('#aisales-mail-provider-mailgun-from-email');
			this.$mailgunFromName = $('#aisales-mail-provider-mailgun-from-name');

			this.$postmarkToken = $('#aisales-mail-provider-postmark-token');
			this.$postmarkFromEmail = $('#aisales-mail-provider-postmark-from-email');
			this.$postmarkFromName = $('#aisales-mail-provider-postmark-from-name');

			this.$sesAccess = $('#aisales-mail-provider-ses-access');
			this.$sesSecret = $('#aisales-mail-provider-ses-secret');
			this.$sesRegion = $('#aisales-mail-provider-ses-region');
			this.$sesFromEmail = $('#aisales-mail-provider-ses-from-email');
			this.$sesFromName = $('#aisales-mail-provider-ses-from-name');

			this.$modalOverlay = $('#aisales-mail-provider-test-overlay');
			this.$modal = $('#aisales-mail-provider-test');
			this.$modalClose = $('#aisales-mail-provider-test-close');
			this.$modalCancel = $('#aisales-mail-provider-test-cancel');
			this.$modalSend = $('#aisales-mail-provider-test-send');
			this.$modalRecipient = $('#aisales-mail-provider-test-recipient');
		},

		bindEvents: function() {
			this.$enabledToggle.on('change', this.syncEnabledState.bind(this));
			this.$providerInputs.on('change', this.syncProviderState.bind(this));
			this.$providerInputs.on('change', this.applyProviderSelection.bind(this));
			this.$authToggle.on('change', this.syncAuthState.bind(this));
			this.$saveButton.on('click', this.handleSave.bind(this));
			this.$testButton.on('click', this.openTestModal.bind(this));

			this.$modalClose.on('click', this.closeTestModal.bind(this));
			this.$modalCancel.on('click', this.closeTestModal.bind(this));
			this.$modalOverlay.on('click', this.closeTestModal.bind(this));
			this.$modalSend.on('click', this.handleSendTest.bind(this));

			$(document).on('keydown', (event) => {
				if (event.key === 'Escape' && this.$modal.hasClass('aisales-modal--active')) {
					this.closeTestModal();
				}
			});
		},

		syncEnabledState: function() {
			const enabled = this.$enabledToggle.is(':checked');
			this.$settingsWrapper.toggleClass('is-disabled', !enabled);
		},

		syncProviderState: function() {
			const provider = this.getProvider();
			this.$panels.addClass('is-hidden');
			this.$panels.filter(`[data-provider="${provider}"]`).removeClass('is-hidden');
			this.syncAuthState();
		},

		syncAuthState: function() {
			const authEnabled = this.$authToggle.is(':checked');
			this.$username.prop('disabled', !authEnabled);
			this.$password.prop('disabled', !authEnabled);
		},

		applyProviderSelection: function() {
			this.$providerInputs.closest('.aisales-mail-provider-option').removeClass('is-selected');
			this.$providerInputs.filter(':checked').closest('.aisales-mail-provider-option').addClass('is-selected');
		},

		getProvider: function() {
			return this.$providerInputs.filter(':checked').val() || 'default';
		},

		getSettingsPayload: function() {
			return {
				enabled: this.$enabledToggle.is(':checked'),
				provider: this.getProvider(),
				smtp: {
					host: this.$host.val().trim(),
					port: parseInt(this.$port.val(), 10) || 587,
					encryption: this.$encryption.val(),
					auth: this.$authToggle.is(':checked'),
					username: this.$username.val().trim(),
					password: this.$password.val(),
					from_email: this.$fromEmail.val().trim(),
					from_name: this.$fromName.val().trim(),
				},
				sendgrid: {
					api_key: this.$sendgridKey.val().trim(),
					from_email: this.$sendgridFromEmail.val().trim(),
					from_name: this.$sendgridFromName.val().trim(),
				},
				resend: {
					api_key: this.$resendKey.val().trim(),
					domain: this.$resendDomain.val().trim(),
					from_email: this.$resendFromEmail.val().trim(),
					from_name: this.$resendFromName.val().trim(),
				},
				mailgun: {
					api_key: this.$mailgunKey.val().trim(),
					domain: this.$mailgunDomain.val().trim(),
					region: this.$mailgunRegion.val(),
					from_email: this.$mailgunFromEmail.val().trim(),
					from_name: this.$mailgunFromName.val().trim(),
				},
				postmark: {
					server_token: this.$postmarkToken.val().trim(),
					from_email: this.$postmarkFromEmail.val().trim(),
					from_name: this.$postmarkFromName.val().trim(),
				},
				ses: {
					access_key: this.$sesAccess.val().trim(),
					secret_key: this.$sesSecret.val(),
					region: this.$sesRegion.val().trim(),
					from_email: this.$sesFromEmail.val().trim(),
					from_name: this.$sesFromName.val().trim(),
				},
			};
		},

		handleSave: function() {
			const payload = this.getSettingsPayload();
			const $spinner = this.$saveButton.find('.spinner');

			this.$saveButton.addClass('aisales-btn--loading').prop('disabled', true);
			$spinner.addClass('is-active');

			$.ajax({
				url: aisalesMailProvider.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_save_mail_provider_settings',
					nonce: aisalesMailProvider.nonce,
					settings: JSON.stringify(payload),
				},
			})
				.done((response) => {
					if (response.success) {
						window.AISales?.showRichToast({
							type: 'success',
							icon: 'dashicons-yes-alt',
							title: aisalesMailProvider.i18n.saved,
							message: response.data?.message || aisalesMailProvider.i18n.saved,
							duration: 3000,
						});
					} else {
						window.AISales?.showErrorToast(response.data?.message || aisalesMailProvider.i18n.saveFailed);
					}
				})
				.fail(() => {
					window.AISales?.showErrorToast(aisalesMailProvider.i18n.saveFailed);
				})
				.always(() => {
					this.$saveButton.removeClass('aisales-btn--loading').prop('disabled', false);
					$spinner.removeClass('is-active');
				});
		},

		openTestModal: function() {
			if (!this.$modalRecipient.val()) {
				this.$modalRecipient.val(aisalesMailProvider.adminEmail || '');
			}
			this.$modalOverlay.addClass('aisales-modal-overlay--active');
			this.$modal.addClass('aisales-modal--active');
			$('body').addClass('aisales-modal-open');
			setTimeout(() => this.$modalRecipient.trigger('focus'), 50);
		},

		closeTestModal: function() {
			this.$modalOverlay.removeClass('aisales-modal-overlay--active');
			this.$modal.removeClass('aisales-modal--active');
			$('body').removeClass('aisales-modal-open');
		},

		handleSendTest: function() {
			const recipient = this.$modalRecipient.val().trim();
			if (!recipient || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(recipient)) {
				window.AISales?.showErrorToast(aisalesMailProvider.i18n.invalidEmail);
				return;
			}

			this.$modalSend.prop('disabled', true);

			$.ajax({
				url: aisalesMailProvider.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_send_mail_provider_test',
					nonce: aisalesMailProvider.nonce,
					recipient: recipient,
				},
			})
				.done((response) => {
					if (response.success) {
						window.AISales?.showRichToast({
							type: 'success',
							icon: 'dashicons-email-alt',
							title: aisalesMailProvider.i18n.testSent,
							message: response.data?.message || aisalesMailProvider.i18n.testSent,
							duration: 3000,
						});
						this.closeTestModal();
					} else {
						window.AISales?.showErrorToast(response.data?.message || aisalesMailProvider.i18n.testFailed);
					}
				})
				.fail(() => {
					window.AISales?.showErrorToast(aisalesMailProvider.i18n.testFailed);
				})
				.always(() => {
					this.$modalSend.prop('disabled', false);
				});
		},
	};

	$(document).ready(function() {
		// Initialize on both standalone page and embedded settings panel
		if ($('.aisales-mail-provider-page').length || $('.aisales-email-settings-panel').length) {
			MailProvider.init();
		}
	});
})(jQuery);
