<?php

require_once 'lib/openpayu.php';

class WC_Gateway_PayU extends WC_Payment_Gateway {
	
    function __construct() {
		
        $this->id = "payu";
        $this->db_location = "payu_data";
		$this->pluginVersion = '1.1.0';
        $this->has_fields = false;

        $this->method_title = __('PayU', 'payu');
        $this->method_description = __('Official PayU payment gateway for WooCommerce.', 'payu');

        $this->icon = apply_filters('woocommerce_payu_icon', 'https://static.payu.com/plugins/woocommerce_payu_logo.png');

        $this->supports = array(
			'default_credit_card_form',
			'subscriptions',
			'refunds',
			'subscription_cancellation',
			'subscription_reactivation',
			'products',
		);

        $this->init_settings();

        foreach ($this->settings as $setting_key => $value) {
			
            $this->$setting_key = $value;
			
        }
		
        $this->init_form_fields();

        // Saving hook
        add_action('woocommerce_update_options_payment_gateways_payu', array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_payu', array($this, 'gateway_ipn'));
		
        // Status change hook
        add_action('woocommerce_order_status_changed', array($this, 'change_status_action'), 10, 3);
		
		add_action( 'wp_enqueue_scripts', array( $this, 'load_payu_scripts' ) );
		
        add_action( 'woocommerce_order_status_processing_to_completed', array( $this, 'order_status_completed' ) );
		
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 3 );
		
		add_action( 'woocommerce_credit_card_form_end', array( $this, 'after_cc_form' ) );
		
		$this->init_OpenPayU();

        $this->notifyUrl = add_query_arg('wc-api', 'WC_Gateway_PayU', home_url('/'));
    }

    protected function init_OpenPayU()
    
	{	
        OpenPayU_Configuration::setEnvironment('secure');
		if($this->sandbox == "yes"){
	// sandbox details		
        OpenPayU_Configuration::setMerchantPosId($this->test_pos_id);
        OpenPayU_Configuration::setSignatureKey($this->test_md5);
		
		}else{
			
	// live details		
		OpenPayU_Configuration::setMerchantPosId($this->live_pos_id);
        OpenPayU_Configuration::setSignatureKey($this->live_md5);
		
		}
        OpenPayU_Configuration::setSender('Wordpress ver ' . get_bloginfo('version') . ' / WooCommerce ver ' . WOOCOMMERCE_VERSION . ' / Plugin ver ' . $this->pluginVersion);
    }

    public function admin_options() {
        ?>

        <h3><?php echo $this->method_title; ?></h3>
       
		<p><?php echo $this->method_description; ?></p>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>

        <?php
    }

    function init_form_fields() {
		
        $this->form_fields = include('form-fields.php');
		
    }
  /**
     * Output payment fields, optional additional fields and woocommerce cc form
     *
     * @access      public
     * @return      void
     */
	 
    public function payment_fields() {
		
        // Output the saved card data
        $this->payu_get_template( 'payment-fields.php' );
		
        // Output WooCommerce 2.1+ cc form
        $this->credit_card_form( array(
            'fields_have_names' => false,
        ) );
    }
	
/**
 * Add an option to save card details after the form
 *
 * @access      public
 * @param       string $gateway_id
 * @return      void
 */	
	public function after_cc_form( $gateway_id ) {
        
		// Ensure that we're only outputting this for the s4wc gateway
        if ( $gateway_id !== $this->id ) {
			
            return;
			
        }

        // This form field is optional, so we should respect that
        if ( $this->saved_cards !== 'yes' ) {
			
            return;
			
        }

        woocommerce_form_field( 'payu_save_card', array(
            'type'              => 'checkbox',
            'label'             => __( 'Save Card Details For Later', 'payu-for-woocommerce' ),
            'class'             => array( 'form-row-wide' ),
            'input_class'       => array( 'payu-save-card' ),
            'custom_attributes' => array(
            'autocomplete'  => 'off'
            ),
        ) );
    }	
/**
 * Wrapper of wc_get_template to relate directly to payu
 *
 * @param       string $template_name
 * @param       array $args
 * @return      string
 */	
 

	function payu_get_template( $template_name, $args = array() ) {
		
		include($template_name);
	
	}
	
	
	
