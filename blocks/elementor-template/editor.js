( function( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var InspectorControls = ( wp.blockEditor && wp.blockEditor.InspectorControls ) || ( wp.editor && wp.editor.InspectorControls );
	var useBlockProps = ( wp.blockEditor && wp.blockEditor.useBlockProps ) || function( p ) { return p || {}; };
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ComboboxControl = wp.components.ComboboxControl;
	var Button = wp.components.Button;
	var Notice = wp.components.Notice;
	var TextControl = wp.components.TextControl;
	var __ = wp.i18n.__;

	// Global data localized from PHP
	var blockData = window.abElementorBlockData || {
		isElementorActive: false,
		templates: [],
		adminTemplatesUrl: 'edit.php?post_type=elementor_library'
	};

	// SVG layout icon for AB Elementor Template
	var blockIcon = el(
		'svg',
		{ width: 20, height: 20, viewBox: '0 0 24 24', fill: 'currentColor' },
		el( 'path', {
			d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 4.5H5V5h14v2.5zm0 6H13V9.5h6v4zm-8-4v9.5H5V9.5h6zm8 5.5v4h-6v-4h6z'
		} )
	);

	registerBlockType( 'ab-addon/elementor-template', {
		apiVersion: 3,
		title: __( 'AB Elementor Template', 'ab-addon' ),
		description: __( 'Insert a saved Elementor template into Gutenberg content.', 'ab-addon' ),
		category: 'ab-addon',
		icon: blockIcon,
		keywords: [
			__( 'elementor', 'ab-addon' ),
			__( 'template', 'ab-addon' ),
			__( 'ab addon', 'ab-addon' ),
			__( 'container', 'ab-addon' ),
			__( 'section', 'ab-addon' )
		],
		attributes: {
			templateId: {
				type: 'number',
				default: 0
			}
		},
		supports: {
			html: false,
			customClassName: true,
			align: [ 'wide', 'full' ]
		},

		edit: function( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var templateId = parseInt( attributes.templateId, 10 ) || 0;

			var isElementorActive = Boolean( blockData.isElementorActive );
			var initialTemplates = blockData.templates || [];

			var state = useState( initialTemplates );
			var templates = state[0];
			var setTemplates = state[1];

			var isEditingState = useState( false );
			var isChanging = isEditingState[0];
			var setIsChanging = isEditingState[1];

			var searchState = useState( '' );
			var searchTerm = searchState[0];
			var setSearchTerm = searchState[1];

			// Find current selected template object
			var selectedTemplate = null;
			for ( var i = 0; i < templates.length; i++ ) {
				if ( templates[i].id === templateId ) {
					selectedTemplate = templates[i];
					break;
				}
			}

			// Filter templates by search term
			var filteredTemplates = templates;
			if ( searchTerm.trim() ) {
				var term = searchTerm.toLowerCase();
				filteredTemplates = templates.filter( function( t ) {
					return (
						( t.title && t.title.toLowerCase().indexOf( term ) !== -1 ) ||
						( t.type && t.type.toLowerCase().indexOf( term ) !== -1 ) ||
						( String( t.id ).indexOf( term ) !== -1 )
					);
				} );
			}

			// Prepare options for select controls
			var selectOptions = [
				{ value: '0', label: __( '— Select an Elementor template —', 'ab-addon' ) }
			];
			templates.forEach( function( t ) {
				var label = t.title ? t.title : __( '(Untitled Template)', 'ab-addon' );
				if ( t.type ) {
					label += ' (' + t.type + ')';
				}
				selectOptions.push( {
					value: String( t.id ),
					label: label
				} );
			} );

			// Determine badge class according to type
			var getBadgeClass = function( type ) {
				if ( ! type ) return 'ab-elementor-type-badge';
				var lower = type.toLowerCase();
				if ( lower.indexOf( 'container' ) !== -1 ) return 'ab-elementor-type-badge badge-container';
				if ( lower.indexOf( 'section' ) !== -1 ) return 'ab-elementor-type-badge badge-section';
				if ( lower.indexOf( 'page' ) !== -1 ) return 'ab-elementor-type-badge badge-page';
				if ( lower.indexOf( 'div' ) !== -1 ) return 'ab-elementor-type-badge badge-div';
				return 'ab-elementor-type-badge';
			};

			var blockProps = useBlockProps( {
				className: 'ab-elementor-block-wrapper'
			} );

			// Inspector sidebar controls
			var sidebarControls = el(
				InspectorControls,
				{ key: 'ab-elementor-inspector' },
				el(
					PanelBody,
					{ title: __( 'Elementor Template Settings', 'ab-addon' ), initialOpen: true },
					! isElementorActive && el(
						Notice,
						{ status: 'warning', isDismissible: false },
						__( 'Elementor is required to use this block.', 'ab-addon' )
					),
					isElementorActive && el(
						SelectControl,
						{
							label: __( 'Select Template', 'ab-addon' ),
							value: String( templateId ),
							options: selectOptions,
							onChange: function( newId ) {
								setAttributes( { templateId: parseInt( newId, 10 ) || 0 } );
								setIsChanging( false );
							},
							help: __( 'Choose which saved Elementor template to render here.', 'ab-addon' )
						}
					),
					selectedTemplate && el(
						'div',
						{ style: { marginTop: '12px', fontSize: '12px', color: '#646970' } },
						el( 'p', { style: { margin: '4px 0' } },
							el( 'strong', null, __( 'Template ID: ', 'ab-addon' ) ),
							String( selectedTemplate.id )
						),
						selectedTemplate.type && el( 'p', { style: { margin: '4px 0' } },
							el( 'strong', null, __( 'Type: ', 'ab-addon' ) ),
							selectedTemplate.type
						),
						selectedTemplate.editUrl && el(
							Button,
							{
								variant: 'secondary',
								href: selectedTemplate.editUrl,
								target: '_blank',
								style: { marginTop: '10px', width: '100%', justifyContent: 'center' }
							},
							__( 'Edit with Elementor', 'ab-addon' )
						)
					)
				)
			);

			// Render condition: Elementor is not active
			if ( ! isElementorActive ) {
				return el(
					'div',
					blockProps,
					sidebarControls,
					el(
						'div',
						{ className: 'ab-elementor-block-placeholder' },
						el(
							'div',
							{ className: 'ab-elementor-block-header' },
							el( 'span', { className: 'ab-header-icon' }, blockIcon ),
							el( 'span', { className: 'ab-header-title' }, __( 'AB Elementor Template', 'ab-addon' ) )
						),
						el(
							Notice,
							{ status: 'warning', isDismissible: false, className: 'ab-elementor-notice-warning' },
							__( 'Elementor is required to use the AB Elementor Template block.', 'ab-addon' )
						)
					)
				);
			}

			// Render condition: No templates found in WordPress
			if ( templates.length === 0 ) {
				return el(
					'div',
					blockProps,
					sidebarControls,
					el(
						'div',
						{ className: 'ab-elementor-block-placeholder' },
						el(
							'div',
							{ className: 'ab-elementor-block-header' },
							el( 'span', { className: 'ab-header-icon' }, blockIcon ),
							el( 'span', { className: 'ab-header-title' }, __( 'AB Elementor Template', 'ab-addon' ) )
						),
						el(
							Notice,
							{ status: 'info', isDismissible: false, className: 'ab-elementor-notice-warning' },
							__( 'No saved Elementor templates were found.', 'ab-addon' )
						),
						el(
							Button,
							{
								variant: 'primary',
								href: blockData.adminTemplatesUrl || 'edit.php?post_type=elementor_library',
								target: '_blank',
								style: { marginTop: '8px' }
							},
							__( 'Create Elementor Template', 'ab-addon' )
						)
					)
				);
			}

			// Render condition: Template ID was set, but not found in existing templates (deleted/trashed)
			if ( templateId > 0 && ! selectedTemplate && ! isChanging ) {
				return el(
					'div',
					blockProps,
					sidebarControls,
					el(
						'div',
						{ className: 'ab-elementor-block-placeholder' },
						el(
							'div',
							{ className: 'ab-elementor-block-header' },
							el( 'span', { className: 'ab-header-icon' }, blockIcon ),
							el( 'span', { className: 'ab-header-title' }, __( 'AB Elementor Template', 'ab-addon' ) )
						),
						el(
							Notice,
							{ status: 'warning', isDismissible: false, className: 'ab-elementor-notice-warning' },
							__( 'Selected Elementor template is no longer available.', 'ab-addon' )
						),
						el(
							Button,
							{
								variant: 'secondary',
								onClick: function() {
									setAttributes( { templateId: 0 } );
									setIsChanging( true );
								}
							},
							__( 'Select another template', 'ab-addon' )
						)
					)
				);
			}

			// Render condition: Selecting / Changing Template UI
			if ( templateId === 0 || isChanging ) {
				return el(
					'div',
					blockProps,
					sidebarControls,
					el(
						'div',
						{ className: 'ab-elementor-block-placeholder' },
						el(
							'div',
							{ className: 'ab-elementor-block-header' },
							el( 'span', { className: 'ab-header-icon' }, blockIcon ),
							el( 'span', { className: 'ab-header-title' }, __( 'AB Elementor Template', 'ab-addon' ) )
						),
						el(
							'p',
							{ className: 'ab-elementor-block-desc' },
							__( 'Select a saved Elementor template to insert into this content:', 'ab-addon' )
						),
						templates.length > 5 && el(
							'div',
							{ style: { marginBottom: '12px' } },
							el( TextControl, {
								placeholder: __( 'Search Elementor templates...', 'ab-addon' ),
								value: searchTerm,
								onChange: setSearchTerm
							} )
						),
						el(
							'div',
							{ className: 'ab-elementor-select-wrapper' },
							el( SelectControl, {
								label: __( 'Select Template:', 'ab-addon' ),
								value: String( templateId ),
								options: ( function() {
									var opts = [
										{ value: '0', label: __( '— Select an Elementor template —', 'ab-addon' ) }
									];
									filteredTemplates.forEach( function( t ) {
										var lbl = t.title ? t.title : __( '(Untitled Template)', 'ab-addon' );
										if ( t.type ) {
											lbl += ' (' + t.type + ')';
										}
										opts.push( { value: String( t.id ), label: lbl } );
									} );
									return opts;
								} )(),
								onChange: function( newId ) {
									var parsed = parseInt( newId, 10 ) || 0;
									if ( parsed > 0 ) {
										setAttributes( { templateId: parsed } );
										setIsChanging( false );
										setSearchTerm( '' );
									}
								}
							} )
						),
						isChanging && el(
							Button,
							{
								variant: 'tertiary',
								onClick: function() {
									setIsChanging( false );
								}
							},
							__( 'Cancel', 'ab-addon' )
						)
					)
				);
			}

			// Render condition: Template successfully selected!
			return el(
				'div',
				blockProps,
				sidebarControls,
				el(
					'div',
					{ className: 'ab-elementor-block-preview' },
					el(
						'div',
						{ className: 'ab-elementor-block-header' },
						el( 'span', { className: 'ab-header-icon' }, blockIcon ),
						el( 'span', { className: 'ab-header-title' }, __( 'AB Elementor Template', 'ab-addon' ) )
					),
					el(
						'div',
						{ className: 'ab-elementor-block-card' },
						el(
							'div',
							{ className: 'ab-elementor-template-meta' },
							el(
								'div',
								{ className: 'ab-elementor-template-title-wrap' },
								el( 'h4', { className: 'ab-elementor-template-name' }, selectedTemplate.title || __( '(Untitled)', 'ab-addon' ) ),
								selectedTemplate.type && el(
									'span',
									{ className: getBadgeClass( selectedTemplate.type ) },
									selectedTemplate.type
								)
							),
							el(
								'span',
								{ className: 'ab-elementor-template-id' },
								__( 'ID: ', 'ab-addon' ) + '#' + selectedTemplate.id
							)
						),
						el(
							'p',
							{ className: 'ab-elementor-card-notice' },
							el(
								'svg',
								{ width: 16, height: 16, viewBox: '0 0 20 20', fill: '#2271b1' },
								el( 'path', { d: 'M10 2a8 8 0 100 16 8 8 0 000-16zm.75 12h-1.5v-5h1.5v5zm0-6.5h-1.5v-1.5h1.5v1.5z' } )
							),
							__( 'This Elementor template will be displayed on the frontend.', 'ab-addon' )
						),
						el(
							'div',
							{ className: 'ab-elementor-card-actions' },
							el(
								Button,
								{
									variant: 'secondary',
									onClick: function() {
										setIsChanging( true );
									}
								},
								__( 'Change Template', 'ab-addon' )
							),
							selectedTemplate.editUrl && el(
								Button,
								{
									variant: 'primary',
									href: selectedTemplate.editUrl,
									target: '_blank',
									className: 'ab-elementor-btn-edit'
								},
								__( 'Edit with Elementor', 'ab-addon' )
							)
						)
					)
				)
			);
		},

		save: function() {
			// Dynamic block: returns null so content is rendered via PHP render_callback.
			return null;
		}
	} );

} )( window.wp );
