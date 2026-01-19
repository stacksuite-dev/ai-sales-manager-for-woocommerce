/**
 * WooAI Chat Interface
 *
 * @package WooAI_Sales_Manager
 */

(function($) {
	'use strict';

	// Chat state
	const state = {
		sessionId: null,
		entityType: 'product', // 'product' | 'category' | 'agent'
		selectedProduct: null,
		selectedCategory: null,
		isAgentMode: false,
		products: [],
		categories: [],
		messages: [],
		pendingSuggestions: {},
		isLoading: false,
		balance: 0,
		storeContext: {},
		// Attachment state
		pendingAttachments: [],
		maxAttachments: 5,
		maxFileSize: 7 * 1024 * 1024, // 7MB
		maxImageSize: 1024 * 1024, // 1MB - resize images larger than this
		allowedMimeTypes: ['image/png', 'image/jpeg', 'image/webp', 'image/heic', 'image/heif', 'application/pdf']
	};

	// DOM Elements
	const elements = {
		// Entity switcher
		entityTabs: null,
		entitySelect: null,
		
		// Chat elements
		messageInput: null,
		sendButton: null,
		messagesContainer: null,
		chatWelcome: null,
		
		// Quick actions
		quickActionsProduct: null,
		quickActionsCategory: null,
		quickActionsAgent: null,
		
		// Entity panels
		entityPanel: null,
		entityEmpty: null,
		productInfo: null,
		categoryInfo: null,
		agentInfo: null,
		
		// Pending changes
		pendingSummary: null,
		categoryPendingSummary: null,
		
		// Header elements
		balanceDisplay: null,
		newChatButton: null,
		tokensUsed: null,
		
		// Store context
		storeContextBtn: null,
		contextPanel: null,
		contextBackdrop: null,
		contextForm: null,
		
		// Onboarding
		onboardingOverlay: null,
		
		// Welcome cards
		welcomeCards: null
	};

	// Templates
	const templates = {};

	/**
	 * Initialize the chat interface
	 */
	function init() {
		// Cache DOM elements
		cacheElements();

		// Load templates
		loadTemplates();

		// Initialize state from localized data
		if (typeof wooaiChat !== 'undefined') {
			state.products = wooaiChat.products || [];
			state.categories = wooaiChat.categories || [];
			state.balance = parseInt(wooaiChat.balance, 10) || 0;
			state.storeContext = wooaiChat.storeContext || {};
		}

		// Check for preselected entity type
		if (typeof window.wooaiPreselectedEntityType !== 'undefined') {
			state.entityType = window.wooaiPreselectedEntityType;
		}

		// Populate entity selector based on type
		populateEntitySelector();

		// Bind events
		bindEvents();

		// Check for preselected product
		if (typeof window.wooaiPreselectedProduct !== 'undefined') {
			state.entityType = 'product';
			updateEntityTabs();
			selectProduct(window.wooaiPreselectedProduct);
		}

		// Check for preselected category
		if (typeof window.wooaiPreselectedCategory !== 'undefined') {
			state.entityType = 'category';
			updateEntityTabs();
			selectCategory(window.wooaiPreselectedCategory);
		}

		// Auto-resize textarea
		autoResizeTextarea();

		// Update UI based on initial entity type
		updateEntityTabs();
		updateQuickActionsVisibility();
	}

	/**
	 * Cache DOM elements
	 */
	function cacheElements() {
		// Entity switcher
		elements.entityTabs = $('.wooai-entity-tabs');
		elements.entitySelect = $('#wooai-entity-select');
		
		// Chat elements
		elements.messageInput = $('#wooai-message-input');
		elements.sendButton = $('#wooai-send-message');
		elements.messagesContainer = $('#wooai-chat-messages');
		elements.chatWelcome = $('#wooai-chat-welcome');
		
		// Quick actions
		elements.quickActionsProduct = $('#wooai-quick-actions-product');
		elements.quickActionsCategory = $('#wooai-quick-actions-category');
		elements.quickActionsAgent = $('#wooai-quick-actions-agent');
		
		// Entity panels
		elements.entityPanel = $('#wooai-entity-panel');
		elements.entityEmpty = $('#wooai-entity-empty');
		elements.productInfo = $('#wooai-product-info');
		elements.categoryInfo = $('#wooai-category-info');
		elements.agentInfo = $('#wooai-agent-info');
		
		// Pending changes
		elements.pendingSummary = $('#wooai-pending-summary');
		elements.categoryPendingSummary = $('#wooai-category-pending-summary');
		
		// Header elements
		elements.balanceDisplay = $('#wooai-balance-display');
		elements.newChatButton = $('#wooai-new-chat');
		elements.tokensUsed = $('#wooai-tokens-used');
		
		// Store context
		elements.storeContextBtn = $('#wooai-open-context');
		elements.contextPanel = $('#wooai-context-panel');
		elements.contextBackdrop = $('#wooai-context-backdrop');
		elements.contextForm = $('#wooai-context-form');
		
		// Onboarding
		elements.onboardingOverlay = $('#wooai-onboarding');
		
		// Welcome cards
		elements.welcomeCards = $('.wooai-welcome-cards');
		
		// Attachment elements
		elements.attachButton = $('#wooai-attach-button');
		elements.fileInput = $('#wooai-file-input');
		elements.attachmentPreviews = $('#wooai-attachment-previews');
	}

	/**
	 * Load template strings
	 */
	function loadTemplates() {
		templates.message = $('#wooai-message-template').html() || '';
		templates.suggestion = $('#wooai-suggestion-template').html() || '';
		templates.thinking = $('#wooai-thinking-template').html() || '';
	}

	/**
	 * Populate product selector dropdown
	 */
	function populateProductSelector() {
		const $select = elements.entitySelect;
		$select.empty();
		$select.append($('<option>', {
			value: '',
			text: wooaiChat.i18n.selectProduct
		}));

		if (state.products.length === 0) {
			$select.append($('<option>', {
				value: '',
				text: wooaiChat.i18n.noProducts,
				disabled: true
			}));
			return;
		}

		state.products.forEach(function(product) {
			$select.append($('<option>', {
				value: product.id,
				text: product.title,
				'data-product': JSON.stringify(product)
			}));
		});
	}

	/**
	 * Populate category selector dropdown
	 */
	function populateCategorySelector() {
		const $select = elements.entitySelect;
		$select.empty();
		$select.append($('<option>', {
			value: '',
			text: wooaiChat.i18n.selectCategory || 'Select a category...'
		}));

		if (state.categories.length === 0) {
			$select.append($('<option>', {
				value: '',
				text: wooaiChat.i18n.noCategories || 'No categories found',
				disabled: true
			}));
			return;
		}

		state.categories.forEach(function(category) {
			const indent = '— '.repeat(category.depth || 0);
			$select.append($('<option>', {
				value: category.id,
				text: indent + category.name,
				'data-category': JSON.stringify(category)
			}));
		});
	}

	/**
	 * Populate entity selector based on current entity type
	 */
	function populateEntitySelector() {
		if (state.entityType === 'agent') {
			// Agent mode doesn't need entity selector
			elements.entitySelect.empty().hide();
			return;
		}
		
		elements.entitySelect.show();
		
		if (state.entityType === 'category') {
			populateCategorySelector();
		} else {
			populateProductSelector();
		}
	}

	/**
	 * Bind event handlers
	 */
	function bindEvents() {
		// Entity type tabs
		elements.entityTabs.on('click', '.wooai-entity-tab', handleEntityTabClick);

		// Entity selection
		elements.entitySelect.on('change', handleEntityChange);

		// Send message
		elements.sendButton.on('click', handleSendMessage);
		elements.messageInput.on('keydown', function(e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				handleSendMessage();
			}
		});

		// Quick actions - Product
		elements.quickActionsProduct.on('click', '[data-action]', handleQuickAction);
		
		// Quick actions - Category
		elements.quickActionsCategory.on('click', '[data-action]', handleCategoryQuickAction);
		
		// Quick actions - Agent
		elements.quickActionsAgent.on('click', '[data-action]', handleAgentQuickAction);

		// New chat
		elements.newChatButton.on('click', handleNewChat);

		// Accept/Discard all - Products
		$('#wooai-accept-all').on('click', handleAcceptAll);
		$('#wooai-discard-all').on('click', handleDiscardAll);
		
		// Accept/Discard all - Categories
		$('#wooai-category-accept-all').on('click', handleAcceptAll);
		$('#wooai-category-discard-all').on('click', handleDiscardAll);

		// Suggestion actions (delegated)
		elements.messagesContainer.on('click', '.wooai-suggestion [data-action]', handleSuggestionAction);

		// Product panel suggestion actions
		elements.productInfo.on('click', '.wooai-pending-change__actions [data-action]', handlePendingChangeAction);
		
		// Category panel suggestion actions
		elements.categoryInfo.on('click', '.wooai-pending-change__actions [data-action]', handlePendingChangeAction);

		// Expand toggle for description
		elements.entityPanel.on('click', '.wooai-expand-toggle', handleExpandToggle);

		// Store context panel
		elements.storeContextBtn.on('click', openContextPanel);
		$('#wooai-close-context, #wooai-cancel-context').on('click', closeContextPanel);
		elements.contextBackdrop.on('click', closeContextPanel);
		$('#wooai-save-context').on('click', saveStoreContext);
		$('#wooai-sync-context').on('click', syncStoreContext);

		// Onboarding
		$('#wooai-onboarding-setup').on('click', function() {
			closeOnboarding();
			openContextPanel();
		});
		$('#wooai-onboarding-skip').on('click', closeOnboarding);

		// Welcome cards
		elements.welcomeCards.on('click', '.wooai-welcome-card', handleWelcomeCardClick);
		
		// Welcome context hint
		$('#wooai-welcome-setup-context').on('click', openContextPanel);
		
		// Attachment handling
		elements.attachButton.on('click', function() {
			elements.fileInput.click();
		});
		elements.fileInput.on('change', handleFileSelect);
		elements.attachmentPreviews.on('click', '.wooai-attachment-remove', handleRemoveAttachment);
		
		// Drag and drop
		setupDragAndDrop();
		
		// Paste handling for images
		elements.messageInput.on('paste', handlePaste);
	}

	/**
	 * Handle file selection from input
	 */
	function handleFileSelect(e) {
		const files = Array.from(e.target.files || []);
		processFiles(files);
		// Reset input so same file can be selected again
		e.target.value = '';
	}

	/**
	 * Handle removing an attachment
	 */
	function handleRemoveAttachment(e) {
		const index = $(e.currentTarget).closest('.wooai-attachment-preview').data('index');
		state.pendingAttachments.splice(index, 1);
		updateAttachmentPreviews();
	}

	/**
	 * Handle paste event for images
	 */
	function handlePaste(e) {
		const clipboardData = e.originalEvent.clipboardData;
		if (!clipboardData || !clipboardData.items) return;

		const items = Array.from(clipboardData.items);
		const imageItems = items.filter(item => item.type.startsWith('image/'));
		
		if (imageItems.length > 0) {
			e.preventDefault();
			const files = imageItems.map(item => item.getAsFile()).filter(Boolean);
			processFiles(files);
		}
	}

	/**
	 * Setup drag and drop for attachments
	 */
	function setupDragAndDrop() {
		const $dropZone = $('.wooai-chat-input');
		
		$dropZone.on('dragenter dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).addClass('wooai-drag-over');
		});

		$dropZone.on('dragleave drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).removeClass('wooai-drag-over');
		});

		$dropZone.on('drop', function(e) {
			const files = Array.from(e.originalEvent.dataTransfer.files || []);
			processFiles(files);
		});
	}

	/**
	 * Process selected/dropped/pasted files
	 */
	function processFiles(files) {
		if (!files || files.length === 0) return;

		// Check max attachments
		const remainingSlots = state.maxAttachments - state.pendingAttachments.length;
		if (remainingSlots <= 0) {
			showNotice('Maximum ' + state.maxAttachments + ' files allowed per message', 'error');
			return;
		}

		const filesToProcess = files.slice(0, remainingSlots);

		filesToProcess.forEach(function(file) {
			// Validate file type
			if (!state.allowedMimeTypes.includes(file.type)) {
				showNotice('File type not supported: ' + file.name, 'error');
				return;
			}

			// Validate file size
			if (file.size > state.maxFileSize) {
				showNotice('File too large: ' + file.name + ' (max ' + Math.round(state.maxFileSize / 1024 / 1024) + 'MB)', 'error');
				return;
			}

			// Process the file
			if (file.type.startsWith('image/') && file.size > state.maxImageSize) {
				// Resize large images
				resizeImage(file).then(function(resizedFile) {
					addAttachment(resizedFile);
				}).catch(function(err) {
					console.error('Image resize failed:', err);
					// Fall back to original
					addAttachment(file);
				});
			} else {
				addAttachment(file);
			}
		});
	}

	/**
	 * Add an attachment to pending list
	 */
	function addAttachment(file) {
		fileToBase64(file).then(function(base64Data) {
			state.pendingAttachments.push({
				file: file,
				filename: file.name,
				mime_type: file.type,
				data: base64Data,
				preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null
			});
			updateAttachmentPreviews();
		}).catch(function(err) {
			console.error('Failed to read file:', err);
			showNotice('Failed to read file: ' + file.name, 'error');
		});
	}

	/**
	 * Convert file to base64
	 */
	function fileToBase64(file) {
		return new Promise(function(resolve, reject) {
			const reader = new FileReader();
			reader.onload = function() {
				// Remove data URL prefix to get pure base64
				const base64 = reader.result.split(',')[1];
				resolve(base64);
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}

	/**
	 * Resize image if too large (browser-side)
	 */
	function resizeImage(file) {
		return new Promise(function(resolve, reject) {
			const img = new Image();
			img.onload = function() {
				// Calculate new dimensions (max 1920px on longest side)
				const maxDim = 1920;
				let width = img.width;
				let height = img.height;

				if (width > maxDim || height > maxDim) {
					if (width > height) {
						height = Math.round((height / width) * maxDim);
						width = maxDim;
					} else {
						width = Math.round((width / height) * maxDim);
						height = maxDim;
					}
				}

				// Create canvas and resize
				const canvas = document.createElement('canvas');
				canvas.width = width;
				canvas.height = height;
				const ctx = canvas.getContext('2d');
				ctx.drawImage(img, 0, 0, width, height);

				// Convert to blob
				canvas.toBlob(function(blob) {
					if (blob) {
						// Create new file with same name
						const resizedFile = new File([blob], file.name, {
							type: 'image/jpeg',
							lastModified: Date.now()
						});
						resolve(resizedFile);
					} else {
						reject(new Error('Failed to create blob'));
					}
				}, 'image/jpeg', 0.85);
			};
			img.onerror = reject;
			img.src = URL.createObjectURL(file);
		});
	}

	/**
	 * Update attachment previews UI
	 */
	function updateAttachmentPreviews() {
		const $container = elements.attachmentPreviews;
		$container.empty();

		if (state.pendingAttachments.length === 0) {
			$container.hide();
			elements.attachButton.find('.wooai-attachment-count').remove();
			return;
		}

		state.pendingAttachments.forEach(function(attachment, index) {
			const isImage = attachment.mime_type.startsWith('image/');
			const isPdf = attachment.mime_type === 'application/pdf';

			let previewHtml = '<div class="wooai-attachment-preview" data-index="' + index + '">';
			
			if (isImage && attachment.preview) {
				previewHtml += '<img src="' + attachment.preview + '" alt="' + escapeHtml(attachment.filename) + '">';
			} else if (isPdf) {
				previewHtml += '<div class="wooai-attachment-preview__icon"><span class="dashicons dashicons-pdf"></span></div>';
			} else {
				previewHtml += '<div class="wooai-attachment-preview__icon"><span class="dashicons dashicons-media-default"></span></div>';
			}

			previewHtml += '<div class="wooai-attachment-preview__name">' + escapeHtml(truncate(attachment.filename, 15)) + '</div>';
			previewHtml += '<button type="button" class="wooai-attachment-remove" title="Remove"><span class="dashicons dashicons-no-alt"></span></button>';
			previewHtml += '</div>';

			$container.append(previewHtml);
		});

		$container.show();

		// Update attachment count on button
		let $count = elements.attachButton.find('.wooai-attachment-count');
		if ($count.length === 0) {
			$count = $('<span class="wooai-attachment-count"></span>');
			elements.attachButton.append($count);
		}
		$count.text(state.pendingAttachments.length);
	}

	/**
	 * Clear all pending attachments
	 */
	function clearAttachments() {
		// Revoke object URLs to prevent memory leaks
		state.pendingAttachments.forEach(function(attachment) {
			if (attachment.preview) {
				URL.revokeObjectURL(attachment.preview);
			}
		});
		state.pendingAttachments = [];
		updateAttachmentPreviews();
	}

	/**
	 * Handle entity tab click (switch between Products/Categories/Agent)
	 */
	function handleEntityTabClick(e) {
		const $tab = $(e.currentTarget);
		const newType = $tab.data('type');
		
		if (newType === state.entityType) return;
		
		// Switch entity type
		state.entityType = newType;
		state.isAgentMode = (newType === 'agent');
		
		// Update tabs UI
		updateEntityTabs();
		
		// Clear current selection (skip for agent - selectAgent handles cleanup)
		if (newType !== 'agent') {
			clearEntity();
		}
		
		// Populate selector with new entity type (or hide for agent)
		populateEntitySelector();
		
		// Update quick actions visibility
		updateQuickActionsVisibility();
		
		// Update empty state text and icon
		updateEmptyState();
		
		// If agent mode, activate immediately
		if (state.entityType === 'agent') {
			selectAgent();
		}
	}

	/**
	 * Update entity tabs UI
	 */
	function updateEntityTabs() {
		elements.entityTabs.find('.wooai-entity-tab').removeClass('wooai-entity-tab--active');
		elements.entityTabs.find('[data-type="' + state.entityType + '"]').addClass('wooai-entity-tab--active');
	}

	/**
	 * Update quick actions visibility based on entity type
	 */
	function updateQuickActionsVisibility() {
		elements.quickActionsProduct.hide();
		elements.quickActionsCategory.hide();
		elements.quickActionsAgent.hide();
		
		if (state.entityType === 'agent') {
			elements.quickActionsAgent.show();
		} else if (state.entityType === 'category') {
			elements.quickActionsCategory.show();
		} else {
			elements.quickActionsProduct.show();
		}
	}

	/**
	 * Update empty state text and icon
	 */
	function updateEmptyState() {
		const $icon = $('#wooai-empty-icon');
		const $text = $('#wooai-empty-text');
		
		if (state.entityType === 'agent') {
			$icon.attr('class', 'dashicons dashicons-superhero-alt');
			$text.text(wooaiChat.i18n.agentReady || 'AI Agent ready to help with marketing');
		} else if (state.entityType === 'category') {
			$icon.attr('class', 'dashicons dashicons-category');
			$text.text(wooaiChat.i18n.selectCategory || 'Select a category to view details');
		} else {
			$icon.attr('class', 'dashicons dashicons-products');
			$text.text(wooaiChat.i18n.selectProduct || 'Select a product to view details');
		}
	}

	/**
	 * Handle entity selection change
	 */
	function handleEntityChange() {
		const entityId = elements.entitySelect.val();
		if (!entityId) {
			clearEntity();
			return;
		}

		const $selected = elements.entitySelect.find(':selected');
		
		if (state.entityType === 'category') {
			const categoryData = $selected.data('category');
			if (categoryData) {
				selectCategory(categoryData);
			}
		} else {
			const productData = $selected.data('product');
			if (productData) {
				selectProduct(productData);
			}
		}
	}

	/**
	 * Handle product selection change (legacy support)
	 */
	function handleProductChange() {
		handleEntityChange();
	}

	/**
	 * Select a product and create/load session
	 */
	function selectProduct(product) {
		state.selectedProduct = product;
		state.selectedCategory = null;
		state.isAgentMode = false;
		updateProductPanel(product);
		enableInputs();

		// Hide welcome, show product info
		elements.chatWelcome.hide();
		elements.entityEmpty.hide();
		elements.productInfo.show();
		elements.categoryInfo.hide();
		elements.agentInfo.hide();

		// Create new session for this product
		createSession(product, 'product');
	}

	/**
	 * Select a category and create/load session
	 */
	function selectCategory(category) {
		state.selectedCategory = category;
		state.selectedProduct = null;
		state.isAgentMode = false;
		updateCategoryPanel(category);
		enableInputs();

		// Hide welcome, show category info
		elements.chatWelcome.hide();
		elements.entityEmpty.hide();
		elements.productInfo.hide();
		elements.categoryInfo.show();
		elements.agentInfo.hide();

		// Create new session for this category
		createSession(category, 'category');
	}

	/**
	 * Select agent mode (store-wide marketing operations)
	 */
	function selectAgent() {
		// Clear previous state
		state.selectedProduct = null;
		state.selectedCategory = null;
		state.sessionId = null;
		state.messages = [];
		state.pendingSuggestions = {};
		state.isAgentMode = true;
		
		// Clear messages display
		clearMessages();
		
		// Enable inputs for agent mode
		enableInputs();

		// Hide welcome and other panels, show agent info
		elements.chatWelcome.hide();
		elements.entityEmpty.hide();
		elements.productInfo.hide();
		elements.categoryInfo.hide();
		elements.agentInfo.show();
		elements.pendingSummary.hide();

		// Create new session for agent mode
		createSession(null, 'agent');
	}

	/**
	 * Load and select a category by ID
	 */
	function loadAndSelectCategory(categoryId) {
		// Find category in state
		const category = state.categories.find(c => c.id == categoryId);
		if (category) {
			elements.entitySelect.val(categoryId);
			selectCategory(category);
		} else {
			// Fetch category data via AJAX
			$.ajax({
				url: wooaiChat.ajaxUrl,
				method: 'POST',
				data: {
					action: 'wooai_get_category',
					nonce: wooaiChat.nonce,
					category_id: categoryId
				},
				success: function(response) {
					if (response.success && response.data) {
						selectCategory(response.data);
					}
				}
			});
		}
	}

	/**
	 * Clear entity selection
	 */
	function clearEntity() {
		if (state.entityType === 'agent') {
			clearAgent();
		} else if (state.entityType === 'category') {
			clearCategory();
		} else {
			clearProduct();
		}
	}

	/**
	 * Clear product selection
	 */
	function clearProduct() {
		state.selectedProduct = null;
		state.sessionId = null;
		state.messages = [];
		state.pendingSuggestions = {};

		elements.productInfo.hide();
		elements.entityEmpty.show();
		elements.pendingSummary.hide();

		disableInputs();
		showWelcomeMessage();
	}

	/**
	 * Clear category selection
	 */
	function clearCategory() {
		state.selectedCategory = null;
		state.sessionId = null;
		state.messages = [];
		state.pendingSuggestions = {};

		elements.categoryInfo.hide();
		elements.entityEmpty.show();
		elements.categoryPendingSummary.hide();

		disableInputs();
		showWelcomeMessage();
	}

	/**
	 * Clear agent mode
	 */
	function clearAgent() {
		state.isAgentMode = false;
		state.sessionId = null;
		state.messages = [];
		state.pendingSuggestions = {};

		elements.agentInfo.hide();
		elements.entityEmpty.show();

		disableInputs();
		showWelcomeMessage();
	}

	/**
	 * Update product info panel
	 */
	function updateProductPanel(product) {
		elements.entityEmpty.hide();
		elements.productInfo.show();

		// Image
		const $image = $('#wooai-product-image');
		if (product.image_url) {
			$image.attr('src', product.image_url).show();
		} else {
			$image.hide();
		}

		// Title
		$('#wooai-product-title').text(product.title || '');

		// Description
		const desc = product.description || '';
		$('#wooai-product-description').html(desc.substring(0, 200) + (desc.length > 200 ? '...' : ''));

		// Short description
		$('#wooai-product-short-description').text(product.short_description || '');

		// Categories
		const categories = product.categories || [];
		if (categories.length > 0) {
			$('#wooai-product-categories').html(
				categories.map(cat => '<span class="wooai-tag">' + escapeHtml(cat) + '</span>').join('')
			);
		} else {
			$('#wooai-product-categories').html('<span class="wooai-no-value">' + (wooaiChat.i18n.none || 'None') + '</span>');
		}

		// Tags
		const tags = product.tags || [];
		if (tags.length > 0) {
			$('#wooai-product-tags').html(
				tags.map(tag => '<span class="wooai-tag">' + escapeHtml(tag) + '</span>').join('')
			);
		} else {
			$('#wooai-product-tags').html('<span class="wooai-no-value">' + (wooaiChat.i18n.none || 'None') + '</span>');
		}

		// Price
		const priceHtml = formatPrice(product.regular_price, product.sale_price, product.price);
		$('#wooai-product-price').html(priceHtml);

		// Stock
		const stockHtml = formatStock(product.stock_status, product.stock_quantity);
		$('#wooai-product-stock').html(stockHtml);

		// Status
		$('#wooai-product-status').html(formatStatus(product.status));

		// Edit/View links
		$('#wooai-edit-product').attr('href', product.edit_url || '#');
		$('#wooai-view-product').attr('href', product.view_url || '#');

		// Set selected in dropdown
		elements.entitySelect.val(product.id);
	}

	/**
	 * Update category info panel
	 */
	function updateCategoryPanel(category) {
		elements.entityEmpty.hide();
		elements.categoryInfo.show();

		// Name
		$('#wooai-category-name').text(category.name || '');
		$('#wooai-category-name-value').text(category.name || '');

		// Parent
		if (category.parent_name) {
			$('#wooai-category-parent').text('in ' + category.parent_name).show();
		} else {
			$('#wooai-category-parent').hide();
		}

		// Stats
		$('#wooai-category-product-count').text(category.product_count || 0);
		$('#wooai-category-subcat-count').text(category.subcategory_count || 0);

		// Description
		const desc = category.description || '';
		if (desc) {
			$('#wooai-category-description').html(desc.substring(0, 200) + (desc.length > 200 ? '...' : ''));
		} else {
			$('#wooai-category-description').html('<span class="wooai-no-value">' + (wooaiChat.i18n.noDescription || 'No description') + '</span>');
		}

		// SEO Title
		if (category.seo_title) {
			$('#wooai-category-seo-title').text(category.seo_title);
		} else {
			$('#wooai-category-seo-title').html('<span class="wooai-no-value">' + (wooaiChat.i18n.notSet || 'Not set') + '</span>');
		}

		// Meta Description
		if (category.meta_description) {
			$('#wooai-category-meta-description').text(category.meta_description);
		} else {
			$('#wooai-category-meta-description').html('<span class="wooai-no-value">' + (wooaiChat.i18n.notSet || 'Not set') + '</span>');
		}

		// Subcategories
		const subcats = category.subcategories || [];
		if (subcats.length > 0) {
			$('#wooai-category-subcategories').html(
				subcats.map(sub => '<span class="wooai-subcat-tag"><span class="dashicons dashicons-category"></span>' + escapeHtml(sub.name) + '</span>').join('')
			);
		} else {
			$('#wooai-category-subcategories').html('<span class="wooai-no-value">' + (wooaiChat.i18n.none || 'None') + '</span>');
		}

		// Edit/View links
		$('#wooai-edit-category').attr('href', category.edit_url || '#');
		$('#wooai-view-category').attr('href', category.view_url || '#');

		// Set selected in dropdown
		elements.entitySelect.val(category.id);
	}

	/**
	 * Create a new chat session
	 */
	function createSession(entity, entityType) {
		if (state.isLoading) return;

		setLoading(true);
		clearMessages();

		const sessionData = {
			entity_type: entityType || state.entityType
		};

		// Handle different entity types
		if (entityType === 'agent') {
			sessionData.title = 'Marketing Agent';
			// Include store context for agent mode
			if (state.storeContext && Object.keys(state.storeContext).length > 0) {
				const filteredContext = {};
				for (const [key, value] of Object.entries(state.storeContext)) {
					if (value !== '' && value !== null && value !== undefined) {
						filteredContext[key] = value;
					}
				}
				if (Object.keys(filteredContext).length > 0) {
					sessionData.store_context = filteredContext;
				}
			}
		} else if (entityType === 'category') {
			sessionData.title = entity.name;
			sessionData.category_id = String(entity.id);
			sessionData.category_data = {
				id: String(entity.id),
				name: entity.name,
				slug: entity.slug,
				description: entity.description,
				parent_id: entity.parent_id,
				parent_name: entity.parent_name,
				product_count: entity.product_count,
				subcategory_count: entity.subcategory_count,
				subcategories: entity.subcategories,
				seo_title: entity.seo_title,
				meta_description: entity.meta_description
			};
			// Include store context if available
			if (state.storeContext && Object.keys(state.storeContext).length > 0) {
				const filteredContext = {};
				for (const [key, value] of Object.entries(state.storeContext)) {
					if (value !== '' && value !== null && value !== undefined) {
						filteredContext[key] = value;
					}
				}
				if (Object.keys(filteredContext).length > 0) {
					sessionData.store_context = filteredContext;
				}
			}
		} else {
			sessionData.title = entity.title;
			sessionData.product_id = String(entity.id);
			sessionData.product_data = {
				id: String(entity.id),
				title: entity.title,
				description: entity.description,
				short_description: entity.short_description,
				price: entity.price,
				sale_price: entity.sale_price,
				sku: entity.sku,
				stock_status: entity.stock_status,
				stock_quantity: entity.stock_quantity,
				categories: entity.categories,
				tags: entity.tags,
				image_url: entity.image_url,
				status: entity.status
			};
			// Include store context if available
			if (state.storeContext && Object.keys(state.storeContext).length > 0) {
				const filteredContext = {};
				for (const [key, value] of Object.entries(state.storeContext)) {
					if (value !== '' && value !== null && value !== undefined) {
						filteredContext[key] = value;
					}
				}
				if (Object.keys(filteredContext).length > 0) {
					sessionData.store_context = filteredContext;
				}
			}
		}

		$.ajax({
			url: wooaiChat.apiBaseUrl + '/ai/chat/sessions',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': wooaiChat.apiKey
			},
			data: JSON.stringify(sessionData),
			success: function(response) {
				// Handle both direct API response and wrapped response
				const session = response.session || (response.data && response.data.session);
				if (session) {
					state.sessionId = session.id;
					loadSessionMessages(state.sessionId);
				} else {
					setLoading(false);
				}
			},
			error: function(xhr) {
				handleError(xhr);
				setLoading(false);
			}
		});
	}

	/**
	 * Load messages for a session
	 */
	function loadSessionMessages(sessionId) {
		$.ajax({
			url: wooaiChat.apiBaseUrl + '/ai/chat/sessions/' + sessionId,
			method: 'GET',
			headers: {
				'X-API-Key': wooaiChat.apiKey
			},
			success: function(response) {
				// Handle both direct API response and wrapped response
				const data = response.data || response;
				state.messages = data.messages || [];
				state.pendingSuggestions = {};

				// Track pending suggestions
				(data.pending_suggestions || []).forEach(function(suggestion) {
					state.pendingSuggestions[suggestion.id] = suggestion;
				});

				renderMessages();
				updatePendingSummary();
				setLoading(false);
			},
			error: function(xhr) {
				handleError(xhr);
				setLoading(false);
			}
		});
	}

	/**
	 * Handle send message
	 */
	function handleSendMessage() {
		const content = elements.messageInput.val().trim();
		if (!content || !state.sessionId || state.isLoading) return;

		sendMessage(content);
	}

	/**
	 * Send a message to the chat
	 */
	function sendMessage(content, quickAction) {
		if (state.isLoading) return;

		setLoading(true);
		elements.messageInput.val('');

		// Capture attachments before clearing
		const attachments = state.pendingAttachments.map(function(a) {
			return {
				filename: a.filename,
				mime_type: a.mime_type,
				data: a.data
			};
		});

		// Add user message to UI immediately (with attachment indicators)
		const messageData = {
			id: 'temp-' + Date.now(),
			role: 'user',
			content: content,
			created_at: new Date().toISOString(),
			attachments: attachments.length > 0 ? attachments : undefined
		};
		addMessage(messageData);

		// Clear attachments after capturing
		clearAttachments();

		// Show thinking indicator
		showThinking();

		// Build request data
		const requestData = {
			content: content
		};

		if (quickAction) {
			requestData.quick_action = quickAction;
		}

		if (attachments.length > 0) {
			requestData.attachments = attachments;
		}

		// Try SSE first, fall back to regular request
		sendMessageSSE(requestData);
	}

	/**
	 * Send message with SSE streaming
	 */
	function sendMessageSSE(requestData) {
		const url = wooaiChat.apiBaseUrl + '/ai/chat/sessions/' + state.sessionId + '/messages';

		// Use fetch with streaming
		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': wooaiChat.apiKey,
				'Accept': 'text/event-stream'
			},
			body: JSON.stringify(requestData)
		})
		.then(function(response) {
			if (!response.ok) {
				throw new Error('Network response was not ok');
			}

			const contentType = response.headers.get('content-type');

			// Check if SSE response
			if (contentType && contentType.includes('text/event-stream')) {
				return handleSSEResponse(response);
			} else {
				// Regular JSON response
				return response.json().then(function(data) {
					hideThinking();
					// Handle both wrapped {success, data} and direct response formats
					const responseData = data.data || data;
					if (responseData.assistant_message || responseData.suggestions) {
						handleMessageResponse(responseData);
					}
					setLoading(false);
				});
			}
		})
		.catch(function(error) {
			console.error('Chat error:', error);
			hideThinking();
			addErrorMessage(wooaiChat.i18n.connectionError);
			setLoading(false);
		});
	}

	/**
	 * Handle SSE streaming response
	 */
	function handleSSEResponse(response) {
		const reader = response.body.getReader();
		const decoder = new TextDecoder();
		let buffer = '';
		let assistantMessage = null;
		let messageContent = '';

		function processChunk(chunk) {
			buffer += decoder.decode(chunk, { stream: true });

			// Process complete events
			const lines = buffer.split('\n');
			buffer = lines.pop() || ''; // Keep incomplete line

			let eventType = null;
			let eventData = null;

			lines.forEach(function(line) {
				if (line.startsWith('event:')) {
					eventType = line.substring(6).trim();
				} else if (line.startsWith('data:')) {
					const dataStr = line.substring(5).trim();
					try {
						eventData = JSON.parse(dataStr);
						handleSSEEvent(eventType, eventData);
					} catch (e) {
						// Not valid JSON, ignore
					}
					eventType = null;
					eventData = null;
				}
			});
		}

		function handleSSEEvent(event, data) {
			switch (event) {
				case 'message_start':
					assistantMessage = {
						id: data.id,
						role: 'assistant',
						content: '',
						created_at: new Date().toISOString()
					};
					break;

				case 'content_delta':
					if (data.delta) {
						// On first content, hide thinking and add the message bubble
						if (!messageContent && assistantMessage) {
							hideThinking();
							addMessage(assistantMessage, true);
						}
						messageContent += data.delta;
						updateStreamingMessage(messageContent);
					}
					break;

				case 'suggestion':
					if (data.suggestion) {
						handleSuggestionReceived(data.suggestion);
					}
					break;

				case 'usage':
					if (data.tokens) {
						updateTokensUsed(data.tokens);
						// Don't update balance here - wait for balance_update event with accurate value
					}
					break;

				case 'balance_update':
					if (typeof data.new_balance !== 'undefined') {
						// Animate the balance change
						animateBalance(data.new_balance);
						// Sync to WordPress for page refresh persistence
						syncBalanceToWordPress(data.new_balance);
					}
					break;

				case 'message_end':
					if (assistantMessage) {
						assistantMessage.content = messageContent;
						state.messages.push(assistantMessage);
					}
					messageContent = '';
					break;

				case 'data_request':
					// AI is requesting data from WordPress
					if (data.requests && data.requests.length > 0) {
						// Show interim message if provided
						if (data.interim_message) {
							showToolProcessingMessage(data.interim_message);
						}
						// Fetch tool data and continue conversation
						handleToolDataRequests(data.requests);
					}
					break;

				case 'tool_processing':
					// Status update about tool processing
					if (data.message) {
						showToolProcessingMessage(data.message);
					}
					break;

				case 'done':
					setLoading(false);
					break;

				case 'error':
					hideThinking();
					addErrorMessage(data.message || wooaiChat.i18n.errorOccurred);
					setLoading(false);
					break;
			}
		}

		function read() {
			return reader.read().then(function(result) {
				if (result.done) {
					setLoading(false);
					return;
				}
				processChunk(result.value);
				return read();
			});
		}

		return read();
	}

	/**
	 * Show tool processing status message
	 * Displays a temporary status indicator while AI tools fetch data
	 */
	function showToolProcessingMessage(message) {
		// Remove any existing tool processing message
		elements.messagesContainer.find('.wooai-message--tool-processing').remove();

		const $toolMessage = $(
			'<div class="wooai-message wooai-message--tool-processing">' +
				'<div class="wooai-message__avatar"><span class="dashicons dashicons-database"></span></div>' +
				'<div class="wooai-message__content">' +
					'<div class="wooai-message__text">' +
						'<span class="wooai-tool-spinner"></span> ' +
						escapeHtml(message) +
					'</div>' +
				'</div>' +
			'</div>'
		);

		elements.messagesContainer.append($toolMessage);
		scrollToBottom();
	}

	/**
	 * Hide tool processing message
	 */
	function hideToolProcessingMessage() {
		elements.messagesContainer.find('.wooai-message--tool-processing').remove();
	}

	/**
	 * Handle tool data requests from AI
	 * Fetches data via WordPress AJAX and sends results back to API
	 */
	function handleToolDataRequests(requests) {
		if (!requests || requests.length === 0) return;

		// Call WordPress AJAX to execute the tools
		$.ajax({
			url: wooaiChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'wooai_fetch_tool_data',
				nonce: wooaiChat.nonce,
				requests: JSON.stringify(requests)
			},
			success: function(response) {
				hideToolProcessingMessage();

				if (response.success && response.data && response.data.tool_results) {
					// Send tool results back to API to continue conversation
					sendToolResults(response.data.tool_results);
				} else {
					// Handle error - send error results back
					const errorResults = requests.map(function(req) {
						return {
							request_id: req.request_id,
							tool: req.tool,
							success: false,
							error: response.data || 'Failed to fetch tool data'
						};
					});
					sendToolResults(errorResults);
				}
			},
			error: function(xhr) {
				hideToolProcessingMessage();
				console.error('Tool data fetch error:', xhr);

				// Send error results back to API
				const errorResults = requests.map(function(req) {
					return {
						request_id: req.request_id,
						tool: req.tool,
						success: false,
						error: 'Network error fetching tool data'
					};
				});
				sendToolResults(errorResults);
			}
		});
	}

	/**
	 * Send tool results back to API to continue the conversation
	 */
	function sendToolResults(toolResults) {
		if (!state.sessionId || !toolResults || toolResults.length === 0) return;

		// Show thinking indicator while AI processes the results
		showThinking();

		const requestData = {
			role: 'tool',
			content: 'Tool execution results',
			tool_results: toolResults
		};

		// Send tool results using SSE for streaming response
		const url = wooaiChat.apiBaseUrl + '/ai/chat/sessions/' + state.sessionId + '/messages';

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': wooaiChat.apiKey,
				'Accept': 'text/event-stream'
			},
			body: JSON.stringify(requestData)
		})
		.then(function(response) {
			if (!response.ok) {
				throw new Error('Network response was not ok');
			}

			const contentType = response.headers.get('content-type');

			if (contentType && contentType.includes('text/event-stream')) {
				return handleSSEResponse(response);
			} else {
				return response.json().then(function(data) {
					hideThinking();
					const responseData = data.data || data;
					if (responseData.assistant_message || responseData.suggestions) {
						handleMessageResponse(responseData);
					}
					setLoading(false);
				});
			}
		})
		.catch(function(error) {
			console.error('Tool results send error:', error);
			hideThinking();
			addErrorMessage(wooaiChat.i18n.connectionError || 'Connection error');
			setLoading(false);
		});
	}

	/**
	 * Handle message response (non-SSE)
	 */
	function handleMessageResponse(data) {
		if (data.assistant_message) {
			addMessage(data.assistant_message);
		}

		if (data.suggestions && data.suggestions.length > 0) {
			data.suggestions.forEach(function(suggestion) {
				handleSuggestionReceived(suggestion);
			});
		}

		if (data.tokens_used) {
			updateTokensUsed(data.tokens_used);
			updateBalance(-data.tokens_used.total);
		}
	}

	/**
	 * Handle received suggestion
	 */
	function handleSuggestionReceived(suggestion) {
		state.pendingSuggestions[suggestion.id] = suggestion;
		renderSuggestionInMessage(suggestion);
		showPendingChange(suggestion);
		updatePendingSummary();
	}

	/**
	 * Handle quick action button click
	 */
	function handleQuickAction(e) {
		const action = $(e.currentTarget).data('action');
		if (!action || !state.selectedProduct) return;

		const actionMessages = {
			'improve_title': 'Please improve the product title',
			'improve_description': 'Please improve the product description',
			'seo_optimize': 'Please optimize this product for SEO',
			'suggest_tags': 'Please suggest relevant tags for this product',
			'suggest_categories': 'Please suggest appropriate categories for this product',
			'generate_content': 'Please generate comprehensive content for this product'
		};

		const message = actionMessages[action] || 'Help with this product';
		sendMessage(message, action);
	}

	/**
	 * Handle category quick action button click
	 */
	function handleCategoryQuickAction(e) {
		const action = $(e.currentTarget).data('action');
		if (!action || !state.selectedCategory) return;

		const actionMessages = {
			'improve_name': 'Please suggest a better name for this category',
			'improve_cat_description': 'Please generate a compelling description for this category',
			'cat_seo_optimize': 'Please optimize this category for SEO including title and meta description',
			'generate_cat_content': 'Please fully optimize this category - improve the name, generate a description, and add SEO content'
		};

		const message = actionMessages[action] || 'Help with this category';
		sendMessage(message, action);
	}

	/**
	 * Handle agent quick action button click
	 */
	function handleAgentQuickAction(e) {
		const action = $(e.currentTarget).data('action');
		if (!action || !state.isAgentMode) return;

		const actionMessages = {
			'create_campaign': 'Help me create a marketing campaign for my store',
			'social_content': 'Generate social media content for my store products',
			'email_campaign': 'Create an email marketing campaign',
			'generate_image': 'Help me create a marketing image',
			'store_analysis': 'Analyze my store and suggest improvements',
			'bulk_optimize': 'Suggest bulk optimization strategies for my products'
		};

		const message = actionMessages[action] || 'Help with marketing';
		sendMessage(message, action);
	}

	/**
	 * Handle new chat button
	 */
	function handleNewChat() {
		if (state.isAgentMode) {
			state.pendingSuggestions = {};
			updatePendingSummary();
			clearPendingChanges();
			createSession(null, 'agent');
		} else if (state.entityType === 'category' && state.selectedCategory) {
			state.pendingSuggestions = {};
			updatePendingSummary();
			clearPendingChanges();
			createSession(state.selectedCategory, 'category');
		} else if (state.selectedProduct) {
			state.pendingSuggestions = {};
			updatePendingSummary();
			clearPendingChanges();
			createSession(state.selectedProduct, 'product');
		}
	}

	/**
	 * Handle suggestion action (apply/edit/discard)
	 */
	function handleSuggestionAction(e) {
		e.preventDefault();
		const action = $(e.currentTarget).data('action');
		const $suggestion = $(e.currentTarget).closest('.wooai-suggestion');
		const suggestionId = $suggestion.data('suggestion-id');

		if (action === 'apply') {
			applySuggestion(suggestionId, $suggestion);
		} else if (action === 'discard') {
			discardSuggestion(suggestionId, $suggestion);
		} else if (action === 'edit') {
			editSuggestion(suggestionId, $suggestion);
		}
	}

	/**
	 * Handle pending change action in product panel
	 */
	function handlePendingChangeAction(e) {
		e.preventDefault();
		const action = $(e.currentTarget).data('action');
		const $field = $(e.currentTarget).closest('.wooai-product-info__field, .wooai-category-info__field');
		const fieldType = $field.data('field');

		// Find matching suggestion
		const suggestion = Object.values(state.pendingSuggestions).find(function(s) {
			return s.suggestion_type === fieldType && s.status === 'pending';
		});

		if (suggestion) {
			if (action === 'accept') {
				applySuggestion(suggestion.id);
			} else if (action === 'undo') {
				discardSuggestion(suggestion.id);
			}
		}
	}

	/**
	 * Apply a suggestion
	 */
	function applySuggestion(suggestionId, $element) {
		const suggestion = state.pendingSuggestions[suggestionId];
		if (!suggestion) return;

		$.ajax({
			url: wooaiChat.apiBaseUrl + '/ai/chat/sessions/' + state.sessionId + '/suggestions/' + suggestionId,
			method: 'PATCH',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': wooaiChat.apiKey
			},
			data: JSON.stringify({ action: 'apply' }),
			success: function(response) {
				// Handle both { success: true, data: {...} } and { suggestion: {...} } formats
				const appliedSuggestion = response.suggestion || (response.data && response.data.suggestion);
				if (appliedSuggestion || response.success) {
					suggestion.status = 'applied';

					// Update entity data based on type
					if (state.entityType === 'category') {
						updateCategoryField(suggestion.suggestion_type, suggestion.suggested_value);
						saveCategoryField(suggestion.suggestion_type, suggestion.suggested_value);
					} else {
						updateProductField(suggestion.suggestion_type, suggestion.suggested_value);
						saveProductField(suggestion.suggestion_type, suggestion.suggested_value);
					}

					// Update UI
					if ($element) {
						$element.addClass('wooai-suggestion--applied');
						$element.find('.wooai-suggestion__actions').html(
							'<span class="wooai-suggestion__status wooai-suggestion__status--applied">' +
							'<span class="dashicons dashicons-yes-alt"></span> ' +
							wooaiChat.i18n.applied +
							'</span>'
						);
					}

					// Update entity panel
					hidePendingChange(suggestion.suggestion_type);
					delete state.pendingSuggestions[suggestionId];
					updatePendingSummary();
				}
			},
			error: handleError
		});
	}

	/**
	 * Discard a suggestion
	 */
	function discardSuggestion(suggestionId, $element) {
		const suggestion = state.pendingSuggestions[suggestionId];
		if (!suggestion) return;

		$.ajax({
			url: wooaiChat.apiBaseUrl + '/ai/chat/sessions/' + state.sessionId + '/suggestions/' + suggestionId,
			method: 'PATCH',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': wooaiChat.apiKey
			},
			data: JSON.stringify({ action: 'discard' }),
			success: function(response) {
				// Handle both { success: true, data: {...} } and { suggestion: {...} } formats
				const discardedSuggestion = response.suggestion || (response.data && response.data.suggestion);
				if (discardedSuggestion || response.success) {
					suggestion.status = 'discarded';

					// Update UI
					if ($element) {
						$element.addClass('wooai-suggestion--discarded');
						$element.find('.wooai-suggestion__actions').html(
							'<span class="wooai-suggestion__status wooai-suggestion__status--discarded">' +
							'<span class="dashicons dashicons-dismiss"></span> ' +
							wooaiChat.i18n.discarded +
							'</span>'
						);
					}

					// Update product panel
					hidePendingChange(suggestion.suggestion_type);
					delete state.pendingSuggestions[suggestionId];
					updatePendingSummary();
				}
			},
			error: handleError
		});
	}

	/**
	 * Edit a suggestion (show in text input)
	 */
	function editSuggestion(suggestionId, $element) {
		const suggestion = state.pendingSuggestions[suggestionId];
		if (!suggestion) return;

		// Pre-fill the input with suggested value
		elements.messageInput.val('Please modify: ' + suggestion.suggested_value);
		elements.messageInput.focus();
	}

	/**
	 * Handle accept all pending suggestions
	 */
	function handleAcceptAll() {
		const pending = Object.keys(state.pendingSuggestions);
		if (pending.length === 0) return;

		pending.forEach(function(suggestionId) {
			const $element = $('[data-suggestion-id="' + suggestionId + '"]');
			applySuggestion(suggestionId, $element);
		});
	}

	/**
	 * Handle discard all pending suggestions
	 */
	function handleDiscardAll() {
		const pending = Object.keys(state.pendingSuggestions);
		if (pending.length === 0) return;

		pending.forEach(function(suggestionId) {
			const $element = $('[data-suggestion-id="' + suggestionId + '"]');
			discardSuggestion(suggestionId, $element);
		});
	}

	/**
	 * Handle expand toggle for description
	 */
	function handleExpandToggle(e) {
		const $field = $(e.currentTarget).closest('.wooai-product-info__field, .wooai-category-info__field');
		$field.toggleClass('wooai-product-info__field--expanded wooai-category-info__field--expanded');
		$field.find('.wooai-product-info__value, .wooai-category-info__value').toggleClass('wooai-product-info__value--truncated wooai-category-info__value--truncated');
	}

	/**
	 * Handle welcome card click
	 */
	function handleWelcomeCardClick(e) {
		const $card = $(e.currentTarget);
		const entityType = $card.data('entity');
		
		if (entityType && entityType !== state.entityType) {
			state.entityType = entityType;
			updateEntityTabs();
			populateEntitySelector();
			updateQuickActionsVisibility();
			updateEmptyState();
		}
		
		// Focus the selector
		elements.entitySelect.focus();
	}

	/**
	 * Open store context panel
	 */
	function openContextPanel() {
		elements.contextPanel.addClass('wooai-context-panel--open');
		$('body').addClass('wooai-context-panel-active');
	}

	/**
	 * Close store context panel
	 */
	function closeContextPanel() {
		elements.contextPanel.removeClass('wooai-context-panel--open');
		$('body').removeClass('wooai-context-panel-active');
	}

	/**
	 * Save store context
	 */
	function saveStoreContext() {
		const formData = {
			store_name: $('#wooai-store-name').val(),
			store_description: $('#wooai-store-description').val(),
			business_niche: $('#wooai-business-niche').val(),
			target_audience: $('#wooai-target-audience').val(),
			brand_tone: $('input[name="brand_tone"]:checked').val() || '',
			language: $('#wooai-language').val(),
			custom_instructions: $('#wooai-custom-instructions').val()
		};

		const $saveBtn = $('#wooai-save-context');
		$saveBtn.addClass('wooai-btn--loading').prop('disabled', true);

		$.ajax({
			url: wooaiChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'wooai_save_store_context',
				nonce: wooaiChat.nonce,
				context: formData
			},
			success: function(response) {
				if (response.success) {
					state.storeContext = formData;
					updateContextStatus(formData);
					closeContextPanel();
					
					// Show success notice
					showNotice('Store context saved successfully', 'success');
				} else {
					showNotice(response.data || 'Failed to save store context', 'error');
				}
			},
			error: function() {
				showNotice('Failed to save store context', 'error');
			},
			complete: function() {
				$saveBtn.removeClass('wooai-btn--loading').prop('disabled', false);
			}
		});
	}

	/**
	 * Sync store context (re-fetch category/product counts)
	 */
	function syncStoreContext() {
		const $syncBtn = $('#wooai-sync-context');
		$syncBtn.addClass('wooai-btn--loading').prop('disabled', true);

		$.ajax({
			url: wooaiChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'wooai_sync_store_context',
				nonce: wooaiChat.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#wooai-sync-status').text(response.data.message || 'Synced successfully');
				} else {
					showNotice(response.data || 'Sync failed', 'error');
				}
			},
			error: function() {
				showNotice('Sync failed', 'error');
			},
			complete: function() {
				$syncBtn.removeClass('wooai-btn--loading').prop('disabled', false);
			}
		});
	}

	/**
	 * Update context status indicator
	 */
	function updateContextStatus(context) {
		const hasRequired = context.store_name || context.business_niche;
		const hasOptional = context.target_audience || context.brand_tone;
		
		let status = 'missing';
		if (hasRequired) {
			status = hasOptional ? 'configured' : 'partial';
		}

		$('.wooai-context-status')
			.removeClass('wooai-context-status--configured wooai-context-status--partial wooai-context-status--missing')
			.addClass('wooai-context-status--' + status);

		// Update store name in button
		if (context.store_name) {
			$('.wooai-store-name').text(context.store_name);
		}
	}

	/**
	 * Close onboarding overlay
	 */
	function closeOnboarding() {
		elements.onboardingOverlay.fadeOut(300, function() {
			$(this).remove();
		});

		// Mark as visited
		$.ajax({
			url: wooaiChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'wooai_mark_chat_visited',
				nonce: wooaiChat.nonce
			}
		});
	}

	/**
	 * Show a notice
	 */
	function showNotice(message, type) {
		const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
		$('.wooai-chat-page-header').after($notice);
		
		// Auto-dismiss after 5 seconds
		setTimeout(function() {
			$notice.fadeOut(300, function() {
				$(this).remove();
			});
		}, 5000);
	}

	/**
	 * Add a message to the chat
	 */
	function addMessage(message, isStreaming) {
		const $message = renderMessage(message, isStreaming);
		elements.messagesContainer.append($message);
		scrollToBottom();
	}

	/**
	 * Render a message
	 */
	function renderMessage(message, isStreaming) {
		const icons = {
			'user': 'dashicons-admin-users',
			'assistant': 'dashicons-admin-site-alt3',
			'system': 'dashicons-info'
		};

		let html = templates.message
			.replace('{role}', message.role)
			.replace('{id}', message.id)
			.replace('{icon}', icons[message.role] || 'dashicons-format-chat')
			.replace('{content}', formatMessageContent(message.content))
			.replace('{time}', formatTime(message.created_at))
			.replace('{tokens}', message.tokens_input ?
				'<span class="wooai-message__tokens">' + message.tokens_input + ' + ' + message.tokens_output + ' ' + wooaiChat.i18n.tokensUsed + '</span>' :
				''
			);

		const $message = $(html);

		// Add attachment indicators for user messages
		if (message.attachments && message.attachments.length > 0) {
			let attachmentHtml = '<div class="wooai-message__attachments">';
			message.attachments.forEach(function(att) {
				const isImage = att.mime_type && att.mime_type.startsWith('image/');
				const isPdf = att.mime_type === 'application/pdf';
				const icon = isImage ? 'dashicons-format-image' : (isPdf ? 'dashicons-pdf' : 'dashicons-media-default');
				attachmentHtml += '<span class="wooai-message__attachment"><span class="dashicons ' + icon + '"></span>' + escapeHtml(att.filename) + '</span>';
			});
			attachmentHtml += '</div>';
			$message.find('.wooai-message__content').prepend(attachmentHtml);
		}

		if (isStreaming) {
			$message.addClass('wooai-message--streaming');
			$message.attr('data-streaming', 'true');
		}

		return $message;
	}

	/**
	 * Update streaming message content
	 */
	function updateStreamingMessage(content) {
		const $streaming = elements.messagesContainer.find('[data-streaming="true"]');
		if ($streaming.length) {
			$streaming.find('.wooai-message__text').html(formatMessageContent(content));
			scrollToBottom();
		}
	}

	/**
	 * Render suggestion in message - groups multiple suggestions of same type
	 */
	function renderSuggestionInMessage(suggestion) {
		const typeLabels = {
			// Product fields
			'title': wooaiChat.i18n.improveTitle || 'Improve Title',
			'description': wooaiChat.i18n.improveDescription || 'Improve Description',
			'short_description': 'Short Description',
			'tags': wooaiChat.i18n.suggestTags || 'Tags',
			'categories': wooaiChat.i18n.suggestCategories || 'Categories',
			'meta_description': 'Meta Description',
			// Category fields
			'name': 'Category Name',
			'seo_title': 'SEO Title',
			'seo_description': 'SEO Description',
			'subcategories': 'Subcategories'
		};

		const $container = elements.messagesContainer.find('[data-streaming="true"]').length
			? elements.messagesContainer.find('[data-streaming="true"] .wooai-message__content')
			: elements.messagesContainer.find('.wooai-message--assistant').last().find('.wooai-message__content');

		if (!$container.length) return;

		// Check if there's already a group for this suggestion type
		let $group = $container.find('.wooai-suggestions-group[data-type="' + suggestion.suggestion_type + '"]');

		if ($group.length) {
			// Add to existing group
			const $options = $group.find('.wooai-suggestions-group__options');
			const optionNumber = $options.find('.wooai-suggestion').length + 1;
			
			const optionHtml = renderSuggestionOption(suggestion, optionNumber);
			$options.append(optionHtml);
			
			// Update count
			$group.find('.wooai-suggestions-group__count').text(optionNumber + ' options');
		} else {
			// Create new group
			const groupHtml = '<div class="wooai-suggestions-group" data-type="' + suggestion.suggestion_type + '">' +
				'<div class="wooai-suggestions-group__header">' +
					'<div class="wooai-suggestions-group__title">' +
						'<span class="dashicons dashicons-lightbulb"></span>' +
						'<span>' + (typeLabels[suggestion.suggestion_type] || suggestion.suggestion_type) + '</span>' +
					'</div>' +
					'<span class="wooai-suggestions-group__count">1 option</span>' +
				'</div>' +
				'<div class="wooai-suggestions-group__current">' +
					'<div class="wooai-suggestions-group__current-label">Current value</div>' +
					'<div class="wooai-suggestions-group__current-value">' + escapeHtml(suggestion.current_value || 'None') + '</div>' +
				'</div>' +
				'<div class="wooai-suggestions-group__options">' +
					renderSuggestionOption(suggestion, 1) +
				'</div>' +
			'</div>';

			$container.append(groupHtml);
		}
	}

	/**
	 * Render a single suggestion option within a group
	 */
	function renderSuggestionOption(suggestion, number) {
		return '<div class="wooai-suggestion" data-suggestion-id="' + suggestion.id + '" data-suggestion-type="' + suggestion.suggestion_type + '">' +
			'<span class="wooai-suggestion__number">' + number + '</span>' +
			'<div class="wooai-suggestion__content">' +
				'<div class="wooai-suggestion__value">' + escapeHtml(suggestion.suggested_value) + '</div>' +
			'</div>' +
			'<div class="wooai-suggestion__actions">' +
				'<button type="button" class="wooai-btn wooai-btn--success wooai-btn--sm" data-action="apply">' +
					'<span class="dashicons dashicons-yes"></span> Apply' +
				'</button>' +
				'<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="discard">' +
					'<span class="dashicons dashicons-no"></span>' +
				'</button>' +
			'</div>' +
		'</div>';
	}

	/**
	 * Render all messages
	 */
	function renderMessages() {
		clearMessages();

		if (state.messages.length === 0) {
			return;
		}

		state.messages.forEach(function(message) {
			addMessage(message);
		});

		// Add pending suggestions to their messages
		Object.values(state.pendingSuggestions).forEach(function(suggestion) {
			renderSuggestionInMessage(suggestion);
			showPendingChange(suggestion);
		});
	}

	/**
	 * Clear messages container
	 */
	function clearMessages() {
		elements.messagesContainer.empty();
	}

	/**
	 * Show welcome message
	 */
	function showWelcomeMessage() {
		// Show the welcome element that's already in the template
		elements.chatWelcome.show();
	}

	/**
	 * Show thinking indicator
	 */
	function showThinking() {
		elements.messagesContainer.find('.wooai-message--thinking').remove();
		elements.messagesContainer.append(templates.thinking);
		scrollToBottom();
	}

	/**
	 * Hide thinking indicator
	 */
	function hideThinking() {
		elements.messagesContainer.find('.wooai-message--thinking').remove();
	}

	/**
	 * Add error message
	 */
	function addErrorMessage(text) {
		const $error = $('<div class="wooai-message wooai-message--error">' +
			'<div class="wooai-message__avatar"><span class="dashicons dashicons-warning"></span></div>' +
			'<div class="wooai-message__content">' +
			'<div class="wooai-message__text">' + escapeHtml(text) + '</div>' +
			'</div></div>');
		elements.messagesContainer.append($error);
		scrollToBottom();
	}

	/**
	 * Show pending change in entity panel
	 */
	function showPendingChange(suggestion) {
		// Determine which panel to update
		const $panel = state.entityType === 'category' ? elements.categoryInfo : elements.productInfo;
		const $field = $panel.find('[data-field="' + suggestion.suggestion_type + '"]');
		if (!$field.length) return;

		$field.addClass('wooai-product-info__field--has-pending wooai-category-info__field--has-pending');
		const $pending = $field.find('.wooai-product-info__pending, .wooai-category-info__pending');

		if (suggestion.suggestion_type === 'tags' || suggestion.suggestion_type === 'categories' || suggestion.suggestion_type === 'subcategories') {
			// Handle array fields
			const currentArr = (suggestion.current_value || '').split(',').map(s => s.trim()).filter(Boolean);
			const suggestedArr = (suggestion.suggested_value || '').split(',').map(s => s.trim()).filter(Boolean);
			const added = suggestedArr.filter(t => !currentArr.includes(t));
			const removed = currentArr.filter(t => !suggestedArr.includes(t));

			$pending.find('.wooai-pending-change__added').html(
				added.map(t => '<span class="wooai-tag wooai-tag--added">+ ' + escapeHtml(t) + '</span>').join('')
			);
			$pending.find('.wooai-pending-change__removed').html(
				removed.map(t => '<span class="wooai-tag wooai-tag--removed">- ' + escapeHtml(t) + '</span>').join('')
			);
		} else {
			// Handle text fields - only show new value
			$pending.find('.wooai-pending-change__new').text(suggestion.suggested_value);
		}

		$pending.slideDown(200);
	}

	/**
	 * Hide pending change in entity panel
	 */
	function hidePendingChange(fieldType) {
		const $panel = state.entityType === 'category' ? elements.categoryInfo : elements.productInfo;
		const $field = $panel.find('[data-field="' + fieldType + '"]');
		$field.removeClass('wooai-product-info__field--has-pending wooai-category-info__field--has-pending');
		$field.find('.wooai-product-info__pending, .wooai-category-info__pending').slideUp(200);
	}

	/**
	 * Clear all pending changes from entity panel
	 */
	function clearPendingChanges() {
		elements.productInfo.find('.wooai-product-info__field--has-pending').removeClass('wooai-product-info__field--has-pending');
		elements.productInfo.find('.wooai-product-info__pending').hide();
		elements.categoryInfo.find('.wooai-category-info__field--has-pending').removeClass('wooai-category-info__field--has-pending');
		elements.categoryInfo.find('.wooai-category-info__pending').hide();
	}

	/**
	 * Update pending summary
	 */
	function updatePendingSummary() {
		const count = Object.keys(state.pendingSuggestions).filter(function(id) {
			return state.pendingSuggestions[id].status === 'pending';
		}).length;

		if (state.entityType === 'category') {
			$('#wooai-category-pending-count').text(count);
			if (count > 0) {
				elements.categoryPendingSummary.slideDown(200);
			} else {
				elements.categoryPendingSummary.slideUp(200);
			}
			elements.pendingSummary.hide();
		} else {
			$('#wooai-pending-count').text(count);
			if (count > 0) {
				elements.pendingSummary.slideDown(200);
			} else {
				elements.pendingSummary.slideUp(200);
			}
			elements.categoryPendingSummary.hide();
		}
	}

	/**
	 * Update product field in local state
	 */
	function updateProductField(fieldType, value) {
		if (!state.selectedProduct) return;

		const fieldMap = {
			'title': 'title',
			'description': 'description',
			'short_description': 'short_description',
			'tags': 'tags',
			'categories': 'categories'
		};

		const field = fieldMap[fieldType];
		if (!field) return;

		if (field === 'tags' || field === 'categories') {
			state.selectedProduct[field] = value.split(',').map(s => s.trim()).filter(Boolean);
		} else {
			state.selectedProduct[field] = value;
		}

		// Update display
		updateProductPanel(state.selectedProduct);
	}

	/**
	 * Update category field in local state
	 */
	function updateCategoryField(fieldType, value) {
		if (!state.selectedCategory) return;

		const fieldMap = {
			'name': 'name',
			'description': 'description',
			'seo_title': 'seo_title',
			'meta_description': 'meta_description',
			'subcategories': 'subcategories'
		};

		const field = fieldMap[fieldType];
		if (!field) return;

		if (field === 'subcategories') {
			state.selectedCategory[field] = value.split(',').map(s => ({ name: s.trim() })).filter(s => s.name);
		} else {
			state.selectedCategory[field] = value;
		}

		// Update display
		updateCategoryPanel(state.selectedCategory);
	}

	/**
	 * Save product field via WordPress AJAX
	 */
	function saveProductField(fieldType, value) {
		if (!state.selectedProduct) return;

		$.ajax({
			url: wooaiChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'wooai_update_product_field',
				nonce: wooaiChat.nonce,
				product_id: state.selectedProduct.id,
				field: fieldType,
				value: value
			},
			success: function(response) {
				if (!response.success) {
					console.error('Failed to save product field:', response.data);
				}
			},
			error: function(xhr) {
				console.error('AJAX error:', xhr);
			}
		});
	}

	/**
	 * Save category field via WordPress AJAX
	 */
	function saveCategoryField(fieldType, value) {
		if (!state.selectedCategory) return;

		$.ajax({
			url: wooaiChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'wooai_update_category_field',
				nonce: wooaiChat.nonce,
				category_id: state.selectedCategory.id,
				field: fieldType,
				value: value
			},
			success: function(response) {
				if (!response.success) {
					console.error('Failed to save category field:', response.data);
				}
			},
			error: function(xhr) {
				console.error('AJAX error:', xhr);
			}
		});
	}

	/**
	 * Update tokens used display
	 */
	function updateTokensUsed(tokens) {
		elements.tokensUsed.text(tokens.total + ' ' + wooaiChat.i18n.tokensUsed);
	}

	/**
	 * Update balance display
	 */
	function updateBalance(change) {
		state.balance += change;
		if (state.balance < 0) state.balance = 0;
		elements.balanceDisplay.text(numberFormat(state.balance));
	}

	/**
	 * Sync balance to WordPress for persistence across page refreshes
	 */
	function syncBalanceToWordPress(balance) {
		$.ajax({
			url: wooaiChat.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wooai_sync_balance',
				nonce: wooaiChat.nonce,
				balance: balance
			}
		});
	}

	/**
	 * Save AI-generated image to WordPress media library
	 * @param {string} imageData - Base64 encoded image data (with or without data URL prefix)
	 * @param {string} filename - Suggested filename for the image
	 * @param {string} title - Optional title for the media item
	 * @returns {Promise} - Resolves with attachment data or rejects with error
	 */
	function saveGeneratedImage(imageData, filename, title) {
		return new Promise(function(resolve, reject) {
			$.ajax({
				url: wooaiChat.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wooai_save_generated_image',
					nonce: wooaiChat.nonce,
					image_data: imageData,
					filename: filename || 'ai-generated-image.png',
					title: title || ''
				},
				success: function(response) {
					if (response.success) {
						showNotice(response.data.message || 'Image saved to media library', 'success');
						resolve(response.data);
					} else {
						showNotice(response.data.message || 'Failed to save image', 'error');
						reject(new Error(response.data.message || 'Failed to save image'));
					}
				},
				error: function(xhr) {
					var message = 'Failed to save image';
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						message = xhr.responseJSON.data.message;
					}
					showNotice(message, 'error');
					reject(new Error(message));
				}
			});
		});
	}

	/**
	 * Get store summary for agent context
	 * @returns {Promise} - Resolves with store summary data
	 */
	function getStoreSummary() {
		return new Promise(function(resolve, reject) {
			$.ajax({
				url: wooaiChat.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wooai_get_store_summary',
					nonce: wooaiChat.nonce
				},
				success: function(response) {
					if (response.success) {
						resolve(response.data);
					} else {
						reject(new Error(response.data.message || 'Failed to get store summary'));
					}
				},
				error: function(xhr) {
					var message = 'Failed to get store summary';
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						message = xhr.responseJSON.data.message;
					}
					reject(new Error(message));
				}
			});
		});
	}

	/**
	 * Enable input elements
	 */
	function enableInputs() {
		elements.messageInput.prop('disabled', false);
		elements.sendButton.prop('disabled', false);
		
		if (state.entityType === 'agent') {
			elements.quickActionsAgent.find('button').prop('disabled', false);
		} else if (state.entityType === 'category') {
			elements.quickActionsCategory.find('button').prop('disabled', false);
		} else {
			elements.quickActionsProduct.find('button').prop('disabled', false);
		}
	}

	/**
	 * Disable input elements
	 */
	function disableInputs() {
		elements.messageInput.prop('disabled', true);
		elements.sendButton.prop('disabled', true);
		elements.quickActionsProduct.find('button').prop('disabled', true);
		elements.quickActionsCategory.find('button').prop('disabled', true);
		elements.quickActionsAgent.find('button').prop('disabled', true);
	}

	/**
	 * Set loading state
	 */
	function setLoading(loading) {
		state.isLoading = loading;
		const hasEntity = state.isAgentMode || (state.entityType === 'category' ? state.selectedCategory : state.selectedProduct);
		elements.sendButton.prop('disabled', loading || !hasEntity);
		elements.messageInput.prop('disabled', loading || !hasEntity);

		if (loading) {
			elements.sendButton.addClass('wooai-btn--loading');
		} else {
			elements.sendButton.removeClass('wooai-btn--loading');
		}
	}

	/**
	 * Scroll chat to bottom
	 */
	function scrollToBottom() {
		elements.messagesContainer.scrollTop(elements.messagesContainer[0].scrollHeight);
	}

	/**
	 * Auto-resize textarea
	 */
	function autoResizeTextarea() {
		elements.messageInput.on('input', function() {
			this.style.height = 'auto';
			this.style.height = Math.min(this.scrollHeight, 120) + 'px';
		});
	}

	/**
	 * Handle error response
	 */
	function handleError(xhr) {
		let message = wooaiChat.i18n.errorOccurred;

		if (xhr.responseJSON && xhr.responseJSON.error) {
			message = xhr.responseJSON.error.message || message;

			if (xhr.responseJSON.error.code === 'INSUFFICIENT_BALANCE') {
				message = wooaiChat.i18n.insufficientBalance;
			}
		}

		addErrorMessage(message);
	}

	/**
	 * Format message content (handle markdown-like formatting)
	 */
	function formatMessageContent(content) {
		if (!content) return '';

		// Escape HTML first
		let formatted = escapeHtml(content);

		// Convert basic markdown
		// Bold: **text**
		formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

		// Italic: *text*
		formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');

		// Code: `code`
		formatted = formatted.replace(/`(.+?)`/g, '<code>$1</code>');

		// Line breaks
		formatted = formatted.replace(/\n/g, '<br>');

		return formatted;
	}

	/**
	 * Format time
	 */
	function formatTime(isoString) {
		if (!isoString) return '';
		const date = new Date(isoString);
		return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
	}

	/**
	 * Format price display
	 */
	function formatPrice(regularPrice, salePrice, price) {
		if (!price && !regularPrice) return '<span class="wooai-no-value">Not set</span>';

		if (salePrice && regularPrice && salePrice !== regularPrice) {
			return '<del>' + formatCurrency(regularPrice) + '</del> ' + formatCurrency(salePrice);
		}

		return formatCurrency(price || regularPrice);
	}

	/**
	 * Format currency
	 */
	function formatCurrency(amount) {
		if (!amount) return '';
		const num = parseFloat(amount);
		return isNaN(num) ? amount : '$' + num.toFixed(2);
	}

	/**
	 * Format stock display
	 */
	function formatStock(status, quantity) {
		const statusLabels = {
			'instock': '<span class="wooai-stock wooai-stock--instock">In Stock</span>',
			'outofstock': '<span class="wooai-stock wooai-stock--outofstock">Out of Stock</span>',
			'onbackorder': '<span class="wooai-stock wooai-stock--backorder">On Backorder</span>'
		};

		let html = statusLabels[status] || '<span class="wooai-no-value">Unknown</span>';

		if (quantity !== null && quantity !== undefined && status === 'instock') {
			html += ' <span class="wooai-stock__qty">(' + quantity + ')</span>';
		}

		return html;
	}

	/**
	 * Format status display
	 */
	function formatStatus(status) {
		const statusClasses = {
			'publish': 'wooai-status--published',
			'draft': 'wooai-status--draft',
			'pending': 'wooai-status--pending',
			'private': 'wooai-status--private'
		};

		const statusLabels = {
			'publish': 'Published',
			'draft': 'Draft',
			'pending': 'Pending',
			'private': 'Private'
		};

		const cls = statusClasses[status] || '';
		const label = statusLabels[status] || status || 'Unknown';

		return '<span class="wooai-status ' + cls + '">' + label + '</span>';
	}

	/**
	 * Escape HTML
	 */
	function escapeHtml(str) {
		if (!str) return '';
		return str
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	/**
	 * Truncate string
	 */
	function truncate(str, len) {
		if (!str) return '';
		return str.length > len ? str.substring(0, len) + '...' : str;
	}

	/**
	 * Format number with commas
	 */
	function numberFormat(num) {
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	/**
	 * Animate balance counter from current value to new value
	 */
	function animateBalance(newBalance, duration) {
		duration = duration || 800;
		var $el = elements.balanceDisplay;
		var startBalance = state.balance;
		var difference = newBalance - startBalance;
		
		// If no change, just update
		if (difference === 0) {
			return;
		}
		
		var startTime = null;
		var isDecreasing = difference < 0;
		
		// Add visual feedback class
		$el.parent().addClass(isDecreasing ? 'wooai-balance--decreasing' : 'wooai-balance--increasing');
		
		function step(timestamp) {
			if (!startTime) startTime = timestamp;
			var elapsed = timestamp - startTime;
			var progress = Math.min(elapsed / duration, 1);
			
			// Ease out cubic for smooth deceleration
			var easeProgress = 1 - Math.pow(1 - progress, 3);
			
			var currentValue = Math.round(startBalance + (difference * easeProgress));
			$el.text(numberFormat(currentValue));
			
			if (progress < 1) {
				requestAnimationFrame(step);
			} else {
				// Ensure final value is exact
				$el.text(numberFormat(newBalance));
				state.balance = newBalance;
				
				// Remove visual feedback class after a short delay
				setTimeout(function() {
					$el.parent().removeClass('wooai-balance--decreasing wooai-balance--increasing');
				}, 300);
			}
		}
		
		requestAnimationFrame(step);
	}

	// Initialize when DOM is ready
	$(document).ready(init);

})(jQuery);
