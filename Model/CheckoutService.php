<?php

namespace Latitude\Checkout\Model;

use \Magento\Framework\Exception\LocalizedException as LocalizedException;
use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;
use \Latitude\Checkout\Model\Util\Helper as LatitudeHelper;

class CheckoutService
{
    protected $curlClient;
    protected $storeManager;
    protected $logger;
    protected $latitudeHelper;

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curlClient,
        \Latitude\Checkout\Logger\Logger $logger,
        LatitudeHelper $latitudeHelper
    ) {
        $this->curlClient = $curlClient;
        $this->logger = $logger;
        $this->latitudeHelper = $latitudeHelper;
    }

    public function post($endpoint, $payload)
    {
        try {
            $this->_setHeaders();
            $this->_setOptions();

            $url = $this->latitudeHelper->getApiUrl() . $endpoint;
            $this->curlClient->post($url, json_encode($payload, JSON_UNESCAPED_SLASHES));

            $responseStatusCode = $this->curlClient->getStatus();
            $responseBody = $this->curlClient->getBody();

            if ($this->latitudeHelper->isDebugMode()) {
                $this->logger->info($url . " (REQUEST): ". json_encode($payload, JSON_UNESCAPED_SLASHES));
                $this->logger->info($url . " (RESPONSE STATUS CODE): ". $responseStatusCode);
                $this->logger->info($url . " (RESPONSE BODY): ". json_encode($responseBody, JSON_UNESCAPED_SLASHES));
            }

            if ($responseStatusCode < 200 || $responseStatusCode > 299) {
                return $this->_handleError("Status ". $responseStatusCode. " does not indicate success");
            }

            return $responseBody;
        } catch (\Exception $e) {
            return $this->_handleError($e->getMessage());
        }
    }

    private function _setHeaders()
    {
        $merchantId = $this->latitudeHelper->getMerchantId();
        $merchantSecret = $this->latitudeHelper->getMerchantSecret();
        $storeBaseUrl = $this->latitudeHelper->getStoreBaseUrl();

        $authToken = "Basic " . base64_encode($merchantId . ':' . $merchantSecret);

        $headers = [
            'Authorization' => $authToken,
            "Content-Type" => "application/json",
            "Referer" => $storeBaseUrl,
        ];

        $this->curlClient->setHeaders($headers);
    }

    private function _setOptions()
    {
        $options =  array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   =>CURL_HTTP_VERSION_1_1
        );

        $this->curlClient->setOptions($options);
    }

    private function _handleError($message)
    {
        $this->logger->error(__METHOD__. " ". $message);

        return [
            "error" => true,
            "message" => $message
        ];
    }
}