    function process_payment($order_id, $retry = true, $force_customer = false ) {
		
		if ( WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) {
			
		$order = new WC_Order($order_id);
		
		$response = OpenPayU_Order::create($order);
			
			if ($response['STATUS'] == 'SUCCESS') {
				
					$this->process_response( $response, $order );
					
					$order->payment_complete();
					
					$this->order_status_completed( $order_id ) ;

					WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );
					
					$order -> add_order_note('PayU payment successful<br/>Unnique Id from PayU: '.$response['REFNO']);
					// Remove cart
					
					WC()->cart->empty_cart();
					
					// Return thank you page redirect
					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order )
					);

					
            } else {
				
                wc_add_notice(__('STATUS:' , 'payu') . $response['STATUS'], 'error');
                wc_add_notice(__('RETURN_CODE:' , 'payu') . $response['RETURN_CODE'], 'error');
                wc_add_notice(__('RETURN_MESSAGE:' , 'payu') . $response['RETURN_MESSAGE'], 'error');
				
                return false;
				
			}
			
		}else{		
		
        $order = new WC_Order($order_id);
		
		//WC()->cart->empty_cart();
		
		$response = OpenPayU_Order::create($order);
			
			if ($response['STATUS'] == 'SUCCESS') {
				
					$this->process_response( $response, $order );
					
					$order->payment_complete();
					
					$this->order_status_completed( $order_id ) ;
					
					$order -> add_order_note('PayU payment successful<br/>Unnique Id from PayU: '.$response['REFNO']);
					// Remove cart
					
					//WC()->cart->empty_cart();
					
					// Return thank you page redirect
					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order )
					);

					
            } else {
				
                wc_add_notice(__('STATUS:' , 'payu') . $response['STATUS'], 'error');
                wc_add_notice(__('RETURN_CODE:' , 'payu') . $response['RETURN_CODE'], 'error');
                wc_add_notice(__('RETURN_MESSAGE:' , 'payu') . $response['RETURN_MESSAGE'], 'error');
				
                return false;
				
			}
		}
	}

    function gateway_ipn() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $body = file_get_contents('php://input');
            $data = stripslashes(trim($body));
            $response = OpenPayU_Order::consumeNotification($data);
            $order_id = (int) preg_replace('/_.*$/', '', $response->getResponse()->order->extOrderId);
            $status = $response->getResponse()->order->status;
            $transaction_id = $response->getResponse()->order->orderId;
			

			$file = './ipn.txt';
			// Open the file to get existing content
			$current = file_get_contents($file);
			// Append a new person to the file
			foreach($_POST as $key => $val){
			$current .= $_POST;
			
			// Write the contents back to the file
			file_put_contents($file, $current);

			}
			
            $order = new WC_Order($order_id);

            if ($order->get_status() != 'completed') {
                switch ($status) {
                    case 'CANCELED':
                        $order->update_status('cancelled', __('Payment has been cancelled.', 'payu'));
                        break;

                    case 'REJECTED':
                        $order->update_status('failed', __('Payment has been rejected.', 'payu'));
                        break;

                    case 'COMPLETED':
                        $order->payment_complete($transaction_id);
                        break;

                    case 'WAITING_FOR_CONFIRMATION':
                        $order->update_status(
                            'on-hold',
                            __('Payment has been put on hold - merchant must approve this payment manually.', 'payu')
                        );
                        break;
                }
            }
            header("HTTP/1.1 200 OK");
        }
    }
	
	 public function load_payu_scripts() {
		
        wp_enqueue_script( 'payu', plugins_url( 'assets/js/payu.js', dirname( __FILE__ ) ), array('wc-credit-card-form' ), '1.30', true );
		
		$customer_cards = get_user_meta( get_current_user_id(), $this->db_location , true );
		$payu_info = array(
            'savedCardsEnabled' => $this->saved_cards === 'yes' ? true : false,
            'hasCard'           => ( $customer_cards && count( $customer_cards ) ) ? true : false
        );

        wp_localize_script( 'payu', 'payu_info', $payu_info );
		
    }
	
    /**
     * Validate credit card form fields
     *
     * @access      public
     * @return      void
     */
    public function validate_fields() {

        $form_fields = array(
            'card-number' => array(
                'name'       => __( 'Credit Card Number', 'payu-for-woocommerce' ),
                'error_type' => isset( $_POST['payu-card-number'] ) ? $_POST['payu-card-number'] : null,
            ),
            'card-expiry' => array(
                'name'       => __( 'Credit Card Expiration', 'payu-for-woocommerce' ),
                'error_type' => isset( $_POST['payu-card-expiry'] ) ? $_POST['payu-card-expiry'] : null,
            ),
            'card-cvc'    => array(
                'name'       => __( 'Credit Card CVC', 'payu-for-woocommerce' ),
                'error_type' => isset( $_POST['payu-card-cvc'] ) ? $_POST['payu-card-cvc'] : null,
            ),
        );
		//echo '<pre>' ; print_r($form_fields);echo '</pre>' ;
		foreach ( $form_fields as $form_field ) {
			
            if ( ! empty( $form_field['error_type'] && ( $form_field['error_type'] === 'invalid' || $form_field['error_type'] === 'undefined')  ))  {
				
                wc_add_notice( $this->get_form_error_message( $form_field['name'], $form_field['error_type'] ), 'error' );
           
		   }
        }
	}
    /**
     * Get error message for form validator given field name and type of error
     *
     * @access      protected
     * @param       string $field_name
     * @param       string $error_type
     * @return      string
     */
    protected function get_form_error_message( $field_name, $error_type = 'undefined' ) {

        if ( $error_type === 'invalid' ) {
            return sprintf( __( 'Please enter a valid %s.', 'payu-for-woocommerce' ), "<strong>$field_name</strong>" );
        } elseif(( $error_type === 'undefined' )) {
            return sprintf( __( '%s is a required field.', 'payu-for-woocommerce' ), "<strong>$field_name</strong>" );
            return sprintf( __( '%s is a required field.', 'payu-for-woocommerce' ), "<strong>$field_name</strong>" );
        }
    }
	
	public function process_refund($order_id, $amount = null, $reason = '') {
        
		$order = new WC_Order($order_id);
		
        if (empty($order)) {
			
            return false;
			
        }

        if(OpenPayU_Refund::create($order,$amount))
		{
			
		return true ;
		
		}else{
			
		return false;
		
		}
    }

    public function change_status_action($order_id, $old_status, $new_status) {
        if ($this->payu_feedback == 'yes' && isset($_REQUEST['_wpnonce'])) {
            $order = new WC_Order($order_id);
            $orderId = $order->get_transaction_id();

            if (empty($orderId)) {
				
                return false;
				
            }

            if ($old_status == 'on-hold' && ($new_status == 'processing' || $new_status == 'completed')) {
                $status_update = array(
                    "orderId" => $orderId,
                    "orderStatus" => 'COMPLETED'
                );

                OpenPayU_Order::statusUpdate($status_update);
            }

            if($new_status == 'cancelled') {
                OpenPayU_Order::cancel($orderId);
            }
        }

    }
	
	    /**
     * Process a scheduled payment
     *
     * @access      public
     * @param       float $amount_to_charge
     * @param       WC_Order $order
     * @param       int $product_id
     * @return      void
     */
    public function scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {
		
		// the message
		$msg = $amount_to_charge .'/'.$order .'/'. $product_id;

		// use wordwrap() if lines are longer than 70 characters
		$msg = wordwrap($msg,70);

		// send email
		mail("rajneesht@whizkraft.net","cron",$msg);
		
		$response = OpenPayU_Order::subscribe( $amount_to_charge,$order,$product_id);
		
		if ($response['STATUS'] == 'SUCCESS') {
			
			// use wordwrap() if lines are longer than 70 characters
			$msg = $response;

			// send email
			mail("rajneesht@whizkraft.net","cronsuccessful",$msg);
			
			$this->process_response( $response, $order );
					
			$order->payment_complete();
			
            WC_Subscriptions_Manager::process_subscription_payments_on_order($order, $product_id);
       
	    } else {
			
			// use wordwrap() if lines are longer than 70 characters
			$msg = $response;

			// send email
			mail("rajneesht@whizkraft.net","cronunsuccessful",$msg);
			
			$order->update_status( 'failed', sprintf( __( 'Payu Transaction Failed (%s)', 'woocommerce-gateway-payu' ), $response['RETURN_MESSAGE'] ) );
					
            WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
			
		}
   
   }
	
	/**
	 * Store extra meta data for an order from a Stripe Response.
	 */
	public function process_response( $response, $order ) {
		
		update_post_meta( $order->id, '_payu_charge_id', $response["TRANSID"] );
		
		update_post_meta( $order->id, '_payu_charge_captured', $response["SUCCESS"] ? 'yes' : 'no' );
		
		$order->payment_complete( $response->id );
		
		if($_POST["payu_save_card"]){
		
		$this->get_customer($order,$response);
		
		}
		
		return $response ;
	}
	
