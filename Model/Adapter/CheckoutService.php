<?php

namespace Latitude\Checkout\Model\Adapter;

use \Magento\Framework\Exception\LocalizedException as LocalizedException;
use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;
use \Latitude\Checkout\Model\Util\Helper as LatitudeHelper;

class CheckoutService
{
    protected $curlClient;
    protected $storeManager;
    protected $logger;
    protected $latitudeHelper;

    const ENDPOINT_PURCHASE = "/purchase";
    const ENDPOINT_PURCHASE_VERIFY = "/purchase/verify";
    const ENDPOINT_REFUND = "/refund";

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
        $this->logger->debug(__METHOD__. " Begin");

        try {
            if ($this->latitudeHelper->isDebugMode()) {
                $this->logger->info("preparing request for POST:". $endpoint);
            }

            $this->_setHeaders();
            $this->_setOptions();

            $url = $this->latitudeHelper->getApiUrl() . $endpoint;
            $requestBody = json_encode($payload, JSON_UNESCAPED_SLASHES);

            $this->curlClient->post($url, $requestBody);

            $responseStatusCode = $this->curlClient->getStatus();
            $responseBody = json_decode($this->curlClient->getBody(), true);

            if ($this->latitudeHelper->isDebugMode()) {
                $this->logger->info($url . " (REQUEST): ". $requestBody);
                $this->logger->info($url . " (RESPONSE STATUS CODE): ". $responseStatusCode);
                $this->logger->info($url . " (RESPONSE BODY): ". json_encode($responseBody));
            }

            if ($responseStatusCode < 200 || $responseStatusCode > 299) {
                return $this->_handleError("Status ". $responseStatusCode. " does not indicate success");
            }

            return $this->_handleSuccess($responseBody);
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
            "Expect:" => "100-continue",
        ];

        $this->curlClient->setHeaders($headers);
    }

    private function _setOptions()
    {
        $options =  array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
        );

        $this->curlClient->setOptions($options);
    }

    private function _handleSuccess($body)
    {
        $this->logger->info(__METHOD__. " ". json_encode($body));

        return [
            "error" => false,
            "body" => $body
        ];
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
