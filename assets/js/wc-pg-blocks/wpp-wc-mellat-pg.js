const wppMellatPG_data = window.wc.wcSettings.getSetting( 'mellat_data', {} );
const wppMellatPG_label = window.wp.htmlEntities.decodeEntities( wppMellatPG_data.title )
  || window.wp.i18n.__( 'Mellat Bank', 'wp-parsidate' );
const wppMellatPG_content = ( wppMellatPG_data ) => {
  return window.wp.htmlEntities.decodeEntities( wppMellatPG_data.description );
};
const wppMellatPG = {
  name: 'mellat',
  label: wppMellatPG_label,
  content: Object( window.wp.element.createElement )( wppMellatPG_content, null ),
  edit: Object( window.wp.element.createElement )( wppMellatPG_content, null ),
  canMakePayment: () => true,
  placeOrderButtonLabel: window.wp.i18n.__( 'Continue', 'wp-parsidate' ),
  ariaLabel: wppMellatPG_label,
  supports: {
    features: wppMellatPG_data.supports,
  },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( wppMellatPG );