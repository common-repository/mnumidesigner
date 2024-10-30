/**
 * Admin settings scripts.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

( function( $ ) {
	$(
		function() {
			'use strict';

			$( '#mnumidesigner-register-demo' ).on(
				'click',
				function(e) {
					e.preventDefault();
					$( this ).addClass( 'loading' );

					wp.apiRequest(
						{
							endpoint: 'settings',
							namespace: MnumiDesigner.namespace,
							method: 'post',
							success: function(model, response) {
								$( '#mnumidesigner_api_key_id' ).val( model.id );
								$( '#mnumidesigner_api_key' ).val( model.key );
								$( '#mnumidesigner-register-demo' ).hide();
								$( '.mnumidesigner-no-api-credentials' ).hide();
							},
							error: function(model, response) {
								alert( response.responseJSON.message );
							}
						}
					);
				}
			);
		}
	);
})( jQuery );
