<?php

/**
 * OpenPayU Standard Library
 *
 * @copyright  Copyright (c) 2011-2015 PayU
 * @license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 * http://www.payu.com
 * http://developers.payu.com
 */

class OpenPayU_Refund extends OpenPayU
{
    /**
     * Function make refund for order
     * @param $orderId
     * @param $description
     * @param int $amount Amount of refund in pennies
     * @return null|OpenPayU_Result
     * @throws OpenPayU_Exception
     */
    public static function create($order,$amount)
    {
        if (empty($order)){
            throw new OpenPayU_Exception('Invalid order value for refund');
		}
        
		$merchant = OpenPayU_Configuration::getMerchantPosId();
		
		$secretKey	= OpenPayU_Configuration::getSignatureKey();
		
		$post_meta = get_post_meta( $order->id, '_payu_charge_id' , false );
		
		$refno = $post_meta[0];
		
		$currency = trim(get_woocommerce_currency());
		
		$refund = array(
				'MERCHANT' => $merchant,
				'ORDER_REF' => $refno,
				'ORDER_AMOUNT' => $amount,
				'ORDER_CURRENCY' => $currency,
				'AMOUNT' => $amount,
				'IRN_DATE' => gmdate('Y-m-d H:i:s'),
			);
      
			ksort($refund);

			$hashString = "";

			foreach ($refund as $key=>$val) {
				$hashString .= strlen($val) . $val;
			}

			$refund["ORDER_HASH"] = hash_hmac("md5", $hashString, $secretKey);
			//end HASH calculation
			
			$pathUrl = "https://secure.payu.com.tr/order/irn.php";
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($refund));
			
			$response = curl_exec($ch);
	
			$response = explode('|',$response);
			
			if($response[2] == 'OK'){
				
				return true;
				
			}else{
				
				return false;
				
			}
    }

}