/**
 * Create a customer if the current user isn't already one
 * Retrieve a customer if one already exists
 * Add a card to a customer if necessary
 *
 * @access      protected
 * @return      array
 */	
 
	function get_customer($order,$response) {
       
		$output = array();
		
        $customer_info = get_user_meta( $order->user_id, '' , true );
		
		if ( $customer_info ) {
			
		// Add new customer details to database
		$result = Payu_DB::update_customer( $order->user_id, array(
				'customer_id'  => $response["CLIENTID"],
				'card'         => array(
					'card_id'  => $response["REFNO"],
					'card_no'  => $response["PAN"],
					'exp_month' => $response["EXPMONTH"],
					'exp_year'  => $response["EXPYEAR"],
					'card_CVV'  => $_POST["payu-card-cvc"],
					'token_hash' => $response["TOKEN_HASH"],
				),
				'default_card' => $response["REFNO"],
			),$this->db_location );

        } 
		
    }
 /**
     * Process the captured payment when changing order status to completed
     *
     * @access      public
     * @param       int $order_id
     * @return      mixed
     */
    public function order_status_completed( $order_id = null ) {

        if ( ! $order_id ) {
            $order_id = $_POST['order_id'];
        }

        // `_payu_capture` added in 1.35, let `capture` last for a few more updates before removing
        if ( get_post_meta( $order_id, '_payu_capture', true ) || get_post_meta( $order_id, 'capture', true ) ) {

            
        }
    }
}
?>