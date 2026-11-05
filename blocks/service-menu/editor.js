( function ( blocks, blockEditor, components, element, labels ) {
	'use strict';
	var el = element.createElement;
	blocks.registerBlockType( 'digicyfr/service-menu', {
		title: labels.title,
		description: labels.description,
		icon: 'list-view',
		category: 'widgets',
		attributes: { category: { type: 'string', default: '' } },
		edit: function ( props ) {
			return el( element.Fragment, null,
				el( blockEditor.InspectorControls, null,
					el( components.PanelBody, { title: labels.settings, initialOpen: true },
						el( components.TextControl, {
							label: labels.category,
							help: labels.categoryHelp,
							value: props.attributes.category || '',
							onChange: function ( value ) { props.setAttributes( { category: value } ); }
						} )
					)
				),
				el( components.Placeholder, { icon: 'list-view', label: labels.title },
					el( 'p', null, labels.preview )
				)
			);
		},
		save: function () { return null; }
	} );
} )( wp.blocks, wp.blockEditor, wp.components, wp.element, digicyfrServiceMenuLabels );
