/**
 * Script for handling PDF generation/download action.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

jQuery(
	function( $ ) {
		var intervalHandler      = null;
		var intervalHandlerDelay = 5000;

		function stopListening() {
			if (intervalHandler) {
				clearInterval( intervalHandler );
				intervalHandler = null;
			}
		};

		function checkPdf(btn, onSuccess, onError) {
			$.ajax(
				{
					url: btn.data( 'pdfCheckUrl' ),
					method: 'GET',
					dataType: 'json',
					error: onError,
					success: onSuccess
				}
			);
		};

		function getPdf(btn) {
			window.location.href = btn.data( 'pdfStatusUrl' );
		};

		$( document.body )
			.on(
				'click',
				'.mnumidesigner-download-project-pdf',
				function() {
					var btn = $( this );
					btn.text( 'Download requested' );

					stopListening();

					intervalHandler = setInterval(
						function() {
							checkPdf(
								btn,
								function(jsonResponse) {
									var result     = jsonResponse.result;
									var queue      = result.queueCount;
									var status     = result.status;
									var statusCode = status.code;

									if (statusCode == 'pending') {
										btn.text( 'Waiting in queue: ' + queue );
										return;
									} else if (statusCode == 'ready') {
										btn.text( btn.attr( 'title' ) );
										getPdf( btn )
									} else if (statusCode == 'error') {
									}

									stopListening();
								},
								function(response, ajaxOptions, thrownError) {
									stopListening();
								}
							);
						},
						intervalHandlerDelay
					);
				}
			);
	}
);
