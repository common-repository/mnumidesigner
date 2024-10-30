/**
 * Frontend scripts.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

( function( $ ) {
	$(
		function() {
			'use strict';

			var $body                     = $( 'body' );
			$.fn.mnumidesigner_variations = function() {
				var $form = $( '.variations_form' ),
				$button   = $form.find( '.single_add_to_cart_button' );

				$form.on(
					'found_variation',
					function( event, variation) {
						if (mnumidesigner_frontend.variations[variation.variation_id]) {
							$button.text( mnumidesigner_frontend.variations[variation.variation_id] );
						} else {
							$button.text( mnumidesigner_frontend.default_cart_label );
						}
					}
				);

			};

			if ( $body.hasClass( 'single-product' ) ) {
				$.fn.mnumidesigner_variations();
			}
		}
	);
	$( document.body ).on(
		'added_to_cart',
		function( e, fragments, cart_hash, $button ) {
			if (fragments && fragments.mnumidesigner) {
				window.location.href = fragments.mnumidesigner;
			}
		}
	);
})( jQuery );
