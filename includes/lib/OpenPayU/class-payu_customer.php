<?php
/**
 * Customer related modifications and templates
 *
 * @class       Payu_Customer
 * @author      Stephen Zuniga
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Payu_Customer { public function __construct() {

        // Hooks
        add_action( 'woocommerce_after_my_account', array( $this, 'account_saved_cards' ) );
        add_action( 'show_user_profile', array( $this, 'add_customer_profile' ), 20 );
        add_action( 'edit_user_profile', array( $this, 'add_customer_profile' ), 20 );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Complete necessary actions and display
     * notifications at the top of the page
     *
     * @access      public
     * @return      void
     */
    public function admin_notices() {
        
		global $pagenow, $profileuser;

        // If we're on the profile page
        if ( $pagenow === 'profile.php' ) {

            if ( ! empty( $_GET['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 's4wc_action' ) ) {

                // Delete test data
                if ( $_GET['action'] === 'delete_payu_data' ) {

                    // Delete test data if the action has been confirmed
                    if ( ! empty( $_GET['confirm'] ) && $_GET['confirm'] === 'yes' ) {

                        $result = delete_user_meta( $profileuser->ID, '_payu_data' );

                        if ( $result ) {
                            ?>
                            <div class="updated">
                                <p><?php _e( 'Payu customer data successfully deleted.', 'payu-for-woocommerce' ); ?></p>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="error">
                                <p><?php _e( 'Unable to delete Payu customer data', 'payu-for-woocommerce' ); ?></p>
                            </div>
                            <?php
                        }
                    }

                    // Ask for confimation before we actually delete data
                    else {
                        ?>
                        <div class="error">
                            <p><?php _e( 'Are you sure you want to delete customer data? This action cannot be undone.', 'payu-for-woocommerce' ); ?></p>
                            <p>
                                <a href="<?php echo wp_nonce_url( admin_url( 'profile.php?action=delete_payu_data&confirm=yes' ), 'payu_action' ); ?>" class="button"><?php _e( 'Delete', 'payu-for-woocommerce' ); ?></a>
                                <a href="<?php echo admin_url( 'profile.php' ); ?>" class="button"><?php _e( 'Cancel', 'payu-for-woocommerce' ); ?></a>
                            </p>
                        </div>
                        <?php
                    }
                }
            }
        }
    }

    /**
     * Add to the customer profile
     *
     * @access      public
     * @param       WP_User $user
     * @return      void
     */
    public function add_customer_profile( $user ) {

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        ?>
        <table class="form-table">
            <tr>
                <th>Delete Payu Data</th>
                <td>
                    <p>
                        <a href="<?php echo wp_nonce_url( admin_url( 'profile.php?action=delete_payu_data' ), 'payu_action' ); ?>" class="button"><?php _e( 'Delete Payu Data', 'payu-for-woocommerce' ); ?></a>
                        <span class="description"><?php _e( '<strong class="red">Warning:</strong> This will delete Payu data for this customer, make sure to back up your database.', 'payu-for-woocommerce' ); ?></span>
                    </p>
                </td>
            </tr>
		</table>
        <?php
    }

    /**
     * Gives front-end view of saved cards in the account page
     *
     * @access      public
     * @return      void
     */
    public function account_saved_cards() {
      
		if ( $payu->settings['saved_cards'] === 'yes' ) {

            // If user requested to delete a card, delete it
            if ( isset( $_POST['delete_card'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'payu_delete_card' ) ) {
                payu_API::delete_card( get_current_user_id(), intval( $_POST['delete_card'] ) );
            }

            $user_meta    = get_user_meta( get_current_user_id(), $payu->settings['stripe_db_location'], true );
            $credit_cards = isset( $user_meta['cards'] ) ? $user_meta['cards'] : false;

            $args = array(
                'user_meta'    => $user_meta,
                'credit_cards' => $credit_cards,
            );

            include ('saved-cards.php');
        }
    }
}

new Payu_Customer();
