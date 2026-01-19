/**
 * AISales Sales Manager - Admin JavaScript
 */
(function($) {
    'use strict';

    var AISales = {
        /**
         * Initialize
         */
        init: function() {
            this.relocateNotices();
            this.bindAuthEvents();
            this.bindTopUpEvents();
            this.bindAIActions();
            this.bindModalEvents();
            this.bindApiKeyEvents();
            this.bindStoreContextEvents();
            this.bindBalanceModalEvents();
            this.bindBillingEvents();
            this.handlePageToasts();
            this.handlePaymentSetupReturn();
            this.initBalanceIndicator();
        },

        /**
         * Handle page-load toast notifications from localized data
         */
        handlePageToasts: function() {
            var self = this;

            // Check for toast data from localized script
            if (typeof aisalesAdmin !== 'undefined' && aisalesAdmin.toast) {
                var toast = aisalesAdmin.toast;
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    self.showRichToast({
                        type: toast.type || 'info',
                        icon: toast.icon || 'dashicons-info',
                        title: toast.title || '',
                        message: toast.message || '',
                        duration: toast.duration || 4000
                    });
                }, 100);
            }
        },

        /**
         * Relocate WordPress admin notices from page header to top of admin wrap
         * This fixes layout issues when WP injects notices after the h1 tag
         */
        relocateNotices: function() {
            var $adminWrap = $('.aisales-admin-wrap');
            var $pageHeader = $('.aisales-page-header');
            var $pageHeaderContent = $('.aisales-page-header__content');
            var $chatWrap = $('.aisales-chat-wrap');
            
            if (!$adminWrap.length) {
                return;
            }

            // Create a notices container at the top of admin wrap if it doesn't exist
            var $noticesContainer = $adminWrap.find('.aisales-notices-container');
            if (!$noticesContainer.length) {
                $noticesContainer = $('<div class="aisales-notices-container"></div>');
                $adminWrap.prepend($noticesContainer);
            }

            // Find and move notices from inside page header content
            var noticeSelectors = '.notice, .notice-warning, .notice-error, .notice-info, .notice-success, .updated, .error';
            
            // Move notices from page header content (where WP injects them after h1)
            $pageHeaderContent.find(noticeSelectors).each(function() {
                $(this).detach().appendTo($noticesContainer);
            });

            // Also check for notices directly in page header
            $pageHeader.children(noticeSelectors).each(function() {
                $(this).detach().appendTo($noticesContainer);
            });

            // Handle chat page - notices may be injected after the h1 element
            if ($chatWrap.length) {
                // Find notices that are direct children of chat wrap (injected after h1)
                $chatWrap.children(noticeSelectors).each(function() {
                    $(this).detach().appendTo($noticesContainer);
                });
                
                // Also find notices inside the h1 (WordPress sometimes injects here)
                $chatWrap.find('> h1').siblings(noticeSelectors).each(function() {
                    $(this).detach().appendTo($noticesContainer);
                });
            }
        },

        /**
         * Bind authentication events
         */
        bindAuthEvents: function() {
            var self = this;

            // Tab switching (legacy support)
            $('.aisales-auth-tab-btn').on('click', function() {
                var tab = $(this).data('tab');

                // Update button states (support both old and new classes)
                $('.aisales-auth-tab-btn').removeClass('button-primary aisales-auth__tab--active');
                $(this).addClass('button-primary aisales-auth__tab--active');

                // Show/hide forms
                $('.aisales-auth-form').hide();
                $('#aisales-' + tab + '-form').show();

                // Clear message
                $('#aisales-auth-message').hide();
            });

            // Legacy Login form
            $('#aisales-login-btn').on('click', function() {
                var email = $('#aisales-login-email').val();
                var password = $('#aisales-login-password').val();

                if (!email || !password) {
                    self.showAuthMessage('error', aisalesAdmin.strings.error);
                    return;
                }

                self.authRequest('aisales_login', { email: email, password: password }, $(this));
            });

            // Legacy Register form
            $('#aisales-register-btn').on('click', function() {
                var email = $('#aisales-register-email').val();
                var password = $('#aisales-register-password').val();

                if (!email || !password) {
                    self.showAuthMessage('error', aisalesAdmin.strings.error);
                    return;
                }

                if (password.length < 8) {
                    self.showAuthMessage('error', 'Password must be at least 8 characters.');
                    return;
                }

                self.authRequest('aisales_register', { email: email, password: password }, $(this));
            });

            // New Connect form (domain-based auth)
            $('#aisales-connect-btn').on('click', function() {
                var email = $('#aisales-connect-email').val();
                var domain = $('#aisales-connect-domain').val();

                if (!email) {
                    self.showAuthMessage('error', 'Please enter your email address.');
                    return;
                }

                self.authRequest('aisales_connect', { email: email, domain: domain }, $(this));
            });
        },

        /**
         * Make auth request
         */
        authRequest: function(action, data, $btn) {
            var self = this;
            var $spinner = $btn.find('.spinner');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            data.action = action;
            data.nonce = aisalesAdmin.nonce;

            $.post(aisalesAdmin.ajaxUrl, data)
                .done(function(response) {
                    if (response.success) {
                        // Show success toast for connect action
                        if (action === 'aisales_connect') {
                            var isNew = response.data.is_new;
                            self.showRichToast({
                                type: 'success',
                                icon: 'dashicons-yes-alt',
                                title: isNew ? 'Account Created!' : 'Connected!',
                                message: isNew
                                    ? 'Your AISales account is ready. Redirecting to dashboard...'
                                    : 'Welcome back! Redirecting to dashboard...',
                                duration: 2500
                            });
                        } else {
                            self.showAuthMessage('success', response.data.message);
                        }

                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, action === 'aisales_connect' ? 1500 : 0);
                        }
                    } else {
                        self.showAuthMessage('error', response.data.message);
                    }
                })
                .fail(function() {
                    self.showAuthMessage('error', aisalesAdmin.strings.error);
                })
                .always(function() {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        },

        /**
         * Show auth message
         */
        showAuthMessage: function(type, message) {
            var $msg = $('#aisales-auth-message');
            var alertClass = type === 'error' ? 'aisales-alert--danger' : 'aisales-alert--success';
            var iconClass = type === 'error' ? 'dashicons-warning' : 'dashicons-yes-alt';

            $msg.removeClass('notice notice-success notice-error notice-info aisales-alert--success aisales-alert--danger aisales-alert--warning aisales-alert--info')
                .addClass('aisales-alert ' + alertClass)
                .html('<span class="dashicons ' + iconClass + '"></span><span>' + message + '</span>')
                .show();
        },

        /**
         * Bind top-up button events
         */
        bindTopUpEvents: function() {
            $('#aisales-topup-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true);

                $.post(aisalesAdmin.ajaxUrl, {
                    action: 'aisales_topup',
                    nonce: aisalesAdmin.nonce
                })
                .done(function(response) {
                    if (response.success && response.data.checkout_url) {
                        window.location.href = response.data.checkout_url;
                    } else {
                        AISales.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'Top-Up Error',
                            message: response.data.message || aisalesAdmin.strings.error,
                            duration: 4000
                        });
                        $btn.prop('disabled', false);
                    }
                })
                .fail(function() {
                    AISales.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: aisalesAdmin.strings.error,
                        duration: 4000
                    });
                    $btn.prop('disabled', false);
                });
            });
        },

        /**
         * Bind AI action button events
         */
        bindAIActions: function() {
            var self = this;

            $('.aisales-ai-action').on('click', function() {
                var $btn = $(this);
                var action = $btn.data('action');
                var productId = $btn.data('product-id');

                // Check balance first
                var balance = parseInt($('#aisales-balance-count').text().replace(/,/g, ''), 10);
                if (balance < 50) {
                    self.showRichToast({
                        type: 'warning',
                        icon: 'dashicons-money-alt',
                        title: 'Low Balance',
                        message: aisalesAdmin.strings.lowBalance || 'Your token balance is too low. Please top up to continue.',
                        duration: 5000
                    });
                    return;
                }

                self.executeAIAction($btn, action, productId);
            });
        },

        /**
         * Execute AI action
         */
        executeAIAction: function($btn, action, productId) {
            var self = this;
            var $spinner = $btn.find('.spinner');

            // Disable button and show spinner
            $btn.addClass('loading').prop('disabled', true);
            $spinner.addClass('is-active');

            var data = {
                action: 'aisales_' + action,
                nonce: aisalesAdmin.nonce,
                product_id: productId
            };

            // Add extra data based on action type
            if (action === 'generate_content') {
                data.ai_action = $btn.data('ai-action') || 'improve';
            }

            $.post(aisalesAdmin.ajaxUrl, data)
                .done(function(response) {
                    if (response.success) {
                        self.showResultModal(action, response.data);
                    } else {
                        self.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'AI Action Failed',
                            message: response.data.message || aisalesAdmin.strings.error,
                            duration: 4000
                        });
                    }
                })
                .fail(function() {
                    self.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: aisalesAdmin.strings.error,
                        duration: 4000
                    });
                })
                .always(function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        },

        /**
         * Show result modal
         */
        showResultModal: function(action, data) {
            var self = this;
            var $modal = $('#aisales-result-modal');
            var $title = $('#aisales-modal-title');
            var $body = $('#aisales-modal-body');
            var $tokensUsed = $('.aisales-tokens-used');

            // Update balance display
            if (data.new_balance !== undefined) {
                $('#aisales-balance-count').text(data.new_balance.toLocaleString());
            }

            // Set tokens used
            if (data.tokens_used) {
                $tokensUsed.text('Tokens used: ' + data.tokens_used.total);
            }

            // Build modal content based on action type
            var html = '';

            switch (action) {
                case 'generate_content':
                    $title.text('Generated Content');
                    html = self.buildContentResult(data.result);
                    break;

                case 'suggest_taxonomy':
                    $title.text('Suggested Taxonomy');
                    html = self.buildTaxonomyResult(data);
                    break;

                case 'generate_image':
                case 'improve_image':
                    $title.text(action === 'generate_image' ? 'Generated Image' : 'Improved Image');
                    html = self.buildImageResult(data);
                    break;
            }

            $body.html(html);

            // Store data for apply action
            $modal.data('action-type', action);
            $modal.data('result-data', data);

            // Open Thickbox
            tb_show('AI Result', '#TB_inline?inlineId=aisales-result-modal&width=550&height=500');
        },

        /**
         * Build content result HTML
         */
        buildContentResult: function(result) {
            var html = '';

            if (result.title) {
                html += '<div class="aisales-result-field">';
                html += '<label>Title</label>';
                html += '<input type="text" id="aisales-result-title" value="' + this.escapeHtml(result.title) + '" class="large-text">';
                html += '</div>';
            }

            if (result.description) {
                html += '<div class="aisales-result-field">';
                html += '<label>Description</label>';
                html += '<textarea id="aisales-result-description" rows="6" class="large-text">' + this.escapeHtml(result.description) + '</textarea>';
                html += '</div>';
            }

            if (result.short_description) {
                html += '<div class="aisales-result-field">';
                html += '<label>Short Description</label>';
                html += '<textarea id="aisales-result-short-desc" rows="2" class="large-text">' + this.escapeHtml(result.short_description) + '</textarea>';
                html += '</div>';
            }

            return html;
        },

        /**
         * Build taxonomy result HTML
         */
        buildTaxonomyResult: function(data) {
            var html = '';

            if (data.categories && data.categories.length) {
                html += '<div class="aisales-result-field">';
                html += '<label>Suggested Categories</label>';
                html += '<div class="aisales-result-tags">';
                data.categories.forEach(function(cat) {
                    html += '<span class="aisales-result-tag selected" data-type="category">' + cat + '</span>';
                });
                html += '</div></div>';
            }

            if (data.tags && data.tags.length) {
                html += '<div class="aisales-result-field">';
                html += '<label>Suggested Tags</label>';
                html += '<div class="aisales-result-tags">';
                data.tags.forEach(function(tag) {
                    html += '<span class="aisales-result-tag selected" data-type="tag">' + tag + '</span>';
                });
                html += '</div></div>';
            }

            return html;
        },

        /**
         * Build image result HTML
         */
        buildImageResult: function(data) {
            var html = '<div class="aisales-result-field" style="text-align: center;">';
            html += '<img src="' + data.image_url + '" class="aisales-result-image" alt="Generated image">';
            html += '</div>';
            return html;
        },

        /**
         * Bind modal events
         */
        bindModalEvents: function() {
            var self = this;

            // Tag selection toggle
            $(document).on('click', '.aisales-result-tag', function() {
                $(this).toggleClass('selected');
            });

            // Discard button
            $('#aisales-modal-discard').on('click', function() {
                tb_remove();
            });

            // Apply button
            $('#aisales-modal-apply').on('click', function() {
                var $modal = $('#aisales-result-modal');
                var actionType = $modal.data('action-type');
                var data = $modal.data('result-data');

                self.applyResult(actionType, data);
                tb_remove();
            });
        },

        /**
         * Bind API key show/hide and copy events
         */
        bindApiKeyEvents: function() {
            var self = this;
            var $display = $('#aisales-api-key-display');
            var $toggleBtn = $('#aisales-toggle-key');
            var $copyBtn = $('#aisales-copy-key');

            // Only proceed if elements exist
            if (!$display.length) {
                return;
            }

            // Toggle API key visibility
            $toggleBtn.on('click', function() {
                var $icon = $(this).find('.dashicons');
                var isVisible = $display.data('visible') === true;

                if (isVisible) {
                    // Hide the key
                    $display.text($display.data('masked'));
                    $display.data('visible', false);
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    $(this).attr('title', aisalesAdmin.strings.showKey || 'Show API Key');
                } else {
                    // Show the key
                    $display.text($display.data('full'));
                    $display.data('visible', true);
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    $(this).attr('title', aisalesAdmin.strings.hideKey || 'Hide API Key');
                }
            });

            // Copy API key to clipboard
            $copyBtn.on('click', function() {
                var $btn = $(this);
                var $icon = $btn.find('.dashicons');
                var apiKey = $display.data('full');

                // Use modern clipboard API if available
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(apiKey).then(function() {
                        self.showCopySuccess($btn, $icon);
                    }).catch(function() {
                        self.fallbackCopy(apiKey, $btn, $icon);
                    });
                } else {
                    self.fallbackCopy(apiKey, $btn, $icon);
                }
            });
        },

        /**
         * Fallback copy method for older browsers
         */
        fallbackCopy: function(text, $btn, $icon) {
            var self = this;
            var $temp = $('<textarea>');
            $temp.val(text).css({
                position: 'fixed',
                left: '-9999px'
            }).appendTo('body').focus().select();

            try {
                document.execCommand('copy');
                self.showCopySuccess($btn, $icon);
            } catch (err) {
                self.showRichToast({
                    type: 'error',
                    icon: 'dashicons-warning',
                    title: 'Copy Failed',
                    message: aisalesAdmin.strings.copyError || 'Failed to copy to clipboard.',
                    duration: 3000
                });
            }

            $temp.remove();
        },

        /**
         * Show copy success feedback
         */
        showCopySuccess: function($btn, $icon) {
            var originalClass = 'dashicons-clipboard';
            var successClass = 'dashicons-yes';

            // Change icon to checkmark
            $icon.removeClass(originalClass).addClass(successClass);
            $btn.addClass('aisales-api-key__btn--success');

            // Show toast notification
            this.showToast(aisalesAdmin.strings.copied || 'Copied to clipboard!');

            // Revert after 2 seconds
            setTimeout(function() {
                $icon.removeClass(successClass).addClass(originalClass);
                $btn.removeClass('aisales-api-key__btn--success');
            }, 2000);
        },

        /**
         * Show simple toast notification (backwards compatible)
         */
        showToast: function(message, type) {
            var typeClass = type ? ' aisales-toast--' + type : '';
            var $toast = $('<div class="aisales-toast aisales-toast--simple' + typeClass + '">' + this.escapeHtml(message) + '</div>');

            // Remove any existing toast
            $('.aisales-toast').remove();

            // Add toast to body
            $('body').append($toast);

            // Trigger animation
            setTimeout(function() {
                $toast.addClass('aisales-toast--visible');
            }, 10);

            // Auto-hide after 2 seconds
            setTimeout(function() {
                $toast.addClass('aisales-toast--hiding');
                setTimeout(function() {
                    $toast.remove();
                }, 400);
            }, 2000);
        },

        /**
         * Show rich toast notification with icon, title, message, and progress bar
         * @param {Object} options - Toast options
         * @param {string} options.type - Toast type: success, error, info, warning
         * @param {string} options.icon - Dashicon class (e.g., 'dashicons-yes-alt')
         * @param {string} options.title - Toast title
         * @param {string} options.message - Toast message
         * @param {number} options.duration - Duration in ms (default: 3000)
         */
        showRichToast: function(options) {
            var self = this;
            var type = options.type || 'info';
            var icon = options.icon || 'dashicons-info';
            var title = options.title || '';
            var message = options.message || '';
            var duration = options.duration || 3000;

            // Build toast HTML
            var html = '<div class="aisales-toast aisales-toast--' + type + '">';
            html += '<div class="aisales-toast__icon"><span class="dashicons ' + icon + '"></span></div>';
            html += '<div class="aisales-toast__content">';
            if (title) {
                html += '<h4 class="aisales-toast__title">' + self.escapeHtml(title) + '</h4>';
            }
            if (message) {
                html += '<p class="aisales-toast__message">' + self.escapeHtml(message) + '</p>';
            }
            html += '</div>';
            html += '<button type="button" class="aisales-toast__close"><span class="dashicons dashicons-no-alt"></span></button>';
            html += '<div class="aisales-toast__progress"><div class="aisales-toast__progress-bar" style="animation-duration: ' + duration + 'ms;"></div></div>';
            html += '</div>';

            var $toast = $(html);

            // Remove any existing toast
            $('.aisales-toast').remove();

            // Add toast to body
            $('body').append($toast);

            // Bind close button
            $toast.find('.aisales-toast__close').on('click', function() {
                self.hideToast($toast);
            });

            // Trigger animation
            setTimeout(function() {
                $toast.addClass('aisales-toast--visible');
            }, 10);

            // Auto-hide after duration
            setTimeout(function() {
                self.hideToast($toast);
            }, duration);

            return $toast;
        },

        /**
         * Hide toast with animation
         */
        hideToast: function($toast) {
            if (!$toast.length || $toast.hasClass('aisales-toast--hiding')) {
                return;
            }
            $toast.addClass('aisales-toast--hiding');
            setTimeout(function() {
                $toast.remove();
            }, 400);
        },

        /**
         * Apply result to product form
         */
        applyResult: function(actionType, data) {
            var actionLabels = {
                'generate_content': 'Content Applied',
                'suggest_taxonomy': 'Taxonomy Updated',
                'generate_image': 'Image Applied',
                'improve_image': 'Image Applied'
            };

            switch (actionType) {
                case 'generate_content':
                    this.applyContentResult();
                    break;

                case 'suggest_taxonomy':
                    this.applyTaxonomyResult();
                    break;

                case 'generate_image':
                case 'improve_image':
                    this.applyImageResult(data);
                    break;
            }

            // Show success toast
            this.showRichToast({
                type: 'success',
                icon: 'dashicons-yes-alt',
                title: actionLabels[actionType] || 'Applied!',
                message: aisalesAdmin.strings.success || 'Changes have been applied to the product.',
                duration: 3000
            });
        },

        /**
         * Apply content result to product form
         */
        applyContentResult: function() {
            var title = $('#aisales-result-title').val();
            var description = $('#aisales-result-description').val();
            var shortDesc = $('#aisales-result-short-desc').val();

            if (title) {
                $('#title').val(title);
            }

            if (description) {
                // Try TinyMCE first, then fallback to textarea
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                    tinyMCE.get('content').setContent(description);
                } else {
                    $('#content').val(description);
                }
            }

            if (shortDesc) {
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('excerpt')) {
                    tinyMCE.get('excerpt').setContent(shortDesc);
                } else {
                    $('#excerpt').val(shortDesc);
                }
            }
        },

        /**
         * Apply taxonomy result
         */
        applyTaxonomyResult: function() {
            // Get selected tags
            var selectedTags = [];
            $('.aisales-result-tag.selected[data-type="tag"]').each(function() {
                selectedTags.push($(this).text());
            });

            // Add to product tags field
            if (selectedTags.length) {
                var $tagInput = $('#new-tag-product_tag');
                if ($tagInput.length) {
                    $tagInput.val(selectedTags.join(', '));
                    // Click add button
                    $('.tagadd').click();
                }
            }

            // Note: Categories would need checkbox handling, simplified for demo
        },

        /**
         * Apply image result
         */
        applyImageResult: function(data) {
            // For now, show info toast - full implementation would download and set as featured image
            this.showRichToast({
                type: 'info',
                icon: 'dashicons-format-image',
                title: 'Image Ready',
                message: 'Image URL copied. Set as product image manually from the Media Library.',
                duration: 5000
            });

            // Copy image URL to clipboard
            if (navigator.clipboard && navigator.clipboard.writeText && data.image_url) {
                navigator.clipboard.writeText(data.image_url);
            }
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap h1').first().after($notice);

            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Bind store context panel events
         */
        bindStoreContextEvents: function() {
            var self = this;
            var $panel = $('#aisales-context-panel');

            // Skip if panel doesn't exist on this page
            if (!$panel.length) {
                return;
            }

            // Open/close panel
            $('#aisales-open-context').on('click', function() { self.openContextPanel(); });
            $('#aisales-close-context, #aisales-cancel-context, #aisales-context-backdrop').on('click', function() { self.closeContextPanel(); });

            // Save and sync
            $('#aisales-save-context').on('click', function() { self.saveStoreContext(); });
            $('#aisales-sync-context').on('click', function() { self.syncStoreContext(); });

            // Close on Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $panel.hasClass('aisales-context-panel--open')) {
                    self.closeContextPanel();
                }
            });
        },

        /**
         * Open store context panel
         */
        openContextPanel: function() {
            $('#aisales-context-panel').addClass('aisales-context-panel--open');
            $('body').addClass('aisales-context-panel-active');
        },

        /**
         * Close store context panel
         */
        closeContextPanel: function() {
            $('#aisales-context-panel').removeClass('aisales-context-panel--open');
            $('body').removeClass('aisales-context-panel-active');
        },

        /**
         * Save store context
         */
        saveStoreContext: function() {
            var self = this;
            var formData = {
                store_name: $('#aisales-store-name').val(),
                store_description: $('#aisales-store-description').val(),
                business_niche: $('#aisales-business-niche').val(),
                target_audience: $('#aisales-target-audience').val(),
                brand_tone: $('input[name="brand_tone"]:checked').val() || '',
                language: $('#aisales-language').val(),
                custom_instructions: $('#aisales-custom-instructions').val()
            };

            var $saveBtn = $('#aisales-save-context');
            $saveBtn.addClass('aisales-btn--loading').prop('disabled', true);

            $.ajax({
                url: aisalesAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'aisales_save_store_context',
                    nonce: aisalesAdmin.chatNonce,
                    context: formData
                },
                success: function(response) {
                    if (response.success) {
                        self.updateContextStatus(formData);
                        self.closeContextPanel();
                        self.showRichToast({
                            type: 'success',
                            icon: 'dashicons-saved',
                            title: 'Saved',
                            message: 'Store context saved successfully',
                            duration: 3000
                        });
                    } else {
                        self.showErrorToast(response.data || 'Failed to save store context');
                    }
                },
                error: function() {
                    self.showErrorToast('Failed to save store context');
                },
                complete: function() {
                    $saveBtn.removeClass('aisales-btn--loading').prop('disabled', false);
                }
            });
        },

        /**
         * Sync store context (re-fetch category/product counts)
         */
        syncStoreContext: function() {
            var self = this;
            var $syncBtn = $('#aisales-sync-context');
            $syncBtn.addClass('aisales-btn--loading').prop('disabled', true);

            $.ajax({
                url: aisalesAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'aisales_sync_store_context',
                    nonce: aisalesAdmin.chatNonce
                },
                success: function(response) {
                    if (response.success) {
                        var message = response.data.message || 'Store context synced';
                        $('#aisales-sync-status').text(message);
                        self.showRichToast({
                            type: 'success',
                            icon: 'dashicons-update',
                            title: 'Synced',
                            message: message,
                            duration: 3000
                        });
                    } else {
                        self.showErrorToast(response.data || 'Failed to sync store context', 'Sync Failed');
                    }
                },
                error: function() {
                    self.showErrorToast('Failed to sync store context');
                },
                complete: function() {
                    $syncBtn.removeClass('aisales-btn--loading').prop('disabled', false);
                }
            });
        },

        /**
         * Show error toast (helper to reduce duplication)
         */
        showErrorToast: function(message, title) {
            this.showRichToast({
                type: 'error',
                icon: 'dashicons-warning',
                title: title || 'Error',
                message: message,
                duration: 4000
            });
        },

        /**
         * Update context status indicator
         */
        updateContextStatus: function(context) {
            var hasRequired = context.store_name || context.business_niche;
            var hasOptional = context.target_audience || context.brand_tone;
            var status = hasRequired ? (hasOptional ? 'configured' : 'partial') : 'missing';

            $('.aisales-context-status')
                .removeClass('aisales-context-status--configured aisales-context-status--partial aisales-context-status--missing')
                .addClass('aisales-context-status--' + status);

            if (context.store_name) {
                $('.aisales-store-name').text(context.store_name);
            }
        },

        /**
         * Bind billing page events
         */
        bindBillingEvents: function() {
            var self = this;
            var $billingPage = $('.aisales-billing');

            // Skip if not on billing page
            if (!$billingPage.length) return;

            // Auto top-up toggle
            $('#aisales-autotopup-enabled').on('change', function() {
                var enabled = $(this).is(':checked');
                self.updateAutoTopupEnabled(enabled);
            });

            // Threshold dropdown change
            $('#aisales-autotopup-threshold').on('change', function() {
                self.updateAutoTopupSettings();
            });

            // Product dropdown change
            $('#aisales-autotopup-product').on('change', function() {
                self.updateAutoTopupSettings();
            });

            // Add payment method button
            $('#aisales-add-card-btn').on('click', function() {
                self.setupPaymentMethod($(this));
            });

            // Change payment method button
            $('#aisales-change-card-btn').on('click', function() {
                self.setupPaymentMethod($(this));
            });

            // Remove payment method button
            $('#aisales-remove-card-btn').on('click', function() {
                self.removePaymentMethod($(this));
            });

            // Add tokens button on billing page
            $('#aisales-billing-topup-btn').on('click', function() {
                var $modal = $('#aisales-balance-modal');
                if ($modal.length) {
                    self.openBalanceModal();
                } else {
                    // Fallback to direct checkout
                    self.initiateCheckout();
                }
            });
        },

        /**
         * Handle payment setup return from Stripe
         */
        handlePaymentSetupReturn: function() {
            var self = this;
            var urlParams = new URLSearchParams(window.location.search);
            var paymentSetup = urlParams.get('payment_setup');
            
            if (!paymentSetup) return;

            var config = {
                success: {
                    type: 'success',
                    icon: 'dashicons-yes-alt',
                    title: 'Payment Method Added',
                    message: 'Your card has been saved for auto top-up.',
                    duration: 4000
                },
                cancelled: {
                    type: 'info',
                    icon: 'dashicons-info',
                    title: 'Setup Cancelled',
                    message: 'Payment method setup was cancelled.',
                    duration: 3000
                }
            };

            var toastConfig = config[paymentSetup];
            if (toastConfig) {
                setTimeout(function() {
                    self.showRichToast(toastConfig);
                }, 100);

                // Clean up URL
                var cleanUrl = window.location.pathname + 
                    window.location.search.replace(/[?&]payment_setup=(success|cancelled)/, '')
                    .replace(/^&/, '?').replace(/\?$/, '');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, cleanUrl || window.location.pathname);
                }
            }
        },

        /**
         * Update auto top-up enabled state
         */
        updateAutoTopupEnabled: function(enabled) {
            var self = this;
            var $toggle = $('#aisales-autotopup-enabled');
            var $options = $('#aisales-autotopup-options');
            var $threshold = $('#aisales-autotopup-threshold');
            var $product = $('#aisales-autotopup-product');
            var $statCard = $('.aisales-stat-card--autotopup');

            // Disable toggle during request
            $toggle.prop('disabled', true);

            $.ajax({
                url: aisalesAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'aisales_update_auto_topup',
                    nonce: aisalesAdmin.nonce,
                    enabled: enabled ? 1 : 0,
                    threshold: $threshold.val(),
                    product_slug: $product.val()
                },
                success: function(response) {
                    if (response.success) {
                        // Update UI state
                        if (enabled) {
                            $options.removeClass('aisales-setting-row--disabled');
                            $threshold.prop('disabled', false);
                            $product.prop('disabled', false);
                            $statCard.removeClass('aisales-stat-card--disabled').addClass('aisales-stat-card--enabled');
                            $statCard.find('.aisales-stat-card__value').html('<span class="aisales-status-dot aisales-status-dot--success"></span> ON');
                        } else {
                            $options.addClass('aisales-setting-row--disabled');
                            $threshold.prop('disabled', true);
                            $product.prop('disabled', true);
                            $statCard.removeClass('aisales-stat-card--enabled').addClass('aisales-stat-card--disabled');
                            $statCard.find('.aisales-stat-card__value').html('<span class="aisales-status-dot aisales-status-dot--muted"></span> OFF');
                        }

                        self.showRichToast({
                            type: 'success',
                            icon: 'dashicons-yes-alt',
                            title: enabled ? 'Auto Top-Up Enabled' : 'Auto Top-Up Disabled',
                            message: enabled ? 'Your balance will be topped up automatically.' : 'Automatic top-ups have been turned off.',
                            duration: 3000
                        });
                    } else {
                        // Revert toggle
                        $toggle.prop('checked', !enabled);
                        self.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'Update Failed',
                            message: response.data.message || 'Could not update auto top-up settings.',
                            duration: 4000
                        });
                    }
                },
                error: function() {
                    // Revert toggle
                    $toggle.prop('checked', !enabled);
                    self.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: 'Failed to connect to server.',
                        duration: 4000
                    });
                },
                complete: function() {
                    $toggle.prop('disabled', false);
                }
            });
        },

        /**
         * Update auto top-up settings (threshold/product)
         */
        updateAutoTopupSettings: function() {
            var self = this;
            var $threshold = $('#aisales-autotopup-threshold');
            var $product = $('#aisales-autotopup-product');

            // Only update if enabled
            if (!$('#aisales-autotopup-enabled').is(':checked')) return;

            $.ajax({
                url: aisalesAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'aisales_update_auto_topup',
                    nonce: aisalesAdmin.nonce,
                    enabled: 1,
                    threshold: $threshold.val(),
                    product_slug: $product.val()
                },
                success: function(response) {
                    if (response.success) {
                        // Update stat card detail
                        var threshold = $threshold.val();
                        $('.aisales-stat-card--autotopup .aisales-stat-card__detail').text('When below ' + parseInt(threshold).toLocaleString() + ' tokens');

                        self.showRichToast({
                            type: 'success',
                            icon: 'dashicons-saved',
                            title: 'Settings Saved',
                            message: 'Auto top-up settings updated.',
                            duration: 2000
                        });
                    } else {
                        self.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'Update Failed',
                            message: response.data.message || 'Could not save settings.',
                            duration: 4000
                        });
                    }
                },
                error: function() {
                    self.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: 'Failed to connect to server.',
                        duration: 4000
                    });
                }
            });
        },

        /**
         * Setup payment method via Stripe
         */
        setupPaymentMethod: function($btn) {
            var self = this;
            
            $btn.prop('disabled', true).addClass('aisales-btn--loading');

            // Build return URL
            var returnUrl = window.location.href.split('?')[0] + '?page=aisales-billing&payment_setup=success';
            var cancelUrl = window.location.href.split('?')[0] + '?page=aisales-billing&payment_setup=cancelled';

            $.ajax({
                url: aisalesAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'aisales_setup_payment_method',
                    nonce: aisalesAdmin.nonce,
                    success_url: returnUrl,
                    cancel_url: cancelUrl
                },
                success: function(response) {
                    if (response.success && response.data.setup_url) {
                        window.location.href = response.data.setup_url;
                    } else {
                        $btn.prop('disabled', false).removeClass('aisales-btn--loading');
                        self.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'Setup Failed',
                            message: response.data.message || 'Could not initiate payment setup.',
                            duration: 4000
                        });
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).removeClass('aisales-btn--loading');
                    self.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: 'Failed to connect to payment service.',
                        duration: 4000
                    });
                }
            });
        },

        /**
         * Remove payment method
         */
        removePaymentMethod: function($btn) {
            var self = this;

            // Confirm removal
            if (!confirm('Remove this payment method? Auto top-up will be disabled.')) {
                return;
            }

            $btn.prop('disabled', true).addClass('aisales-btn--loading');

            $.ajax({
                url: aisalesAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'aisales_remove_payment_method',
                    nonce: aisalesAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show updated state
                        self.showRichToast({
                            type: 'success',
                            icon: 'dashicons-yes-alt',
                            title: 'Card Removed',
                            message: 'Refreshing page...',
                            duration: 2000
                        });
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $btn.prop('disabled', false).removeClass('aisales-btn--loading');
                        self.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'Removal Failed',
                            message: response.data.message || 'Could not remove payment method.',
                            duration: 4000
                        });
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).removeClass('aisales-btn--loading');
                    self.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: 'Failed to connect to server.',
                        duration: 4000
                    });
                }
            });
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Initialize balance indicator
         */
        initBalanceIndicator: function() {
            var $indicator = $('.aisales-balance-indicator');
            if (!$indicator.length) return;

            // Make clickable
            $indicator.addClass('aisales-balance-indicator--clickable');
            $indicator.attr('role', 'button');
            $indicator.attr('tabindex', '0');
            $indicator.attr('title', aisalesAdmin.strings.clickToTopUp || 'Click to add tokens');

            // Check if balance is low (below 1000)
            var balance = this.getBalanceValue();
            if (balance < 1000) {
                $indicator.addClass('aisales-balance-indicator--low');
            }
        },

        /**
         * Get current balance value
         */
        getBalanceValue: function() {
            var balanceText = $('#aisales-balance-count, #aisales-balance-display').first().text();
            return parseInt(balanceText.replace(/,/g, ''), 10) || 0;
        },

        /**
         * Bind balance modal events
         */
        bindBalanceModalEvents: function() {
            var self = this;
            var $modal = $('#aisales-balance-modal');
            var $overlay = $('#aisales-balance-modal-overlay');

            // Skip if modal doesn't exist on this page
            if (!$modal.length) return;

            // Click on balance indicator opens modal
            $('.aisales-balance-indicator').on('click', function(e) {
                e.preventDefault();
                self.openBalanceModal();
            });

            // Keyboard support
            $('.aisales-balance-indicator').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self.openBalanceModal();
                }
            });

            // Close modal
            $('#aisales-balance-modal-close').on('click', function() {
                self.closeBalanceModal();
            });

            $overlay.on('click', function() {
                self.closeBalanceModal();
            });

            // Close on Escape key
            $(document).on('keydown.balanceModal', function(e) {
                if (e.key === 'Escape' && $modal.hasClass('aisales-modal--active')) {
                    self.closeBalanceModal();
                }
            });

            // Package selection (for future multi-plan support)
            $('.aisales-package-card').on('click', function() {
                $('.aisales-package-card').removeClass('aisales-package-card--selected');
                $(this).addClass('aisales-package-card--selected');
            });

            // Purchase button
            $('#aisales-purchase-btn').on('click', function() {
                self.initiateCheckout();
            });
        },

        /**
         * Open balance modal
         */
        openBalanceModal: function() {
            var self = this;
            var $modal = $('#aisales-balance-modal');
            var $overlay = $('#aisales-balance-modal-overlay');

            // Update balance display in modal
            var currentBalance = this.getBalanceValue();
            $('#aisales-balance-modal-value').text(currentBalance.toLocaleString());

            // Update progress bar
            var progressPercent = Math.min(100, (currentBalance / 10000) * 100);
            $('#aisales-balance-progress-bar').css('width', progressPercent + '%');

            // Update low balance state
            var $balanceCurrent = $('.aisales-balance-current');
            if (currentBalance < 1000) {
                $balanceCurrent.addClass('aisales-balance-current--low');
            } else {
                $balanceCurrent.removeClass('aisales-balance-current--low');
            }

            // Show modal
            $overlay.addClass('aisales-modal-overlay--active');
            $modal.addClass('aisales-modal--active');
            $('body').addClass('aisales-balance-modal-active');

            // Focus close button for accessibility
            setTimeout(function() {
                $('#aisales-balance-modal-close').focus();
            }, 100);
        },

        /**
         * Close balance modal
         */
        closeBalanceModal: function() {
            $('#aisales-balance-modal-overlay').removeClass('aisales-modal-overlay--active');
            $('#aisales-balance-modal').removeClass('aisales-modal--active');
            $('body').removeClass('aisales-balance-modal-active');

            // Return focus to balance indicator
            $('.aisales-balance-indicator').first().focus();
        },

        /**
         * Initiate Stripe checkout
         */
        initiateCheckout: function() {
            var self = this;
            var $btn = $('#aisales-purchase-btn');
            var $loading = $('#aisales-balance-modal-loading');

            // Show loading state
            $btn.prop('disabled', true);
            $loading.show();

            $.ajax({
                url: aisalesAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'aisales_topup',
                    nonce: aisalesAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.checkout_url) {
                        // Redirect to Stripe checkout
                        window.location.href = response.data.checkout_url;
                    } else {
                        $loading.hide();
                        $btn.prop('disabled', false);
                        self.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'Checkout Error',
                            message: response.data.message || 'Failed to create checkout session',
                            duration: 4000
                        });
                    }
                },
                error: function() {
                    $loading.hide();
                    $btn.prop('disabled', false);
                    self.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: 'Failed to connect to payment service',
                        duration: 4000
                    });
                }
            });
        },

        /**
         * Update balance indicator after successful purchase
         */
        updateBalanceIndicator: function(newBalance) {
            var $indicator = $('.aisales-balance-indicator');
            var $count = $('#aisales-balance-count, #aisales-balance-display');

            // Animate the update
            $indicator.addClass('aisales-balance--increasing');
            $count.text(newBalance.toLocaleString());

            setTimeout(function() {
                $indicator.removeClass('aisales-balance--increasing');
            }, 500);

            // Update low balance state
            if (newBalance >= 1000) {
                $indicator.removeClass('aisales-balance-indicator--low');
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AISales.init();
    });

})(jQuery);
