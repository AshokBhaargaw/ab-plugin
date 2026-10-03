/**
 * AB Custom CSS - Elementor Editor Live Preview & Element Tree Helper
 *
 * Provides real-time live preview across Desktop, Tablet, and Mobile views,
 * and dynamically scans element DOM trees, classes, and IDs with 1-click CSS insertion.
 *
 * @package AB_Addon
 * @version 1.2.1
 */
(function ($) {
	'use strict';

	var activeModel = null;
	var currentViewMode = 'tree'; // 'tree' or 'badges'

	/**
	 * Replace 'selector' keyword with the unique element selector.
	 * Avoids matching partial words like .my-selector or selector-box.
	 *
	 * @param {string} css
	 * @param {string} elementId
	 * @returns {string}
	 */
	function replaceSelector(css, elementId) {
		if (!css || typeof css !== 'string') {
			return '';
		}

		var uniqueSelector = '.elementor-element.elementor-element-' + elementId;
		return css.replace(/(^|[^a-zA-Z0-9_-])selector(?![a-zA-Z0-9_-])/g, '$1' + uniqueSelector);
	}

	/**
	 * Get the preview iframe's document object reliably.
	 *
	 * @returns {Document|null}
	 */
	function getPreviewDocument() {
		if (window.elementor && window.elementor.$preview && window.elementor.$preview[0]) {
			try {
				var d = window.elementor.$preview[0].contentDocument || window.elementor.$preview[0].contentWindow.document;
				if (d) return d;
			} catch (e) {}
		}

		var previewFrame = document.querySelector('#elementor-preview-iframe');
		if (previewFrame) {
			try {
				return previewFrame.contentDocument || previewFrame.contentWindow.document;
			} catch (e) {
				return null;
			}
		}

		return null;
	}

	/**
	 * Retrieve the active element model in the Elementor editor.
	 *
	 * @returns {object|null}
	 */
	function getActiveModel() {
		if (activeModel) {
			return activeModel;
		}

		if (window.elementor) {
			// 1. Current view model in panel
			if (window.elementor.panel && window.elementor.panel.currentView && window.elementor.panel.currentView.model) {
				return window.elementor.panel.currentView.model;
			}

			// 2. Selection elements
			if (window.elementor.selection && typeof window.elementor.selection.getElements === 'function') {
				var selected = window.elementor.selection.getElements();
				if (selected && selected.length && selected[0].model) {
					return selected[0].model;
				}
			}

			// 3. Document fallback
			if (window.elementor.config && window.elementor.config.document) {
				return {
					get: function (prop) {
						if (prop === 'id') return 'doc-' + window.elementor.config.document.id;
						if (prop === 'settings') return (window.elementor.settings && window.elementor.settings.page && window.elementor.settings.page.model) ? window.elementor.settings.page.model.get('settings') : null;
						return null;
					}
				};
			}
		}

		return null;
	}

	/**
	 * Compile full CSS across Desktop, Tablet, and Mobile settings for an element.
	 *
	 * @param {object} settings
	 * @param {string} id
	 * @returns {string}
	 */
	function compileFullCss(settings, id) {
		if (!settings) return '';

		var customCss = settings.get('ab_custom_css');
		if ((typeof customCss !== 'string' || !customCss.trim()) && !window.ElementorPro) {
			customCss = settings.get('custom_css');
		}

		var tabletCss = settings.get('ab_custom_css_tablet');
		var mobileCss = settings.get('ab_custom_css_mobile');

		var totalCss = '';

		// 1. Desktop
		if (customCss && typeof customCss === 'string' && customCss.trim()) {
			totalCss += '\n/* Desktop View */\n' + replaceSelector(customCss, id) + '\n';
		}

		// 2. Tablet
		if (tabletCss && typeof tabletCss === 'string' && tabletCss.trim()) {
			totalCss += '\n/* Tablet View (max-width: 1024px) */\n@media (max-width: 1024px) {\n' + replaceSelector(tabletCss, id) + '\n}\n';
		}

		// 3. Mobile
		if (mobileCss && typeof mobileCss === 'string' && mobileCss.trim()) {
			totalCss += '\n/* Mobile View (max-width: 767px) */\n@media (max-width: 767px) {\n' + replaceSelector(mobileCss, id) + '\n}\n';
		}

		return totalCss;
	}

	/**
	 * Inject or update live CSS in the preview iframe document head.
	 *
	 * @param {object} model Element model.
	 */
	function updateLivePreview(model) {
		model = model || getActiveModel();
		if (!model || typeof model.get !== 'function') {
			return;
		}

		var settings = model.get('settings');
		if (!settings) {
			return;
		}

		var id = model.get('id');
		if (!id) {
			var pageId = (window.elementor && window.elementor.config && window.elementor.config.document)
				? window.elementor.config.document.id
				: null;
			if (!pageId) return;
			id = 'doc-' + pageId;
		}

		var previewDoc = getPreviewDocument();
		if (!previewDoc) {
			return;
		}

		var compiled = compileFullCss(settings, id);
		var styleTagId = 'ab-live-css-' + id;
		var existingStyle = previewDoc.getElementById(styleTagId);

		if (!compiled || !compiled.trim()) {
			if (existingStyle) {
				existingStyle.remove();
			}
			return;
		}

		if (!existingStyle) {
			existingStyle = previewDoc.createElement('style');
			existingStyle.id = styleTagId;
			existingStyle.type = 'text/css';
			previewDoc.head.appendChild(existingStyle);
		}

		existingStyle.textContent = compiled;
	}

	/**
	 * Check if a DOM node is part of Elementor's editor UI controls/overlays.
	 *
	 * @param {HTMLElement} node
	 * @returns {boolean}
	 */
	function isEditorOverlay(node) {
		if (!node || node.nodeType !== 1) return true;

		var cls = (typeof node.className === 'string') ? node.className : (node.getAttribute('class') || '');
		if (cls.indexOf('elementor-editor-') !== -1 ||
			cls.indexOf('ui-resizable') !== -1 ||
			cls.indexOf('elementor-element-overlay') !== -1 ||
			cls.indexOf('elementor-empty-view') !== -1 ||
			cls.indexOf('elementor-element-empty') !== -1 ||
			cls.indexOf('elementor-handle') !== -1) {
			return true;
		}

		if (node.closest) {
			if (node.closest('.elementor-editor-element-settings, .elementor-editor-widget-settings, .elementor-editor-element-edit, .ui-resizable-handle, .elementor-element-overlay, .elementor-empty-view, .elementor-handle')) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build hierarchical DOM tree object for the element and nested children.
	 *
	 * @param {HTMLElement} node
	 * @param {number} depth
	 * @param {number} maxDepth
	 * @returns {object|null}
	 */
	function buildElementTree(node, depth, maxDepth) {
		if (!node || node.nodeType !== 1 || depth > maxDepth) return null;
		if (isEditorOverlay(node)) return null;

		var tag = node.tagName.toLowerCase();
		if (['script', 'style', 'noscript', 'link', 'meta', 'template'].indexOf(tag) !== -1) {
			return null;
		}

		var id = (node.id && node.id.trim()) ? node.id.trim() : '';
		var classes = [];
		if (node.classList && node.classList.length) {
			for (var i = 0; i < node.classList.length; i++) {
				var c = node.classList[i].trim();
				if (!c) continue;
				if (c.indexOf('elementor-element-') === 0) continue;
				if (c.indexOf('elementor-editor-') === 0) continue;
				if (c.indexOf('ui-resizable') === 0) continue;
				if (['elementor-element', 'elementor-widget', 'elementor-column', 'elementor-section', 'elementor-container'].indexOf(c) !== -1) continue;
				classes.push(c);
			}
		}

		// Calculate the cleanest CSS selector for this node (Priority: ID > Class > Tag)
		var selector = 'selector';
		if (id) {
			selector = 'selector #' + id;
		} else if (classes.length) {
			selector = 'selector .' + (classes.length > 1 ? classes.join('.') : classes[0]);
		} else if (tag) {
			selector = 'selector ' + tag;
		}

		var children = [];
		var childNodes = node.children;
		if (childNodes && childNodes.length) {
			for (var ch = 0; ch < childNodes.length; ch++) {
				var childTree = buildElementTree(childNodes[ch], depth + 1, maxDepth);
				if (childTree) {
					children.push(childTree);
				}
			}
		}

		return {
			tag: tag,
			id: id,
			classes: classes,
			selector: selector,
			children: children,
			depth: depth
		};
	}

	/**
	 * Render the Element Tree into collapsible HTML with 1-click insertable badges.
	 *
	 * @param {object} treeNode
	 * @returns {string}
	 */
	function renderTreeHtml(treeNode) {
		if (!treeNode) return '';

		var hasChildren = treeNode.children && treeNode.children.length > 0;
		var html = '<div class="ab-tree-node' + (hasChildren ? ' ab-has-children' : '') + '">';
		html += '<div class="ab-tree-row">';

		if (hasChildren) {
			html += '<span class="ab-tree-toggle" title="Toggle children">▾</span>';
		} else {
			html += '<span class="ab-tree-bullet">•</span>';
		}

		// Tag badge (clickable to insert)
		var tagSelector = 'selector ' + treeNode.tag;
		html += '<button type="button" class="ab-tree-tag ab-clickable-pill" data-selector="' + tagSelector + '" title="Insert: ' + tagSelector + '">&lt;' + treeNode.tag + '</button>';

		// ID badge (clickable to insert)
		if (treeNode.id) {
			var idSelector = 'selector #' + treeNode.id;
			html += ' <button type="button" class="ab-tree-id ab-clickable-pill" data-selector="' + idSelector + '" title="Insert: ' + idSelector + '">#' + treeNode.id + '</button>';
		}

		// Classes badges (clickable to insert)
		if (treeNode.classes && treeNode.classes.length) {
			treeNode.classes.forEach(function (cls) {
				var classSelector = 'selector .' + cls;
				html += ' <button type="button" class="ab-tree-class ab-clickable-pill" data-selector="' + classSelector + '" title="Insert: ' + classSelector + '">.' + cls + '</button>';
			});
		}

		html += '<span class="ab-tree-close-bracket">&gt;</span>';

		// Node-level Insert action button (ID > Class > Tag)
		html += '<button type="button" class="ab-tree-insert-btn" data-selector="' + treeNode.selector + '" title="Insert: ' + treeNode.selector + ' {\n  \n}">';
		html += '<i class="eicon-plus"></i> <span>Insert</span>';
		html += '</button>';

		html += '</div>'; // .ab-tree-row

		if (hasChildren) {
			html += '<div class="ab-tree-children">';
			treeNode.children.forEach(function (child) {
				html += renderTreeHtml(child);
			});
			html += '</div>';
		}

		html += '</div>'; // .ab-tree-node
		return html;
	}

	/**
	 * Scan the preview DOM for the current element and render both Tree View and Badges View.
	 *
	 * @param {object} model
	 */
	function scanAndDisplayClasses(model) {
		model = model || getActiveModel();
		if (!model || typeof model.get !== 'function') return;

		var id = model.get('id');
		if (!id) return;

		var $box = $('.ab-custom-css-classes-box');
		if (!$box.length) return;

		var $content = $box.find('.ab-classes-content');
		if (!$content.length) {
			$box.html(
				'<div class="ab-classes-header">' +
					'<div class="ab-view-switcher">' +
						'<button type="button" class="ab-view-btn ' + (currentViewMode === 'tree' ? 'active' : '') + '" data-view="tree"><i class="eicon-tree-view"></i> Tree View</button>' +
						'<button type="button" class="ab-view-btn ' + (currentViewMode === 'badges' ? 'active' : '') + '" data-view="badges"><i class="eicon-tags"></i> All Tags & Classes</button>' +
					'</div>' +
					'<button type="button" class="ab-classes-refresh-btn" title="Rescan element structure"><i class="eicon-sync"></i> Rescan</button>' +
				'</div>' +
				'<div class="ab-classes-hint">Click any tag, class, or <b>Insert</b> button to write CSS:</div>' +
				'<div class="ab-classes-content"></div>'
			);
			$content = $box.find('.ab-classes-content');
		}

		var previewDoc = getPreviewDocument();
		var el = null;

		if (previewDoc) {
			el = previewDoc.querySelector('[data-id="' + id + '"]') ||
				previewDoc.querySelector('.elementor-element-' + id) ||
				previewDoc.getElementById(id);
		}

		// Find content root inside widget to skip editor handle overlays
		var contentRoot = el;
		if (el) {
			var widgetContainer = el.querySelector('.elementor-widget-container');
			if (widgetContainer) {
				contentRoot = widgetContainer;
			}
		}

		// 1. Build Tree
		var treeHtml = '';
		if (contentRoot) {
			var treeObj = buildElementTree(contentRoot, 0, 8);
			if (treeObj) {
				treeHtml = '<div class="ab-tree-wrapper">' + renderTreeHtml(treeObj) + '</div>';
			}
		}

		if (!treeHtml) {
			treeHtml = '<div class="ab-classes-empty"><i class="eicon-info-circle"></i> No HTML content detected yet. Rescan after editing.</div>';
		}

		// 2. Build Badges List
		var items = [];
		var seen = {};

		function addItem(selector, label, type) {
			if (!seen[selector]) {
				seen[selector] = true;
				items.push({ selector: selector, label: label, type: type });
			}
		}

		addItem('selector', 'selector (Wrapper)', 'wrapper');

		if (el) {
			// Root element ID
			if (el.id && el.id.trim()) {
				addItem('selector#' + el.id.trim(), '#' + el.id.trim(), 'id');
			}

			// Root element classes
			if (el.classList && el.classList.length) {
				for (var r = 0; r < el.classList.length; r++) {
					var rootCls = el.classList[r].trim();
					if (!rootCls || rootCls.indexOf('elementor-element') === 0 || rootCls.indexOf('elementor-editor-') === 0 || rootCls.indexOf('ui-resizable') === 0) continue;
					addItem('selector.' + rootCls, '.' + rootCls, 'class');
				}
			}

			// Descendants (strictly skipping overlays)
			var targetNode = widgetContainer || el;
			var descendants = targetNode.querySelectorAll('*');
			for (var d = 0; d < descendants.length; d++) {
				var node = descendants[d];
				if (isEditorOverlay(node)) continue;

				if (node.id && node.id.trim()) {
					var childId = node.id.trim();
					addItem('selector #' + childId, '#' + childId, 'id');
				}

				if (node.classList && node.classList.length) {
					for (var c = 0; c < node.classList.length; c++) {
						var cls = node.classList[c].trim();
						if (!cls || cls.indexOf('elementor-element-') === 0 || cls.indexOf('elementor-editor-') === 0 || cls.indexOf('ui-resizable') === 0) continue;
						addItem('selector .' + cls, '.' + cls, 'class');
					}
				}

				var tag = node.tagName.toLowerCase();
				if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'a', 'img', 'button', 'input', 'textarea', 'ul', 'li', 'span', 'svg', 'i', 'table', 'form'].indexOf(tag) !== -1) {
					addItem('selector ' + tag, tag, 'tag');
				}
			}
		}

		var badgesHtml = '<div class="ab-badges-wrapper">';
		items.forEach(function (item) {
			var badgeClass = 'ab-pill-' + item.type;
			badgesHtml += '<button type="button" class="ab-class-pill ' + badgeClass + '" data-selector="' + item.selector + '" title="Click to insert: ' + item.selector + ' {\n  \n}">'
				+ '<span class="ab-pill-name">' + item.label + '</span>'
				+ '<span class="ab-pill-action"><i class="eicon-plus"></i></span>'
				+ '</button>';
		});
		badgesHtml += '</div>';

		// Render active view
		if (currentViewMode === 'tree') {
			$content.html(treeHtml);
		} else {
			$content.html(badgesHtml);
		}

		// Store both views in DOM data for instant switching
		$box.data('tree-html', treeHtml);
		$box.data('badges-html', badgesHtml);
	}

	/**
	 * Run scanner using active model.
	 */
	function runScanner() {
		var model = getActiveModel();
		if (model) {
			scanAndDisplayClasses(model);
		}
	}

	/**
	 * Get the currently active CodeMirror editor and field name in the Custom CSS tabs.
	 *
	 * @returns {object|null}
	 */
	function getActiveEditorTarget() {
		var field = 'ab_custom_css';
		var $panel = $('#elementor-panel');

		// 1. Determine active responsive tab
		if ($panel.find('.elementor-tab-control-ab_custom_css_tablet_tab.elementor-active, [data-tab="ab_custom_css_tablet_tab"].elementor-active').length) {
			field = 'ab_custom_css_tablet';
		} else if ($panel.find('.elementor-tab-control-ab_custom_css_mobile_tab.elementor-active, [data-tab="ab_custom_css_mobile_tab"].elementor-active').length) {
			field = 'ab_custom_css_mobile';
		}

		// 2. Try to get control view from Elementor Marionette
		try {
			if (window.elementor && window.elementor.panel && window.elementor.panel.currentView) {
				var pv = window.elementor.getPanelView ? window.elementor.getPanelView() : window.elementor.panel.currentView;
				var pageView = (pv.getCurrentPageView && pv.getCurrentPageView()) || pv.currentPageView || pv;
				if (pageView && pageView.children) {
					var targetView = pageView.children.find(function (child) {
						return child && child.model && child.model.get('name') === field;
					});
					if (targetView && targetView.editor) {
						return { cm: targetView.editor, textarea: targetView.ui ? targetView.ui.textarea[0] : null, field: field };
					}
				}
			}
		} catch (e) {}

		// 3. Find visible control in DOM
		var $control = $panel.find('.elementor-control-' + field);
		if (!$control.length || $control.is(':hidden')) {
			$control = $panel.find('.elementor-control-type-code:visible').first();
		}
		if (!$control.length) {
			$control = $panel.find('.elementor-control-ab_custom_css, .elementor-control-type-code').first();
		}

		// 4. Check CodeMirror DOM element
		var $cmEl = $control.find('.CodeMirror');
		if ($cmEl.length && $cmEl[0].CodeMirror) {
			return { cm: $cmEl[0].CodeMirror, textarea: $control.find('textarea')[0], field: field };
		}

		// 5. Check textarea DOM element and attached instances
		var $ta = $control.find('textarea');
		if ($ta.length) {
			var ta = $ta[0];
			if (ta.CodeMirror) {
				return { cm: ta.CodeMirror, textarea: ta, field: field };
			}
			if (ta.nextElementSibling && ta.nextElementSibling.CodeMirror) {
				return { cm: ta.nextElementSibling.CodeMirror, textarea: ta, field: field };
			}
			var cmData = $ta.data('CodeMirror') || $ta.data('codemirror');
			if (cmData) {
				return { cm: cmData, textarea: ta, field: field };
			}
			return { cm: null, textarea: ta, field: field };
		}

		// 6. Global visible CodeMirror fallback
		var allCm = $panel.find('.CodeMirror');
		for (var i = 0; i < allCm.length; i++) {
			if (allCm[i].CodeMirror && $(allCm[i]).is(':visible')) {
				return { cm: allCm[i].CodeMirror, textarea: $(allCm[i]).prev('textarea')[0], field: field };
			}
		}

		return null;
	}

	/**
	 * Insert a CSS selector rule into the currently active CodeMirror editor or textarea.
	 *
	 * @param {string} selector
	 * @returns {boolean} Success state
	 */
	function insertSelectorIntoActiveEditor(selector) {
		var target = getActiveEditorTarget();
		var ruleSnippet = selector + ' {\n\t\n}\n';
		var field = target ? target.field : 'ab_custom_css';
		var inserted = false;

		if (target && target.cm) {
			var cm = target.cm;
			var doc = cm.getDoc ? cm.getDoc() : cm;
			var currentVal = doc.getValue() || '';

			if (!currentVal.trim()) {
				doc.setValue(ruleSnippet);
				cm.setCursor({ line: 1, ch: 1 });
			} else {
				var cursor = cm.getCursor ? cm.getCursor() : { line: doc.lineCount(), ch: 0 };
				var textBefore = doc.getRange({ line: 0, ch: 0 }, cursor);
				var prefix = (textBefore.length > 0 && !textBefore.endsWith('\n\n'))
					? (textBefore.endsWith('\n') ? '\n' : '\n\n')
					: '';

				doc.replaceRange(prefix + ruleSnippet, cursor);
				var addedLineCount = prefix.split('\n').length - 1;
				cm.setCursor({ line: cursor.line + addedLineCount + 1, ch: 1 });
			}

			cm.focus();
			if (cm.save) {
				cm.save();
			}

			var updatedVal = doc.getValue();

			// Sync with textarea
			if (target.textarea) {
				$(target.textarea).val(updatedVal).trigger('input').trigger('change');
			}

			// Sync with Elementor Backbone model
			var model = getActiveModel();
			if (model && model.get) {
				var settings = model.get('settings');
				if (settings && field) {
					settings.set(field, updatedVal);
				}
			}

			if (window.elementor && window.elementor.saver && typeof window.elementor.saver.setFlagEditorChange === 'function') {
				window.elementor.saver.setFlagEditorChange(true);
			}

			inserted = true;
		} else if (target && target.textarea) {
			var ta = target.textarea;
			var orig = ta.value || '';
			var prefix = (orig.length > 0 && !orig.endsWith('\n\n')) ? (orig.endsWith('\n') ? '\n' : '\n\n') : '';
			ta.value = orig + prefix + ruleSnippet;
			$(ta).trigger('input').trigger('change');

			var model = getActiveModel();
			if (model && model.get) {
				var settings = model.get('settings');
				if (settings && field) {
					settings.set(field, ta.value);
				}
			}

			if (window.elementor && window.elementor.saver && typeof window.elementor.saver.setFlagEditorChange === 'function') {
				window.elementor.saver.setFlagEditorChange(true);
			}

			inserted = true;
		}

		// Update live preview immediately
		var activeM = getActiveModel();
		if (activeM) {
			updateLivePreview(activeM);
		}

		return inserted;
	}

	/**
	 * Main initializer.
	 */
	function init() {
		if (typeof window.elementor === 'undefined' || !window.elementor.hooks) {
			return;
		}

		// 1. Hook into Elementor's native style filter so element style tags include all custom CSS.
		window.elementor.hooks.addFilter('editor/style/styleText', function (css, context) {
			if (!context || !context.model) {
				return css;
			}

			var model = context.model;
			var settings = model.get('settings');
			if (!settings) {
				return css;
			}

			var id = model.get('id');
			if (!id && window.elementor.config && window.elementor.config.document) {
				id = 'doc-' + window.elementor.config.document.id;
			}

			if (id) {
				var compiled = compileFullCss(settings, id);
				if (compiled) {
					css += '\n' + compiled + '\n';
				}
			}

			return css;
		});

		// 2. Listen to editor control changes across all 3 responsive fields
		if (window.elementor.channels && window.elementor.channels.editor) {
			var responsiveFields = ['ab_custom_css', 'ab_custom_css_tablet', 'ab_custom_css_mobile', 'custom_css'];

			responsiveFields.forEach(function (field) {
				window.elementor.channels.editor.on('change:' + field, function (controlView, elementView) {
					var model = null;
					if (elementView && elementView.model) {
						model = elementView.model;
					} else if (controlView && controlView.model) {
						model = controlView.model;
					}

					if (!model) {
						model = getActiveModel();
					}

					if (model) {
						updateLivePreview(model);
					}
				});
			});

			window.elementor.channels.editor.on('change', function (controlView) {
				if (!controlView || !controlView.model) return;
				var name = controlView.model.get('name');
				if (responsiveFields.indexOf(name) !== -1) {
					var model = getActiveModel();
					if (model) {
						updateLivePreview(model);
					}
				}
			});

			window.elementor.channels.editor.on('section:activated', function (sectionName) {
				if (sectionName === 'ab_section_custom_css') {
					setTimeout(runScanner, 50);
					setTimeout(runScanner, 300);
				}
			});
		}

		// 3. Scan & display classes when opening panel for any element type
		var elementTypes = ['widget', 'section', 'column', 'container'];
		elementTypes.forEach(function (type) {
			window.elementor.hooks.addAction('panel/open_editor/' + type, function (panel, model) {
				if (model) {
					activeModel = model;
					updateLivePreview(model);
					setTimeout(function () {
						scanAndDisplayClasses(model);
					}, 150);
					setTimeout(function () {
						scanAndDisplayClasses(model);
					}, 500);
				}
			});
		});

		// 4. Click listener when navigating to the Advanced tab or clicking Custom CSS section
		$(document).on('click', '.elementor-tab-control-advanced, [data-tab="advanced"], .elementor-control-section_ab_section_custom_css, .elementor-panel-heading', function () {
			setTimeout(runScanner, 100);
			setTimeout(runScanner, 400);
		});

		// 5. MutationObserver to automatically detect when the classes box enters DOM and populate immediately
		try {
			var observer = new MutationObserver(function () {
				var $box = $('.ab-custom-css-classes-box');
				if ($box.length) {
					var $loading = $box.find('.ab-classes-loading');
					if ($loading.length) {
						runScanner();
					}
				}
			});
			observer.observe(document.body, { childList: true, subtree: true });
		} catch (e) {}

		// 6. View Switcher Handler (Tree View vs All Badges)
		$(document).on('click', '.ab-view-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var view = $btn.data('view');
			if (!view) return;

			currentViewMode = view;
			$('.ab-view-btn').removeClass('active');
			$btn.addClass('active');

			var $box = $('.ab-custom-css-classes-box');
			var $content = $box.find('.ab-classes-content');

			if (view === 'tree') {
				var treeHtml = $box.data('tree-html');
				if (treeHtml) {
					$content.html(treeHtml);
				} else {
					runScanner();
				}
			} else {
				var badgesHtml = $box.data('badges-html');
				if (badgesHtml) {
					$content.html(badgesHtml);
				} else {
					runScanner();
				}
			}
		});

		// 7. Tree Toggle (Collapse / Expand child branches)
		$(document).on('click', '.ab-tree-toggle', function (e) {
			e.stopPropagation();
			var $toggle = $(this);
			var $node = $toggle.closest('.ab-tree-node');
			var $children = $node.children('.ab-tree-children');

			if ($children.is(':visible')) {
				$children.slideUp(150);
				$toggle.text('▸');
			} else {
				$children.slideDown(150);
				$toggle.text('▾');
			}
		});

		// 8. Click handler for tree insert button, clickable pills, and class badges
		$(document).on('click', '.ab-tree-insert-btn, .ab-class-pill, .ab-clickable-pill', function (e) {
			e.preventDefault();
			e.stopPropagation();

			var $btn = $(this);
			var selector = $btn.data('selector');
			if (!selector) return;

			var inserted = insertSelectorIntoActiveEditor(selector);

			if (inserted) {
				$btn.addClass('ab-pill-inserted');
				var $action = $btn.find('.ab-pill-action');
				var origActionHtml = $action.length ? $action.html() : '';
				if ($action.length) {
					$action.html('<i class="eicon-check"></i>');
				}

				setTimeout(function () {
					$btn.removeClass('ab-pill-inserted');
					if ($action.length) {
						$action.html(origActionHtml);
					}
				}, 1000);
			}
		});

		// 9. Rescan button click handler
		$(document).on('click', '.ab-classes-refresh-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			$btn.find('i').addClass('eicon-animation-spin');

			runScanner();

			setTimeout(function () {
				$btn.find('i').removeClass('eicon-animation-spin');
			}, 400);
		});

		// 10. Fast input/keyup listener on all Custom CSS textareas
		$(document).on('input keyup change', '.elementor-control-ab_custom_css textarea, .elementor-control-ab_custom_css_tablet textarea, .elementor-control-ab_custom_css_mobile textarea', function () {
			var model = getActiveModel();
			if (model) {
				updateLivePreview(model);
			}
		});

		// 11. Refresh CodeMirror instance on tab switch so line numbers & editor render instantly
		$(document).on('click', '.elementor-tab-control-ab_custom_css_desktop_tab, .elementor-tab-control-ab_custom_css_tablet_tab, .elementor-tab-control-ab_custom_css_mobile_tab, [data-tab="ab_custom_css_desktop_tab"], [data-tab="ab_custom_css_tablet_tab"], [data-tab="ab_custom_css_mobile_tab"]', function () {
			setTimeout(function () {
				var target = getActiveEditorTarget();
				if (target && target.cm && target.cm.refresh) {
					target.cm.refresh();
				}
			}, 60);
		});
	}

	$(window).on('elementor:init', init);

	if (window.elementor && window.elementor.hooks) {
		init();
	}
})(jQuery);
