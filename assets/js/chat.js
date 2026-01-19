/**
 * AISales Chat Interface
 *
 * @package AISales_Sales_Manager
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
		if (typeof aisalesChat !== 'undefined') {
			state.products = aisalesChat.products || [];
			state.categories = aisalesChat.categories || [];
			state.balance = parseInt(aisalesChat.balance, 10) || 0;
			state.storeContext = aisalesChat.storeContext || {};
		}

		// Check for preselected entity type
		if (typeof window.aisalesPreselectedEntityType !== 'undefined') {
			state.entityType = window.aisalesPreselectedEntityType;
		}

		// Populate entity selector based on type
		populateEntitySelector();

		// Bind events
		bindEvents();

		// Check for preselected product
		if (typeof window.aisalesPreselectedProduct !== 'undefined') {
			state.entityType = 'product';
			updateEntityTabs();
			selectProduct(window.aisalesPreselectedProduct);
		}

		// Check for preselected category
		if (typeof window.aisalesPreselectedCategory !== 'undefined') {
			state.entityType = 'category';
			updateEntityTabs();
			selectCategory(window.aisalesPreselectedCategory);
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
		elements.entityTabs = $('.aisales-entity-tabs');
		elements.entitySelect = $('#aisales-entity-select');
		
		// Chat elements
		elements.messageInput = $('#aisales-message-input');
		elements.sendButton = $('#aisales-send-message');
		elements.messagesContainer = $('#aisales-chat-messages');
		elements.chatWelcome = $('#aisales-chat-welcome');
		
		// Quick actions
		elements.quickActionsProduct = $('#aisales-quick-actions-product');
		elements.quickActionsCategory = $('#aisales-quick-actions-category');
		elements.quickActionsAgent = $('#aisales-quick-actions-agent');
		
		// Entity panels
		elements.entityPanel = $('#aisales-entity-panel');
		elements.entityEmpty = $('#aisales-entity-empty');
		elements.productInfo = $('#aisales-product-info');
		elements.categoryInfo = $('#aisales-category-info');
		elements.agentInfo = $('#aisales-agent-info');
		
		// Pending changes
		elements.pendingSummary = $('#aisales-pending-summary');
		elements.categoryPendingSummary = $('#aisales-category-pending-summary');
		
		// Header elements
		elements.balanceDisplay = $('#aisales-balance-display');
		elements.newChatButton = $('#aisales-new-chat');
		elements.tokensUsed = $('#aisales-tokens-used');
		
		// Store context
		elements.storeContextBtn = $('#aisales-open-context');
		elements.contextPanel = $('#aisales-context-panel');
		elements.contextBackdrop = $('#aisales-context-backdrop');
		elements.contextForm = $('#aisales-context-form');
		
		// Onboarding
		elements.onboardingOverlay = $('#aisales-onboarding');
		
		// Welcome cards
		elements.welcomeCards = $('.aisales-welcome-cards');
		
		// Attachment elements
		elements.attachButton = $('#aisales-attach-button');
		elements.fileInput = $('#aisales-file-input');
		elements.attachmentPreviews = $('#aisales-attachment-previews');
	}

	/**
	 * Load template strings
	 */
	function loadTemplates() {
		templates.message = $('#aisales-message-template').html() || '';
		templates.suggestion = $('#aisales-suggestion-template').html() || '';
		templates.thinking = $('#aisales-thinking-template').html() || '';
	}

	/**
	 * Populate product selector dropdown
	 */
	function populateProductSelector() {
		const $select = elements.entitySelect;
		$select.empty();
		$select.append($('<option>', {
			value: '',
			text: aisalesChat.i18n.selectProduct
		}));

		if (state.products.length === 0) {
			$select.append($('<option>', {
				value: '',
				text: aisalesChat.i18n.noProducts,
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
			text: aisalesChat.i18n.selectCategory || 'Select a category...'
		}));

		if (state.categories.length === 0) {
			$select.append($('<option>', {
				value: '',
				text: aisalesChat.i18n.noCategories || 'No categories found',
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
		elements.entityTabs.on('click', '.aisales-entity-tab', handleEntityTabClick);

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
		
		// Agent capabilities (right panel)
		elements.agentInfo.on('click', '.aisales-capability-btn', handleAgentQuickAction);

		// New chat
		elements.newChatButton.on('click', handleNewChat);

		// Accept/Discard all - Products
		$('#aisales-accept-all').on('click', handleAcceptAll);
		$('#aisales-discard-all').on('click', handleDiscardAll);
		
		// Accept/Discard all - Categories
		$('#aisales-category-accept-all').on('click', handleAcceptAll);
		$('#aisales-category-discard-all').on('click', handleDiscardAll);

		// Suggestion actions (delegated)
		elements.messagesContainer.on('click', '.aisales-suggestion [data-action]', handleSuggestionAction);

		// Product panel suggestion actions
		elements.productInfo.on('click', '.aisales-pending-change__actions [data-action]', handlePendingChangeAction);
		
		// Category panel suggestion actions
		elements.categoryInfo.on('click', '.aisales-pending-change__actions [data-action]', handlePendingChangeAction);

		// Expand toggle for description
		elements.entityPanel.on('click', '.aisales-expand-toggle', handleExpandToggle);

		// Store context panel
		elements.storeContextBtn.on('click', openContextPanel);
		$('#aisales-close-context, #aisales-cancel-context').on('click', closeContextPanel);
		elements.contextBackdrop.on('click', closeContextPanel);
		$('#aisales-save-context').on('click', saveStoreContext);
		$('#aisales-sync-context').on('click', syncStoreContext);

		// Onboarding
		$('#aisales-onboarding-setup').on('click', function() {
			closeOnboarding();
			openContextPanel();
		});
		$('#aisales-onboarding-skip').on('click', closeOnboarding);

		// Welcome cards
		elements.welcomeCards.on('click', '.aisales-welcome-card', handleWelcomeCardClick);
		
		// Welcome context hint
		$('#aisales-welcome-setup-context').on('click', openContextPanel);
		
		// Attachment handling
		elements.attachButton.on('click', function() {
			elements.fileInput.click();
		});
		elements.fileInput.on('change', handleFileSelect);
		elements.attachmentPreviews.on('click', '.aisales-attachment-remove', handleRemoveAttachment);
		
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
		const index = $(e.currentTarget).closest('.aisales-attachment-preview').data('index');
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
		const $dropZone = $('.aisales-chat-input');
		
		$dropZone.on('dragenter dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).addClass('aisales-drag-over');
		});

		$dropZone.on('dragleave drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).removeClass('aisales-drag-over');
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
			elements.attachButton.find('.aisales-attachment-count').remove();
			return;
		}

		state.pendingAttachments.forEach(function(attachment, index) {
			const isImage = attachment.mime_type.startsWith('image/');
			const isPdf = attachment.mime_type === 'application/pdf';

			let previewHtml = '<div class="aisales-attachment-preview" data-index="' + index + '">';
			
			if (isImage && attachment.preview) {
				previewHtml += '<img src="' + attachment.preview + '" alt="' + escapeHtml(attachment.filename) + '">';
			} else if (isPdf) {
				previewHtml += '<div class="aisales-attachment-preview__icon"><span class="dashicons dashicons-pdf"></span></div>';
			} else {
				previewHtml += '<div class="aisales-attachment-preview__icon"><span class="dashicons dashicons-media-default"></span></div>';
			}

			previewHtml += '<div class="aisales-attachment-preview__name">' + escapeHtml(truncate(attachment.filename, 15)) + '</div>';
			previewHtml += '<button type="button" class="aisales-attachment-remove" title="Remove"><span class="dashicons dashicons-no-alt"></span></button>';
			previewHtml += '</div>';

			$container.append(previewHtml);
		});

		$container.show();

		// Update attachment count on button
		let $count = elements.attachButton.find('.aisales-attachment-count');
		if ($count.length === 0) {
			$count = $('<span class="aisales-attachment-count"></span>');
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
		elements.entityTabs.find('.aisales-entity-tab').removeClass('aisales-entity-tab--active');
		elements.entityTabs.find('[data-type="' + state.entityType + '"]').addClass('aisales-entity-tab--active');
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
		const $icon = $('#aisales-empty-icon');
		const $text = $('#aisales-empty-text');
		
		if (state.entityType === 'agent') {
			$icon.attr('class', 'dashicons dashicons-superhero-alt');
			$text.text(aisalesChat.i18n.agentReady || 'AI Agent ready to help with marketing');
		} else if (state.entityType === 'category') {
			$icon.attr('class', 'dashicons dashicons-category');
			$text.text(aisalesChat.i18n.selectCategory || 'Select a category to view details');
		} else {
			$icon.attr('class', 'dashicons dashicons-products');
			$text.text(aisalesChat.i18n.selectProduct || 'Select a product to view details');
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
				url: aisalesChat.ajaxUrl,
				method: 'POST',
				data: {
					action: 'aisales_get_category',
					nonce: aisalesChat.nonce,
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
		const $image = $('#aisales-product-image');
		if (product.image_url) {
			$image.attr('src', product.image_url).show();
		} else {
			$image.hide();
		}

		// Title
		$('#aisales-product-title').text(product.title || '');

		// Description
		const desc = product.description || '';
		$('#aisales-product-description').html(desc.substring(0, 200) + (desc.length > 200 ? '...' : ''));

		// Short description
		$('#aisales-product-short-description').text(product.short_description || '');

		// Categories
		const categories = product.categories || [];
		if (categories.length > 0) {
			$('#aisales-product-categories').html(
				categories.map(cat => '<span class="aisales-tag">' + escapeHtml(cat) + '</span>').join('')
			);
		} else {
			$('#aisales-product-categories').html('<span class="aisales-no-value">' + (aisalesChat.i18n.none || 'None') + '</span>');
		}

		// Tags
		const tags = product.tags || [];
		if (tags.length > 0) {
			$('#aisales-product-tags').html(
				tags.map(tag => '<span class="aisales-tag">' + escapeHtml(tag) + '</span>').join('')
			);
		} else {
			$('#aisales-product-tags').html('<span class="aisales-no-value">' + (aisalesChat.i18n.none || 'None') + '</span>');
		}

		// Price
		const priceHtml = formatPrice(product.regular_price, product.sale_price, product.price);
		$('#aisales-product-price').html(priceHtml);

		// Stock
		const stockHtml = formatStock(product.stock_status, product.stock_quantity);
		$('#aisales-product-stock').html(stockHtml);

		// Status
		$('#aisales-product-status').html(formatStatus(product.status));

		// Edit/View links
		$('#aisales-edit-product').attr('href', product.edit_url || '#');
		$('#aisales-view-product').attr('href', product.view_url || '#');

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
		$('#aisales-category-name').text(category.name || '');
		$('#aisales-category-name-value').text(category.name || '');

		// Parent
		if (category.parent_name) {
			$('#aisales-category-parent').text('in ' + category.parent_name).show();
		} else {
			$('#aisales-category-parent').hide();
		}

		// Stats
		$('#aisales-category-product-count').text(category.product_count || 0);
		$('#aisales-category-subcat-count').text(category.subcategory_count || 0);

		// Description
		const desc = category.description || '';
		if (desc) {
			$('#aisales-category-description').html(desc.substring(0, 200) + (desc.length > 200 ? '...' : ''));
		} else {
			$('#aisales-category-description').html('<span class="aisales-no-value">' + (aisalesChat.i18n.noDescription || 'No description') + '</span>');
		}

		// SEO Title
		if (category.seo_title) {
			$('#aisales-category-seo-title').text(category.seo_title);
		} else {
			$('#aisales-category-seo-title').html('<span class="aisales-no-value">' + (aisalesChat.i18n.notSet || 'Not set') + '</span>');
		}

		// Meta Description
		if (category.meta_description) {
			$('#aisales-category-meta-description').text(category.meta_description);
		} else {
			$('#aisales-category-meta-description').html('<span class="aisales-no-value">' + (aisalesChat.i18n.notSet || 'Not set') + '</span>');
		}

		// Subcategories
		const subcats = category.subcategories || [];
		if (subcats.length > 0) {
			$('#aisales-category-subcategories').html(
				subcats.map(sub => '<span class="aisales-subcat-tag"><span class="dashicons dashicons-category"></span>' + escapeHtml(sub.name) + '</span>').join('')
			);
		} else {
			$('#aisales-category-subcategories').html('<span class="aisales-no-value">' + (aisalesChat.i18n.none || 'None') + '</span>');
		}

		// Edit/View links
		$('#aisales-edit-category').attr('href', category.edit_url || '#');
		$('#aisales-view-category').attr('href', category.view_url || '#');

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
			url: aisalesChat.apiBaseUrl + '/ai/chat/sessions',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': aisalesChat.apiKey
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
			url: aisalesChat.apiBaseUrl + '/ai/chat/sessions/' + sessionId,
			method: 'GET',
			headers: {
				'X-API-Key': aisalesChat.apiKey
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
		const url = aisalesChat.apiBaseUrl + '/ai/chat/sessions/' + state.sessionId + '/messages';

		// Use fetch with streaming
		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': aisalesChat.apiKey,
				'Accept': 'text/event-stream'
			},
			body: JSON.stringify(requestData)
		})
		.then(function(response) {
			if (!response.ok) {
				// Handle HTTP errors by parsing the JSON response
				return response.json().then(function(errorData) {
					throw { status: response.status, data: errorData };
				}).catch(function(parseError) {
					// If JSON parsing fails, throw generic error with status
					if (parseError.status) throw parseError;
					throw { status: response.status, data: null };
				});
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
			
			// Handle specific error codes
			let errorMessage = aisalesChat.i18n.connectionError;
			
			if (error.status === 402) {
				// Payment required - insufficient balance
				errorMessage = aisalesChat.i18n.insufficientBalance || 'Insufficient token balance. Please top up to continue.';
			} else if (error.data && error.data.error) {
				// Use error message from API if available
				if (error.data.error.code === 'insufficient_balance') {
					errorMessage = aisalesChat.i18n.insufficientBalance || 'Insufficient token balance. Please top up to continue.';
				} else if (error.data.error.message) {
					errorMessage = error.data.error.message;
				}
			}
			
			addErrorMessage(errorMessage);
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
		let pendingEventType = null; // Persist event type across chunks

		function processChunk(chunk) {
			buffer += decoder.decode(chunk, { stream: true });

			// Process complete events
			const lines = buffer.split('\n');
			buffer = lines.pop() || ''; // Keep incomplete line

			lines.forEach(function(line) {
				if (line.startsWith('event:')) {
					pendingEventType = line.substring(6).trim();
				} else if (line.startsWith('data:')) {
					const dataStr = line.substring(5).trim();
					try {
						const eventData = JSON.parse(dataStr);
						handleSSEEvent(pendingEventType, eventData);
					} catch (e) {
						// Not valid JSON, ignore
					}
					pendingEventType = null; // Reset after handling
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
					addErrorMessage(data.message || aisalesChat.i18n.errorOccurred);
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
		elements.messagesContainer.find('.aisales-message--tool-processing').remove();

		const $toolMessage = $(
			'<div class="aisales-message aisales-message--tool-processing">' +
				'<div class="aisales-message__avatar"><span class="dashicons dashicons-database"></span></div>' +
				'<div class="aisales-message__content">' +
					'<div class="aisales-message__text">' +
						'<span class="aisales-tool-spinner"></span> ' +
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
		elements.messagesContainer.find('.aisales-message--tool-processing').remove();
	}

	/**
	 * Create error results for failed tool requests
	 */
	function createToolErrorResults(requests, errorMessage) {
		return requests.map(function(req) {
			return {
				request_id: req.request_id,
				tool: req.tool,
				success: false,
				error: errorMessage
			};
		});
	}

	/**
	 * Handle tool data requests from AI
	 * Fetches data via WordPress AJAX and sends results back to API
	 */
	function handleToolDataRequests(requests) {
		if (!requests || requests.length === 0) {
			return;
		}

		$.ajax({
			url: aisalesChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'aisales_fetch_tool_data',
				nonce: aisalesChat.nonce,
				requests: JSON.stringify(requests)
			},
			success: function(response) {
				hideToolProcessingMessage();
				if (response.success && response.data && response.data.tool_results) {
					sendToolResults(response.data.tool_results);
				} else {
					sendToolResults(createToolErrorResults(requests, response.data || 'Failed to fetch tool data'));
				}
			},
			error: function(xhr) {
				hideToolProcessingMessage();
				console.error('Tool data fetch error:', xhr);
				sendToolResults(createToolErrorResults(requests, 'Network error fetching tool data'));
			}
		});
	}

	/**
	 * Send tool results back to API to continue the conversation
	 */
	function sendToolResults(toolResults) {
		if (!state.sessionId || !toolResults || toolResults.length === 0) {
			return;
		}

		// Show thinking indicator while AI processes the results
		showThinking();

		const requestData = {
			role: 'tool',
			content: 'Tool execution results',
			tool_results: toolResults
		};

		// Send tool results using SSE for streaming response
		const url = aisalesChat.apiBaseUrl + '/ai/chat/sessions/' + state.sessionId + '/messages';
		console.log('[AISales Debug] Sending tool results to API');
		console.log('[AISales Debug] URL:', url);
		console.log('[AISales Debug] Request body:', JSON.stringify(requestData, null, 2));

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': aisalesChat.apiKey,
				'Accept': 'text/event-stream'
			},
			body: JSON.stringify(requestData)
		})
		.then(function(response) {
			if (!response.ok) {
				// Handle HTTP errors by parsing the JSON response
				return response.json().then(function(errorData) {
					throw { status: response.status, data: errorData };
				}).catch(function(parseError) {
					if (parseError.status) throw parseError;
					throw { status: response.status, data: null };
				});
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
			console.error('[AISales Debug] Tool results send error:', error);
			console.error('[AISales Debug] Error data:', JSON.stringify(error.data, null, 2));
			hideThinking();

			// Handle specific error codes
			let errorMessage = aisalesChat.i18n.connectionError || 'Connection error';

			if (error.status === 402) {
				errorMessage = aisalesChat.i18n.insufficientBalance || 'Insufficient token balance. Please top up to continue.';
			} else if (error.data && error.data.error) {
				if (error.data.error.code === 'insufficient_balance') {
					errorMessage = aisalesChat.i18n.insufficientBalance || 'Insufficient token balance. Please top up to continue.';
				} else if (error.data.error.message) {
					errorMessage = error.data.error.message;
				}
			}
			
			addErrorMessage(errorMessage);
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
		const $suggestion = $(e.currentTarget).closest('.aisales-suggestion');
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
		const $field = $(e.currentTarget).closest('.aisales-product-info__field, .aisales-category-info__field');
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
			url: aisalesChat.apiBaseUrl + '/ai/chat/sessions/' + state.sessionId + '/suggestions/' + suggestionId,
			method: 'PATCH',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': aisalesChat.apiKey
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
						$element.addClass('aisales-suggestion--applied');
						$element.find('.aisales-suggestion__actions').html(
							'<span class="aisales-suggestion__status aisales-suggestion__status--applied">' +
							'<span class="dashicons dashicons-yes-alt"></span> ' +
							aisalesChat.i18n.applied +
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
			url: aisalesChat.apiBaseUrl + '/ai/chat/sessions/' + state.sessionId + '/suggestions/' + suggestionId,
			method: 'PATCH',
			headers: {
				'Content-Type': 'application/json',
				'X-API-Key': aisalesChat.apiKey
			},
			data: JSON.stringify({ action: 'discard' }),
			success: function(response) {
				// Handle both { success: true, data: {...} } and { suggestion: {...} } formats
				const discardedSuggestion = response.suggestion || (response.data && response.data.suggestion);
				if (discardedSuggestion || response.success) {
					suggestion.status = 'discarded';

					// Update UI
					if ($element) {
						$element.addClass('aisales-suggestion--discarded');
						$element.find('.aisales-suggestion__actions').html(
							'<span class="aisales-suggestion__status aisales-suggestion__status--discarded">' +
							'<span class="dashicons dashicons-dismiss"></span> ' +
							aisalesChat.i18n.discarded +
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
		const $field = $(e.currentTarget).closest('.aisales-product-info__field, .aisales-category-info__field');
		$field.toggleClass('aisales-product-info__field--expanded aisales-category-info__field--expanded');
		$field.find('.aisales-product-info__value, .aisales-category-info__value').toggleClass('aisales-product-info__value--truncated aisales-category-info__value--truncated');
	}

	/**
	 * Handle welcome card click
	 */
	function handleWelcomeCardClick(e) {
		const $card = $(e.currentTarget);
		const entityType = $card.data('entity');
		
		if (entityType && entityType !== state.entityType) {
			state.entityType = entityType;
			state.isAgentMode = (entityType === 'agent');
			updateEntityTabs();
			populateEntitySelector();
			updateQuickActionsVisibility();
			updateEmptyState();
			
			// If agent mode, activate immediately
			if (entityType === 'agent') {
				selectAgent();
				return;
			}
		}
		
		// Focus the selector (for product/category modes)
		elements.entitySelect.focus();
	}

	/**
	 * Open store context panel
	 */
	function openContextPanel() {
		elements.contextPanel.addClass('aisales-context-panel--open');
		$('body').addClass('aisales-context-panel-active');
	}

	/**
	 * Close store context panel
	 */
	function closeContextPanel() {
		elements.contextPanel.removeClass('aisales-context-panel--open');
		$('body').removeClass('aisales-context-panel-active');
	}

	/**
	 * Save store context
	 */
	function saveStoreContext() {
		const formData = {
			store_name: $('#aisales-store-name').val(),
			store_description: $('#aisales-store-description').val(),
			business_niche: $('#aisales-business-niche').val(),
			target_audience: $('#aisales-target-audience').val(),
			brand_tone: $('input[name="brand_tone"]:checked').val() || '',
			language: $('#aisales-language').val(),
			custom_instructions: $('#aisales-custom-instructions').val()
		};

		const $saveBtn = $('#aisales-save-context');
		$saveBtn.addClass('aisales-btn--loading').prop('disabled', true);

		$.ajax({
			url: aisalesChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'aisales_save_store_context',
				nonce: aisalesChat.nonce,
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
				$saveBtn.removeClass('aisales-btn--loading').prop('disabled', false);
			}
		});
	}

	/**
	 * Sync store context (re-fetch category/product counts)
	 */
	function syncStoreContext() {
		const $syncBtn = $('#aisales-sync-context');
		$syncBtn.addClass('aisales-btn--loading').prop('disabled', true);

		$.ajax({
			url: aisalesChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'aisales_sync_store_context',
				nonce: aisalesChat.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#aisales-sync-status').text(response.data.message || 'Synced successfully');
				} else {
					showNotice(response.data || 'Sync failed', 'error');
				}
			},
			error: function() {
				showNotice('Sync failed', 'error');
			},
			complete: function() {
				$syncBtn.removeClass('aisales-btn--loading').prop('disabled', false);
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

		$('.aisales-context-status')
			.removeClass('aisales-context-status--configured aisales-context-status--partial aisales-context-status--missing')
			.addClass('aisales-context-status--' + status);

		// Update store name in button
		if (context.store_name) {
			$('.aisales-store-name').text(context.store_name);
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
			url: aisalesChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'aisales_mark_chat_visited',
				nonce: aisalesChat.nonce
			}
		});
	}

	/**
	 * Show a notice
	 */
	function showNotice(message, type) {
		const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
		$('.aisales-chat-page-header').after($notice);
		
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
				'<span class="aisales-message__tokens">' + message.tokens_input + ' + ' + message.tokens_output + ' ' + aisalesChat.i18n.tokensUsed + '</span>' :
				''
			);

		const $message = $(html);

		// Add attachment indicators for user messages
		if (message.attachments && message.attachments.length > 0) {
			let attachmentHtml = '<div class="aisales-message__attachments">';
			message.attachments.forEach(function(att) {
				const isImage = att.mime_type && att.mime_type.startsWith('image/');
				const isPdf = att.mime_type === 'application/pdf';
				const icon = isImage ? 'dashicons-format-image' : (isPdf ? 'dashicons-pdf' : 'dashicons-media-default');
				attachmentHtml += '<span class="aisales-message__attachment"><span class="dashicons ' + icon + '"></span>' + escapeHtml(att.filename) + '</span>';
			});
			attachmentHtml += '</div>';
			$message.find('.aisales-message__content').prepend(attachmentHtml);
		}

		if (isStreaming) {
			$message.addClass('aisales-message--streaming');
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
			$streaming.find('.aisales-message__text').html(formatMessageContent(content));
			scrollToBottom();
		}
	}

	/**
	 * Render suggestion in message - groups multiple suggestions of same type
	 */
	function renderSuggestionInMessage(suggestion) {
		const typeLabels = {
			// Product fields
			'title': aisalesChat.i18n.improveTitle || 'Improve Title',
			'description': aisalesChat.i18n.improveDescription || 'Improve Description',
			'short_description': 'Short Description',
			'tags': aisalesChat.i18n.suggestTags || 'Tags',
			'categories': aisalesChat.i18n.suggestCategories || 'Categories',
			'meta_description': 'Meta Description',
			// Category fields
			'name': 'Category Name',
			'seo_title': 'SEO Title',
			'seo_description': 'SEO Description',
			'subcategories': 'Subcategories'
		};

		const $container = elements.messagesContainer.find('[data-streaming="true"]').length
			? elements.messagesContainer.find('[data-streaming="true"] .aisales-message__content')
			: elements.messagesContainer.find('.aisales-message--assistant').last().find('.aisales-message__content');

		if (!$container.length) return;

		// Check if there's already a group for this suggestion type
		let $group = $container.find('.aisales-suggestions-group[data-type="' + suggestion.suggestion_type + '"]');

		if ($group.length) {
			// Add to existing group
			const $options = $group.find('.aisales-suggestions-group__options');
			const optionNumber = $options.find('.aisales-suggestion').length + 1;
			
			const optionHtml = renderSuggestionOption(suggestion, optionNumber);
			$options.append(optionHtml);
			
			// Update count
			$group.find('.aisales-suggestions-group__count').text(optionNumber + ' options');
		} else {
			// Create new group
			const groupHtml = '<div class="aisales-suggestions-group" data-type="' + suggestion.suggestion_type + '">' +
				'<div class="aisales-suggestions-group__header">' +
					'<div class="aisales-suggestions-group__title">' +
						'<span class="dashicons dashicons-lightbulb"></span>' +
						'<span>' + (typeLabels[suggestion.suggestion_type] || suggestion.suggestion_type) + '</span>' +
					'</div>' +
					'<span class="aisales-suggestions-group__count">1 option</span>' +
				'</div>' +
				'<div class="aisales-suggestions-group__current">' +
					'<div class="aisales-suggestions-group__current-label">Current value</div>' +
					'<div class="aisales-suggestions-group__current-value">' + escapeHtml(suggestion.current_value || 'None') + '</div>' +
				'</div>' +
				'<div class="aisales-suggestions-group__options">' +
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
		return '<div class="aisales-suggestion" data-suggestion-id="' + suggestion.id + '" data-suggestion-type="' + suggestion.suggestion_type + '">' +
			'<span class="aisales-suggestion__number">' + number + '</span>' +
			'<div class="aisales-suggestion__content">' +
				'<div class="aisales-suggestion__value">' + escapeHtml(suggestion.suggested_value) + '</div>' +
			'</div>' +
			'<div class="aisales-suggestion__actions">' +
				'<button type="button" class="aisales-btn aisales-btn--success aisales-btn--sm" data-action="apply">' +
					'<span class="dashicons dashicons-yes"></span> Apply' +
				'</button>' +
				'<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="discard">' +
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
		elements.messagesContainer.find('.aisales-message--thinking').remove();
		elements.messagesContainer.append(templates.thinking);
		scrollToBottom();
	}

	/**
	 * Hide thinking indicator
	 */
	function hideThinking() {
		elements.messagesContainer.find('.aisales-message--thinking').remove();
	}

	/**
	 * Add error message
	 */
	function addErrorMessage(text) {
		const $error = $('<div class="aisales-message aisales-message--error">' +
			'<div class="aisales-message__avatar"><span class="dashicons dashicons-warning"></span></div>' +
			'<div class="aisales-message__content">' +
			'<div class="aisales-message__text">' + escapeHtml(text) + '</div>' +
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

		$field.addClass('aisales-product-info__field--has-pending aisales-category-info__field--has-pending');
		const $pending = $field.find('.aisales-product-info__pending, .aisales-category-info__pending');

		if (suggestion.suggestion_type === 'tags' || suggestion.suggestion_type === 'categories' || suggestion.suggestion_type === 'subcategories') {
			// Handle array fields
			const currentArr = (suggestion.current_value || '').split(',').map(s => s.trim()).filter(Boolean);
			const suggestedArr = (suggestion.suggested_value || '').split(',').map(s => s.trim()).filter(Boolean);
			const added = suggestedArr.filter(t => !currentArr.includes(t));
			const removed = currentArr.filter(t => !suggestedArr.includes(t));

			$pending.find('.aisales-pending-change__added').html(
				added.map(t => '<span class="aisales-tag aisales-tag--added">+ ' + escapeHtml(t) + '</span>').join('')
			);
			$pending.find('.aisales-pending-change__removed').html(
				removed.map(t => '<span class="aisales-tag aisales-tag--removed">- ' + escapeHtml(t) + '</span>').join('')
			);
		} else {
			// Handle text fields - only show new value
			$pending.find('.aisales-pending-change__new').text(suggestion.suggested_value);
		}

		$pending.slideDown(200);
	}

	/**
	 * Hide pending change in entity panel
	 */
	function hidePendingChange(fieldType) {
		const $panel = state.entityType === 'category' ? elements.categoryInfo : elements.productInfo;
		const $field = $panel.find('[data-field="' + fieldType + '"]');
		$field.removeClass('aisales-product-info__field--has-pending aisales-category-info__field--has-pending');
		$field.find('.aisales-product-info__pending, .aisales-category-info__pending').slideUp(200);
	}

	/**
	 * Clear all pending changes from entity panel
	 */
	function clearPendingChanges() {
		elements.productInfo.find('.aisales-product-info__field--has-pending').removeClass('aisales-product-info__field--has-pending');
		elements.productInfo.find('.aisales-product-info__pending').hide();
		elements.categoryInfo.find('.aisales-category-info__field--has-pending').removeClass('aisales-category-info__field--has-pending');
		elements.categoryInfo.find('.aisales-category-info__pending').hide();
	}

	/**
	 * Update pending summary
	 */
	function updatePendingSummary() {
		const count = Object.keys(state.pendingSuggestions).filter(function(id) {
			return state.pendingSuggestions[id].status === 'pending';
		}).length;

		if (state.entityType === 'category') {
			$('#aisales-category-pending-count').text(count);
			if (count > 0) {
				elements.categoryPendingSummary.slideDown(200);
			} else {
				elements.categoryPendingSummary.slideUp(200);
			}
			elements.pendingSummary.hide();
		} else {
			$('#aisales-pending-count').text(count);
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
			url: aisalesChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'aisales_update_product_field',
				nonce: aisalesChat.nonce,
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
			url: aisalesChat.ajaxUrl,
			method: 'POST',
			data: {
				action: 'aisales_update_category_field',
				nonce: aisalesChat.nonce,
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
		elements.tokensUsed.text(tokens.total + ' ' + aisalesChat.i18n.tokensUsed);
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
			url: aisalesChat.ajaxUrl,
			type: 'POST',
			data: {
				action: 'aisales_sync_balance',
				nonce: aisalesChat.nonce,
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
				url: aisalesChat.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_save_generated_image',
					nonce: aisalesChat.nonce,
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
				url: aisalesChat.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aisales_get_store_summary',
					nonce: aisalesChat.nonce
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
			elements.sendButton.addClass('aisales-btn--loading');
		} else {
			elements.sendButton.removeClass('aisales-btn--loading');
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
		let message = aisalesChat.i18n.errorOccurred;

		if (xhr.responseJSON && xhr.responseJSON.error) {
			message = xhr.responseJSON.error.message || message;

			if (xhr.responseJSON.error.code === 'INSUFFICIENT_BALANCE') {
				message = aisalesChat.i18n.insufficientBalance;
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
		if (!price && !regularPrice) return '<span class="aisales-no-value">Not set</span>';

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
			'instock': '<span class="aisales-stock aisales-stock--instock">In Stock</span>',
			'outofstock': '<span class="aisales-stock aisales-stock--outofstock">Out of Stock</span>',
			'onbackorder': '<span class="aisales-stock aisales-stock--backorder">On Backorder</span>'
		};

		let html = statusLabels[status] || '<span class="aisales-no-value">Unknown</span>';

		if (quantity !== null && quantity !== undefined && status === 'instock') {
			html += ' <span class="aisales-stock__qty">(' + quantity + ')</span>';
		}

		return html;
	}

	/**
	 * Format status display
	 */
	function formatStatus(status) {
		const statusClasses = {
			'publish': 'aisales-status--published',
			'draft': 'aisales-status--draft',
			'pending': 'aisales-status--pending',
			'private': 'aisales-status--private'
		};

		const statusLabels = {
			'publish': 'Published',
			'draft': 'Draft',
			'pending': 'Pending',
			'private': 'Private'
		};

		const cls = statusClasses[status] || '';
		const label = statusLabels[status] || status || 'Unknown';

		return '<span class="aisales-status ' + cls + '">' + label + '</span>';
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
		$el.parent().addClass(isDecreasing ? 'aisales-balance--decreasing' : 'aisales-balance--increasing');
		
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
					$el.parent().removeClass('aisales-balance--decreasing aisales-balance--increasing');
				}, 300);
			}
		}
		
		requestAnimationFrame(step);
	}

	// Initialize when DOM is ready
	$(document).ready(init);

})(jQuery);
