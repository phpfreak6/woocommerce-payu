<?php
/**
 * The Template for displaying the credit card form on the checkout page
 *
 * @author      new
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
// Add notification to the user that this will fail miserably if they attempt it.
echo '<noscript>';
    printf( __( '%s payment does not work without Javascript. Please enable Javascript or use a different payment method.', 'payu-for-woocommerce' ), $this->method_title );
echo '</noscript>';

// Payment method description
if ( $this->method_description ) {
	
    echo '<p class="payu-description">' .  $this->method_description . '</p>';
	
}

// Get user database object

$payu_customer_info = get_user_meta( get_current_user_id(), $this->db_location , true );

if ( is_user_logged_in() && ! empty( $payu_customer_info['cards'] ) && $this->saved_cards === 'yes' ) :
	// Add option to use a saved card
    $credit_card = $payu_customer_info['cards'];
      
	  if ($payu_customer_info['cards']) {
            $checked = ' checked';
        }else{
			$checked = '';
		}
		
    ?>

        <input type="radio" id="payu_card" name="payu_card" value="<?php echo $credit_card['card_id'] ?>"<?php echo $checked; ?>>
        <label for="payu_card_<?php echo $i; ?>"><?php printf( __( 'Card No with %s  Ending on (%s/%s)', 'payu-for-woocommerce' ), $credit_card['card_no'], $credit_card['exp_month'], $credit_card['exp_year'] ); ?></label><br>
    <input type="radio" id="new_card" name="payu_card" value="new">
    <label for="new_card"><?php _e( 'Use a new credit card', 'payu-for-woocommerce' ); ?></label>

<?php endif; ?>
