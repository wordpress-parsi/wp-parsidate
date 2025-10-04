const wppMelliPG_data = window.wc.wcSettings.getSetting( 'melli_data', {} );
const wppMelliPG_label = window.wp.htmlEntities.decodeEntities( wppMelliPG_data.title )
  || window.wp.i18n.__( 'Melli Bank', 'wp-parsidate' );
const wppMelliPG_content = ( wppMelliPG_data ) => {
  return window.wp.htmlEntities.decodeEntities( wppMelliPG_data.description );
};
const wppMelliPG = {
  name: 'melli',
  label: wppMelliPG_label,
  content: Object( window.wp.element.createElement )( wppMelliPG_content, null ),
  edit: Object( window.wp.element.createElement )( wppMelliPG_content, null ),
  canMakePayment: () => true,
  placeOrderButtonLabel: window.wp.i18n.__( 'Continue', 'wp-parsidate' ),
  ariaLabel: wppMelliPG_label,
  supports: {
    features: wppMelliPG_data.supports,
  },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( wppMelliPG );