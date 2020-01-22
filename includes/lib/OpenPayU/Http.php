<?php
/**
 * OpenPayU Standard Library
 *
 * @copyright  Copyright (c) 2011-2015 PayU
 * @license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 * http://www.payu.com
 * http://developers.payu.com
 */

class OpenPayU_Http
{
    /**
     * @param $pathUrl
     * @param $data
     * @return mixed
     */
    public static function post($pathUrl, $data)
    {	
        $signatureKey = OpenPayU_Configuration::getSignatureKey();

        $response = OpenPayU_HttpCurl::doRequest($pathUrl, $data);
	
        return $response;
    }

    /**
     * @param $pathUrl
     * @param $data
     * @return mixed
     */
    public static function get($pathUrl, $data)
    {
        $posId = OpenPayU_Configuration::getMerchantPosId();
        $signatureKey = OpenPayU_Configuration::getSignatureKey();

        $response = OpenPayU_HttpCurl::doRequest('GET', $pathUrl, $data, $posId, $signatureKey);

        return $response;
    }

    /**
     * @param $pathUrl
     * @param $data
     * @return mixed
     */
    public static function put($pathUrl, $data)
    {
        $posId = OpenPayU_Configuration::getMerchantPosId();
        $signatureKey = OpenPayU_Configuration::getSignatureKey();

        $response = OpenPayU_HttpCurl::doRequest('PUT', $pathUrl, $data, $posId, $signatureKey);

        return $response;
    }

    /**
     * @param $pathUrl
     * @param $data
     * @return mixed
     */
    public static function delete($pathUrl, $data)
    {
        $posId = OpenPayU_Configuration::getMerchantPosId();
        $signatureKey = OpenPayU_Configuration::getSignatureKey();

        $response = OpenPayU_HttpCurl::doRequest('DELETE', $pathUrl, $data, $posId, $signatureKey);

        return $response;
    }

}
