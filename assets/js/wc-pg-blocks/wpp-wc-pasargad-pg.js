const wppPasargadPG_data = window.wc.wcSettings.getSetting( 'pasargad_data', {} );
const wppPasargadPG_label = window.wp.htmlEntities.decodeEntities( wppPasargadPG_data.title )
  || window.wp.i18n.__( 'Pasargad Bank', 'wp-parsidate' );
const wppPasargadPG_content = ( wppPasargadPG_data ) => {
  return window.wp.htmlEntities.decodeEntities( wppPasargadPG_data.description );
};
const wppPasargadPG = {
  name: 'pasargad',
  label: wppPasargadPG_label,
  content: Object( window.wp.element.createElement )( wppPasargadPG_content, null ),
  edit: Object( window.wp.element.createElement )( wppPasargadPG_content, null ),
  canMakePayment: () => true,
  placeOrderButtonLabel: window.wp.i18n.__( 'Continue', 'wp-parsidate' ),
  ariaLabel: wppPasargadPG_label,
  supports: {
    features: wppPasargadPG_data.supports,
  },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( wppPasargadPG );