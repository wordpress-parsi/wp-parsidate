const wppParsianPG_data = window.wc.wcSettings.getSetting( 'parsian_data', {} );
const wppParsianPG_label = window.wp.htmlEntities.decodeEntities( wppParsianPG_data.title )
  || window.wp.i18n.__( 'Parsian Bank', 'wp-parsidate' );
const wppParsianPG_content = ( wppParsianPG_data ) => {
  return window.wp.htmlEntities.decodeEntities( wppParsianPG_data.description );
};
const wppParsianPG = {
  name: 'parsian',
  label: wppParsianPG_label,
  content: Object( window.wp.element.createElement )( wppParsianPG_content, null ),
  edit: Object( window.wp.element.createElement )( wppParsianPG_content, null ),
  canMakePayment: () => true,
  placeOrderButtonLabel: window.wp.i18n.__( 'Continue', 'wp-parsidate' ),
  ariaLabel: wppParsianPG_label,
  supports: {
    features: wppParsianPG_data.supports,
  },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( wppParsianPG );