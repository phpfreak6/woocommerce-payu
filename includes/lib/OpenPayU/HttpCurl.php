<?php
/**
 * OpenPayU Standard Library
 *
 * @copyright  Copyright (c) 2011-2015 PayU
 * @license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 * http://www.payu.com
 * http://developers.payu.com
 */

class OpenPayU_HttpCurl
{
    /**
     * @var
     */
    static $headers;

    /**
     * @param $requestType
     * @param string $pathUrl
     * @param $data
     * @param $posId
     * @param $signatureKey
     * @return mixed
     * @throws OpenPayU_Exception_Configuration
     * @throws OpenPayU_Exception_Network
     * @throws OpenPayU_Exception_Authorization
     */
    public static function doRequest($pathUrl, $data)
    {
		if (empty($pathUrl))
			
            throw new OpenPayU_Exception_Configuration('The endpoint is empty');
		
		$ch = curl_init();
		 
		curl_setopt($ch, CURLOPT_URL, $pathUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);
		
		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);
		
		$response = json_decode(json_encode((array)simplexml_load_string($response)),1);
		
		if($response["STATUS"] == "SUCCESS"){
			
			if (($response["RETURN_CODE"] == "3DS_ENROLLED") && (!empty($response["URL_3DS"]))) {
				
                header("Location:" . $response["URL_3DS"]);
				
                die();
				
            }
			
		}
		
        return array('code' => $httpStatus, 'response' => $response);
    }

    /**
     * @param array $headers
     *
     * @return mixed
     */
    public static function getSignature($headers)
    {
        foreach($headers as $name => $value)
        {
            if(preg_match('/X-OpenPayU-Signature/i', $name) || preg_match('/OpenPayu-Signature/i', $name))
                return $value;
        }

        return null;
    }

    /**
     * @param resource $ch
     * @param string $header
     * @return int
     */
    public static function readHeader($ch, $header)
    {
        if( preg_match('/([^:]+): (.+)/m', $header, $match) ) {
            self::$headers[$match[1]] = trim($match[2]);
        }

        return strlen($header);
    }
	
}
