/**
 * WooAI Sales Manager - Admin JavaScript
 */
(function($) {
    'use strict';

    var WooAI = {
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
        },

        /**
         * Relocate WordPress admin notices from page header to top of admin wrap
         * This fixes layout issues when WP injects notices after the h1 tag
         */
        relocateNotices: function() {
            var $adminWrap = $('.wooai-admin-wrap');
            var $pageHeader = $('.wooai-page-header');
            var $pageHeaderContent = $('.wooai-page-header__content');
            var $chatWrap = $('.wooai-chat-wrap');
            
            if (!$adminWrap.length) {
                return;
            }

            // Create a notices container at the top of admin wrap if it doesn't exist
            var $noticesContainer = $adminWrap.find('.wooai-notices-container');
            if (!$noticesContainer.length) {
                $noticesContainer = $('<div class="wooai-notices-container"></div>');
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
            $('.wooai-auth-tab-btn').on('click', function() {
                var tab = $(this).data('tab');

                // Update button states (support both old and new classes)
                $('.wooai-auth-tab-btn').removeClass('button-primary wooai-auth__tab--active');
                $(this).addClass('button-primary wooai-auth__tab--active');

                // Show/hide forms
                $('.wooai-auth-form').hide();
                $('#wooai-' + tab + '-form').show();

                // Clear message
                $('#wooai-auth-message').hide();
            });

            // Legacy Login form
            $('#wooai-login-btn').on('click', function() {
                var email = $('#wooai-login-email').val();
                var password = $('#wooai-login-password').val();

                if (!email || !password) {
                    self.showAuthMessage('error', wooaiAdmin.strings.error);
                    return;
                }

                self.authRequest('wooai_login', { email: email, password: password }, $(this));
            });

            // Legacy Register form
            $('#wooai-register-btn').on('click', function() {
                var email = $('#wooai-register-email').val();
                var password = $('#wooai-register-password').val();

                if (!email || !password) {
                    self.showAuthMessage('error', wooaiAdmin.strings.error);
                    return;
                }

                if (password.length < 8) {
                    self.showAuthMessage('error', 'Password must be at least 8 characters.');
                    return;
                }

                self.authRequest('wooai_register', { email: email, password: password }, $(this));
            });

            // New Connect form (domain-based auth)
            $('#wooai-connect-btn').on('click', function() {
                var email = $('#wooai-connect-email').val();
                var domain = $('#wooai-connect-domain').val();

                if (!email) {
                    self.showAuthMessage('error', 'Please enter your email address.');
                    return;
                }

                self.authRequest('wooai_connect', { email: email, domain: domain }, $(this));
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
            data.nonce = wooaiAdmin.nonce;

            $.post(wooaiAdmin.ajaxUrl, data)
                .done(function(response) {
                    if (response.success) {
                        // Show success toast for connect action
                        if (action === 'wooai_connect') {
                            var isNew = response.data.is_new;
                            self.showRichToast({
                                type: 'success',
                                icon: 'dashicons-yes-alt',
                                title: isNew ? 'Account Created!' : 'Connected!',
                                message: isNew
                                    ? 'Your WooAI account is ready. Redirecting to dashboard...'
                                    : 'Welcome back! Redirecting to dashboard...',
                                duration: 2500
                            });
                        } else {
                            self.showAuthMessage('success', response.data.message);
                        }

                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, action === 'wooai_connect' ? 1500 : 0);
                        }
                    } else {
                        self.showAuthMessage('error', response.data.message);
                    }
                })
                .fail(function() {
                    self.showAuthMessage('error', wooaiAdmin.strings.error);
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
            var $msg = $('#wooai-auth-message');
            var alertClass = type === 'error' ? 'wooai-alert--danger' : 'wooai-alert--success';
            var iconClass = type === 'error' ? 'dashicons-warning' : 'dashicons-yes-alt';

            $msg.removeClass('notice notice-success notice-error notice-info wooai-alert--success wooai-alert--danger wooai-alert--warning wooai-alert--info')
                .addClass('wooai-alert ' + alertClass)
                .html('<span class="dashicons ' + iconClass + '"></span><span>' + message + '</span>')
                .show();
        },

        /**
         * Bind top-up button events
         */
        bindTopUpEvents: function() {
            $('#wooai-topup-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true);

                $.post(wooaiAdmin.ajaxUrl, {
                    action: 'wooai_topup',
                    nonce: wooaiAdmin.nonce
                })
                .done(function(response) {
                    if (response.success && response.data.checkout_url) {
                        window.location.href = response.data.checkout_url;
                    } else {
                        WooAI.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'Top-Up Error',
                            message: response.data.message || wooaiAdmin.strings.error,
                            duration: 4000
                        });
                        $btn.prop('disabled', false);
                    }
                })
                .fail(function() {
                    WooAI.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: wooaiAdmin.strings.error,
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

            $('.wooai-ai-action').on('click', function() {
                var $btn = $(this);
                var action = $btn.data('action');
                var productId = $btn.data('product-id');

                // Check balance first
                var balance = parseInt($('#wooai-balance-count').text().replace(/,/g, ''), 10);
                if (balance < 50) {
                    self.showRichToast({
                        type: 'warning',
                        icon: 'dashicons-money-alt',
                        title: 'Low Balance',
                        message: wooaiAdmin.strings.lowBalance || 'Your token balance is too low. Please top up to continue.',
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
                action: 'wooai_' + action,
                nonce: wooaiAdmin.nonce,
                product_id: productId
            };

            // Add extra data based on action type
            if (action === 'generate_content') {
                data.ai_action = $btn.data('ai-action') || 'improve';
            }

            $.post(wooaiAdmin.ajaxUrl, data)
                .done(function(response) {
                    if (response.success) {
                        self.showResultModal(action, response.data);
                    } else {
                        self.showRichToast({
                            type: 'error',
                            icon: 'dashicons-warning',
                            title: 'AI Action Failed',
                            message: response.data.message || wooaiAdmin.strings.error,
                            duration: 4000
                        });
                    }
                })
                .fail(function() {
                    self.showRichToast({
                        type: 'error',
                        icon: 'dashicons-warning',
                        title: 'Connection Error',
                        message: wooaiAdmin.strings.error,
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
            var $modal = $('#wooai-result-modal');
            var $title = $('#wooai-modal-title');
            var $body = $('#wooai-modal-body');
            var $tokensUsed = $('.wooai-tokens-used');

            // Update balance display
            if (data.new_balance !== undefined) {
                $('#wooai-balance-count').text(data.new_balance.toLocaleString());
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
            tb_show('AI Result', '#TB_inline?inlineId=wooai-result-modal&width=550&height=500');
        },

        /**
         * Build content result HTML
         */
        buildContentResult: function(result) {
            var html = '';

            if (result.title) {
                html += '<div class="wooai-result-field">';
                html += '<label>Title</label>';
                html += '<input type="text" id="wooai-result-title" value="' + this.escapeHtml(result.title) + '" class="large-text">';
                html += '</div>';
            }

            if (result.description) {
                html += '<div class="wooai-result-field">';
                html += '<label>Description</label>';
                html += '<textarea id="wooai-result-description" rows="6" class="large-text">' + this.escapeHtml(result.description) + '</textarea>';
                html += '</div>';
            }

            if (result.short_description) {
                html += '<div class="wooai-result-field">';
                html += '<label>Short Description</label>';
                html += '<textarea id="wooai-result-short-desc" rows="2" class="large-text">' + this.escapeHtml(result.short_description) + '</textarea>';
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
                html += '<div class="wooai-result-field">';
                html += '<label>Suggested Categories</label>';
                html += '<div class="wooai-result-tags">';
                data.categories.forEach(function(cat) {
                    html += '<span class="wooai-result-tag selected" data-type="category">' + cat + '</span>';
                });
                html += '</div></div>';
            }

            if (data.tags && data.tags.length) {
                html += '<div class="wooai-result-field">';
                html += '<label>Suggested Tags</label>';
                html += '<div class="wooai-result-tags">';
                data.tags.forEach(function(tag) {
                    html += '<span class="wooai-result-tag selected" data-type="tag">' + tag + '</span>';
                });
                html += '</div></div>';
            }

            return html;
        },

        /**
         * Build image result HTML
         */
        buildImageResult: function(data) {
            var html = '<div class="wooai-result-field" style="text-align: center;">';
            html += '<img src="' + data.image_url + '" class="wooai-result-image" alt="Generated image">';
            html += '</div>';
            return html;
        },

        /**
         * Bind modal events
         */
        bindModalEvents: function() {
            var self = this;

            // Tag selection toggle
            $(document).on('click', '.wooai-result-tag', function() {
                $(this).toggleClass('selected');
            });

            // Discard button
            $('#wooai-modal-discard').on('click', function() {
                tb_remove();
            });

            // Apply button
            $('#wooai-modal-apply').on('click', function() {
                var $modal = $('#wooai-result-modal');
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
            var $display = $('#wooai-api-key-display');
            var $toggleBtn = $('#wooai-toggle-key');
            var $copyBtn = $('#wooai-copy-key');

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
                    $(this).attr('title', wooaiAdmin.strings.showKey || 'Show API Key');
                } else {
                    // Show the key
                    $display.text($display.data('full'));
                    $display.data('visible', true);
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    $(this).attr('title', wooaiAdmin.strings.hideKey || 'Hide API Key');
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
                    message: wooaiAdmin.strings.copyError || 'Failed to copy to clipboard.',
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
            $btn.addClass('wooai-api-key__btn--success');

            // Show toast notification
            this.showToast(wooaiAdmin.strings.copied || 'Copied to clipboard!');

            // Revert after 2 seconds
            setTimeout(function() {
                $icon.removeClass(successClass).addClass(originalClass);
                $btn.removeClass('wooai-api-key__btn--success');
            }, 2000);
        },

        /**
         * Show simple toast notification (backwards compatible)
         */
        showToast: function(message, type) {
            var typeClass = type ? ' wooai-toast--' + type : '';
            var $toast = $('<div class="wooai-toast wooai-toast--simple' + typeClass + '">' + this.escapeHtml(message) + '</div>');

            // Remove any existing toast
            $('.wooai-toast').remove();

            // Add toast to body
            $('body').append($toast);

            // Trigger animation
            setTimeout(function() {
                $toast.addClass('wooai-toast--visible');
            }, 10);

            // Auto-hide after 2 seconds
            setTimeout(function() {
                $toast.addClass('wooai-toast--hiding');
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
            var html = '<div class="wooai-toast wooai-toast--' + type + '">';
            html += '<div class="wooai-toast__icon"><span class="dashicons ' + icon + '"></span></div>';
            html += '<div class="wooai-toast__content">';
            if (title) {
                html += '<h4 class="wooai-toast__title">' + self.escapeHtml(title) + '</h4>';
            }
            if (message) {
                html += '<p class="wooai-toast__message">' + self.escapeHtml(message) + '</p>';
            }
            html += '</div>';
            html += '<button type="button" class="wooai-toast__close"><span class="dashicons dashicons-no-alt"></span></button>';
            html += '<div class="wooai-toast__progress"><div class="wooai-toast__progress-bar" style="animation-duration: ' + duration + 'ms;"></div></div>';
            html += '</div>';

            var $toast = $(html);

            // Remove any existing toast
            $('.wooai-toast').remove();

            // Add toast to body
            $('body').append($toast);

            // Bind close button
            $toast.find('.wooai-toast__close').on('click', function() {
                self.hideToast($toast);
            });

            // Trigger animation
            setTimeout(function() {
                $toast.addClass('wooai-toast--visible');
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
            if (!$toast.length || $toast.hasClass('wooai-toast--hiding')) {
                return;
            }
            $toast.addClass('wooai-toast--hiding');
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
                message: wooaiAdmin.strings.success || 'Changes have been applied to the product.',
                duration: 3000
            });
        },

        /**
         * Apply content result to product form
         */
        applyContentResult: function() {
            var title = $('#wooai-result-title').val();
            var description = $('#wooai-result-description').val();
            var shortDesc = $('#wooai-result-short-desc').val();

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
            $('.wooai-result-tag.selected[data-type="tag"]').each(function() {
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
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        WooAI.init();
    });

})(jQuery);
