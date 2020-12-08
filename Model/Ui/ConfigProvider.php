<?php
namespace Latitude\Checkout\Model\Ui;

final class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    const CODE = 'latitude';
    const SUCCESS = 0;
    const FRAUD = 1;

    const LABEL_AU = "Latitude Interest Free";
    const LABEL_NZ = "Gem Interest Free";

    const LOGO_AU = "https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg";
    const LOGO_NZ = "https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg";

    protected $latitudeHelper;

    public function __construct(
        \Latitude\Checkout\Model\Util\Helper $latitudeHelper
    ) {
        $this->latitudeHelper = $latitudeHelper;
    }

    /**
     * Retrieve assoc array of latitude checkout configuration
     * @return array
     */
    public function getConfig()
    {
        $isNZ = $this->latitudeHelper->isNZMerchant();

        return [
            "payment" => [
                self::CODE => [
                    "transactionResults" => [
                        self::SUCCESS => __('Success'),
                        self::FRAUD => __('Fraud')
                    ],
                    "content" => [
                        "label" => $isNZ ? self::LABEL_NZ : self::LABEL_AU,
                        "logoURL" => $isNZ ? self::LOGO_NZ : self::LOGO_AU,
                    ],
                    "options" => [
                        "merchantId" => $this->latitudeHelper->getMerchantId(),
                        "page" => "checkout",
                        "currency" => $this->latitudeHelper->getBaseCurrency()
                    ],
                    "scriptURL" => $this->latitudeHelper->getScriptURL()
                ]
            ]
        ];
    }
}
