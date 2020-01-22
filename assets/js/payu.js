/* global payu, payu_info */

// Set API key

jQuery( function ( $ ) {
    var $form = $( 'form.checkout, form#order_review' ),
        savedFieldValues = {},
        $ccForm, $ccNumber, $ccExpiry, $ccCvc;
	
	
    function initCCForm () {
        $ccForm   = $( '#wc-payu-cc-form' );
        $ccNumber = $ccForm.find( '#payu-card-number' );
        $ccExpiry = $ccForm.find( '#payu-card-expiry' );
        $ccCvc    = $ccForm.find( '#payu-card-cvc' );
	
		if ( payu_info.hasCard && payu_info.savedCardsEnabled ) {
            $ccForm.hide();
        }

        // Toggle new card form
        $form.on( 'change', 'input[name="payu_card"]', function () {

            if ( $( 'input[name="payu_card"]:checked' ).val() === 'new' ) {
                $ccForm.slideDown( 200 );
            } else {
                $ccForm.slideUp( 200 );
            }
        });
		
        // Add in lost data
        if ( savedFieldValues.number ) {
            $ccNumber.val( savedFieldValues.number.val ).attr( 'class', savedFieldValues.number.classes );
        }

        if ( savedFieldValues.expiry ) {
            $ccExpiry.val( savedFieldValues.expiry.val );
        }

        if ( savedFieldValues.cvc ) {
            $ccCvc.val( savedFieldValues.cvc.val );
        }
    }
	
	function payuFormHandler () {
        if ( $( '#payment_method_payu' ).is( ':checked' ) && ( ! $( 'input[name="payu_card"]' ).length || $( 'input[name="payu_card"]:checked' ).val() === 'new' ) ) {

            if ( ! $( 'input.payu_token' ).length ) {
                var cardExpiry = $ccExpiry.payment( 'cardExpiryVal' );

                var payuData = {
                    number          : $ccNumber.val() || '',
                    cvc             : $ccCvc.val() || '',
                    exp_month       : cardExpiry.month || '',
                    exp_year        : cardExpiry.year || '',
                };

                // Validate form fields, create token if form is valid
                if ( payuFormValidator( payuData ) ) {
                    
					return true;
                
				}
            }
        }

        return true;
    }

    function payuFormValidator ( payuData ) {

        // Validate form fields
        var errors = fieldValidator( payuData );

        // If there are errors, display them using wc_add_notice on the backend
        if ( errors.length ) {

            $( '.payu_token, .form_errors' ).remove();

            for ( var i = 0, len = errors.length; i < len; i++ ) {
                var field = errors[i].field,
                    type  = errors[i].type;

                $form.append( '<input type="hidden" class="form_errors" name="' + field + '" value="' + type + '">' );
            }

            $form.append( '<input type="hidden" class="form_errors" name="form_errors" value="1">' );

            return false;
        }

        // Create the token if we don't have any errors
        else {
            // Clear out notices
            $form.find( '.woocommerce-error' ).remove();

            return true;
        }
    }

    function fieldValidator ( payuData ) {
		
        var errors = [];

        // Card number validation
        if ( ! payuData.number ) {
            errors.push({
                'field' : 'payu-card-number',
                'type'  : 'undefined'
            });
        } else if ( ! $.payment.validateCardNumber( payuData.number ) ) {
            errors.push({
                'field' : 'payu-card-number',
                'type'  : 'invalid'
            });
        }

        // Card expiration validation
        if ( ! payuData.exp_month || ! payuData.exp_year ) {
            errors.push({
                'field' : 'payu-card-expiry',
                'type'  : 'undefined'
            });
        } else if ( ! $.payment.validateCardExpiry( payuData.exp_month, payuData.exp_year ) ) {
            errors.push({
                'field' : 'payu-card-expiry',
                'type'  : 'invalid'
            });
        }

        // Card CVC validation
        if ( ! payuData.cvc ) {
            errors.push({
                'field' : 'payu-card-cvc',
                'type'  : 'undefined'
            });
        } else if ( ! $.payment.validateCardCVC( payuData.cvc, $.payment.cardType( payuData.number ) ) ) {
            errors.push({
                'field' : 'payu-card-cvc',
                'type'  : 'invalid'
            });
        }

        // Send the errors back
        return errors;
    }

    // Make sure the credit card form exists before we try working with it
    $( 'body' ).on( 'updated_checkout.payu', initCCForm ).trigger( 'updated_checkout.payu' );

    // Checkout Form
    $( 'form.checkout' ).on( 'checkout_place_order', payuFormHandler );

    // Pay Page Form
    $( 'form#order_review' ).on( 'submit', payuFormHandler );

    // Both Forms
    $form.on( 'keyup change', '#payu-card-number, #payu-card-expiry, #payu-card-cvc, input[name="payu_card"], input[name="payment_method"]', function () {

        // Save credit card details in case the address changes (or something else)
        savedFieldValues.number = {
            'val'     : $ccNumber.val(),
            'classes' : $ccNumber.attr( 'class' )
        };
        savedFieldValues.expiry = {
            'val' : $ccExpiry.val()
        };
        savedFieldValues.cvc = {
            'val' : $ccCvc.val()
        };

        $( '.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message, .payu_token, .form_errors' ).remove();
    });
});