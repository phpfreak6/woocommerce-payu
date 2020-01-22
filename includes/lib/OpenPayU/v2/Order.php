<?php

/**
 * OpenPayU Standard Library
 *
 * @copyright  Copyright (c) 2011-2015 PayU
 * @license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 * http://www.payu.com
 * http://developers.payu.com
 */

/**
 * Class OpenPayU_Order
 */
class OpenPayU_Order extends OpenPayU
{
    const ORDER_SERVICE = '';
    const SUCCESS = 'SUCCESS';

    /**
     * @var array Default form parameters
     */
    protected static $defaultFormParams = array(
        'formClass' => '',
        'formId' => 'payu-payment-form',
        'submitClass' => '',
        'submitId' => '',
        'submitContent' => '',
        'submitTarget' => '_blank'
    );

    /**
     * Creates new Order
     * - Sends to PayU OrderCreateRequest
     *
     * @access public
     * @param array $order A array containing full Order
     * @return object $result Response array with OrderCreateResponse
     * @throws OpenPayU_Exception
     */
    public static function create($order)
    {	
        $pathUrl     = OpenPayU_Configuration::getServiceUrl() . self::ORDER_SERVICE;
		
		$secretKey	 = OpenPayU_Configuration::getSignatureKey();
		
		$merchant    = OpenPayU_Configuration::getMerchantPosId();
		
		$backref     = trim(add_query_arg('order',$order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id')))));
		
		$notifyUrl     = trim(add_query_arg("wc-api", "WC_Gateway_PayU", home_url("/")));
		
		$orderData["MERCHANT"] = trim($merchant);
        
		$orderData["ORDER_REF"] = trim(rand(1000,9999));
       
		//$orderData["ORDER_DATE"] = date('Y-m-d H:i:s', strtotime($order->order_date) - 60 * 60 * 3);
		$orderData["ORDER_DATE"] = gmdate('Y-m-d H:i:s');
		
		//$orderData["ORDER_DATE"] = "2016-06-28 06:40:00";
      
		// Product Details
		 $i = 0;
		
		 $items = $order->get_items();	
		 
		 foreach ($items as $item) {
		    
			$product = new WC_Product($item['product_id']);
			
			$orderData["ORDER_PNAME[".$i."]"] = trim($item["name"]);
           
			$orderData["ORDER_PINFO[".$i."]"] = trim(get_post($item['product_id'])->post_content);
            
			$orderData["ORDER_PCODE[".$i."]"] = trim($product->get_sku());
			
			$orderData["ORDER_PRICE[".$i."]"] = trim($order->get_item_total($item, true));
			
            $orderData["ORDER_QTY[".$i."]"] = ($item["qty"]);
			
			$i++;
        }
		
		// End product details
		$orderData["PRICES_CURRENCY"] 	= trim(get_woocommerce_currency());
		
		$orderData["PAY_METHOD"] = "CCVISAMC";
		
		$orderData["SELECTED_INSTALLMENTS_NUMBER"] = trim("1");
		
		if(isset($_POST["payu_card"]) && $_POST["payu_card"] != "new"){
		
		$orderData["CC_NUMBER"] 		= "" ;
		$orderData["EXP_MONTH"]			= "" ;
		$orderData["EXP_YEAR"] 			= "" ;
		$orderData["CC_OWNER"] 			= "" ;
		
		$saved_card = get_user_meta( get_current_user_id(), 'payu_data' , true );
		
		$orderData["CC_TOKEN"] = $saved_card["cards"]["token_hash"];
		
		$orderData["CC_CVV"]   = $saved_card["cards"]["card_CVV"];
		
		}else{
			
		
		// card details:
		$orderData["CC_NUMBER"] 		= trim(str_replace(" ","",$_POST["payu-card-number"]));
		$expiry_date = explode('/',$_POST["payu-card-expiry"]);
		$orderData["EXP_MONTH"]			= trim($expiry_date[0]);
		$orderData["EXP_YEAR"] 			= trim($expiry_date[1]);
		if(strlen($orderData["EXP_YEAR"]) == 2){
			
		$orderData["EXP_YEAR"] = '20'. $orderData["EXP_YEAR"];
			
		}
		
        $orderData["CC_CVV"] 			= trim($_POST["payu-card-cvc"]);
        $orderData["CC_OWNER"] 			= trim($order->billing_first_name) . ' ' . trim($order->billing_last_name);
		$orderData["LU_ENABLE_TOKEN"] = 1 ;
		
			
		}
		//end card details
		
		$orderData["CLIENT_IP"] 		= trim($_SERVER["REMOTE_ADDR"]);
		
		$orderData["BACK_REF"] 			= $backref;
      
		// billing details
		$orderData["BILL_LNAME"] 		= trim($order->billing_last_name);
		$orderData["BILL_FNAME"]		= trim($order->billing_first_name);
		$orderData["BILL_EMAIL"] 		= trim($order->billing_email);
		$orderData["BILL_PHONE"] 		= trim($order->billing_phone);
		$orderData["BILL_COUNTRYCODE"] 	= trim($order->billing_country);
		
		// de|ivery details:
		
		$orderData["DELIVERY_FNAME"] 	= trim($order->shipping_first_name);
		$orderData["DELIVERY_LNAME"] 	= trim($order->shipping_last_name);
		$orderData["DELIVERY_PHONE"] 	= trim($order->billing_phone);
		$orderData["DELIVERY_ADDRESS"] 	= trim($order->shipping_address_1) ." ". trim($order->shipping_address_2);
		$orderData["DELIVERY_ZIPCODE"] 	= trim($order->shipping_postcode);
		$orderData["DELIVERY_CITY"] 	= trim($order->shipping_city);
		$orderData["DELIVERY_STATE"] 	= trim($order->shipping_state);
		$orderData["DELIVERY_COUNTRYCODE"] 	= trim($order->shipping_country);
		 
		$order	 = OpenPayU_Util::buildarParams($orderData,$secretKey);
		//echo '<pre>' ; print_r($orderData); die;
		if (empty($order)) {
			
            throw new OpenPayU_Exception('Empty message OrderCreateRequest');
			
        }
		
        $result = OpenPayU_Http::post($pathUrl, $order);
	
        return $result['response'];
    }
	
    /**
     * Subscribe Order
     * - Sends to PayU OrdersubscribeRequest
     *
     * @access public
     * @param array $order A array containing full Order
     * @return object $result Response array with OrderCreateResponse
     * @throws OpenPayU_Exception
     */
    public static function subscribe( $amount_to_charge,$order)
    {	
        $pathUrl     = OpenPayU_Configuration::getServiceUrl() . self::ORDER_SERVICE;
		
		$secretKey	 = OpenPayU_Configuration::getSignatureKey();
		
		$merchant    = OpenPayU_Configuration::getMerchantPosId();
		
		$backref     = trim(add_query_arg('order',$order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id')))));
		
		$notifyUrl     = trim(add_query_arg("wc-api", "WC_Gateway_PayU", home_url("/")));
		
		$orderData["MERCHANT"] = trim($merchant);
        
		$orderData["ORDER_REF"] = trim(rand(1000,9999));
       
		$orderData["ORDER_DATE"] = gmdate('Y-m-d H:i:s');
		
		// Product Details
		 $i = 0;
		
		 $items = $order->get_items();	
		 
		 foreach ($items as $item) {
		    
			$product = new WC_Product($item['product_id']);
			
			$orderData["ORDER_PNAME[".$i."]"] = trim($item["name"]);
           
			$orderData["ORDER_PINFO[".$i."]"] = trim(get_post($item['product_id'])->post_content);
            
			$orderData["ORDER_PCODE[".$i."]"] = trim($product->get_sku());
			
			$orderData["ORDER_PRICE[".$i."]"] = trim($order->get_item_total($item, true));
			
            $orderData["ORDER_QTY[".$i."]"] = ($item["qty"]);
			
			$i++;
        }
		
		// End product details
		$orderData["PRICES_CURRENCY"] 	= trim(get_woocommerce_currency());
		
		$orderData["PAY_METHOD"] = "CCVISAMC";
		
		$orderData["SELECTED_INSTALLMENTS_NUMBER"] = trim("1");
		
		$orderData["CC_NUMBER"] 		= "" ;
		$orderData["EXP_MONTH"]			= "" ;
		$orderData["EXP_YEAR"] 			= "" ;
		$orderData["CC_OWNER"] 			= "" ;
		
		$saved_card = get_user_meta( get_current_user_id(), 'payu_data' , true );
		
		$orderData["CC_TOKEN"] = $saved_card["cards"]["token_hash"];
		
		$orderData["CC_CVV"]   = $saved_card["cards"]["card_CVV"];
		
		//end card details
		
		$orderData["CLIENT_IP"] 		= trim($_SERVER["REMOTE_ADDR"]);
		
		$orderData["BACK_REF"] 			= $backref;
      
		// billing details
		$orderData["BILL_LNAME"] 		= trim($order->billing_last_name);
		$orderData["BILL_FNAME"]		= trim($order->billing_first_name);
		$orderData["BILL_EMAIL"] 		= trim($order->billing_email);
		$orderData["BILL_PHONE"] 		= trim($order->billing_phone);
		$orderData["BILL_COUNTRYCODE"] 	= trim($order->billing_country);
		
		// de|ivery details:
		
		$orderData["DELIVERY_FNAME"] 	= trim($order->shipping_first_name);
		$orderData["DELIVERY_LNAME"] 	= trim($order->shipping_last_name);
		$orderData["DELIVERY_PHONE"] 	= trim($order->billing_phone);
		$orderData["DELIVERY_ADDRESS"] 	= trim($order->shipping_address_1) ." ". trim($order->shipping_address_2);
		$orderData["DELIVERY_ZIPCODE"] 	= trim($order->shipping_postcode);
		$orderData["DELIVERY_CITY"] 	= trim($order->shipping_city);
		$orderData["DELIVERY_STATE"] 	= trim($order->shipping_state);
		$orderData["DELIVERY_COUNTRYCODE"] 	= trim($order->shipping_country);
		 
		$order	 = OpenPayU_Util::buildarParams($orderData,$secretKey);
	
		if (empty($order)) {
			
            throw new OpenPayU_Exception('Empty message OrderCreateRequest');
			
        }
		
        $result = OpenPayU_Http::post($pathUrl, $order);
	
        return $result['response'];
    }

}
