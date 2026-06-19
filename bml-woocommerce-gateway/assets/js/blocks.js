/**
 * BML Connect payment method registration for the WooCommerce Blocks checkout.
 *
 * Payment happens on BML's hosted page, so there are no on-site card fields;
 * we only render the title and description and let WooCommerce redirect on
 * order submission (handled server-side by process_payment()).
 */
( function () {
	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { getSetting } = window.wc.wcSettings;
	const { decodeEntities } = window.wp.htmlEntities;
	const { createElement } = window.wp.element;

	const settings = getSetting( 'bml_data', {} );
	const title = decodeEntities( settings.title || 'Card / BML Connect' );
	const description = decodeEntities( settings.description || '' );

	const Label = function () {
		return createElement( 'span', null, title );
	};

	const Content = function () {
		return createElement( 'div', null, description );
	};

	registerPaymentMethod( {
		name: 'bml',
		label: createElement( Label, null ),
		content: createElement( Content, null ),
		edit: createElement( Content, null ),
		canMakePayment: function () {
			return true;
		},
		ariaLabel: title,
		supports: {
			features: settings.supports || [ 'products' ],
		},
	} );
} )();
