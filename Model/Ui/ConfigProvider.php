<?php
namespace Latitude\Checkout\Model\Ui;

final class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    const CODE = 'latitude';
    const SUCCESS = 0;
    const FRAUD = 1;

    protected $contentAdapter;

    public function __construct(
        \Latitude\Checkout\Model\Adapter\Content $contentAdapter
    ) {
        $this->contentAdapter = $contentAdapter;
    }

    /**
     * Retrieve assoc array of latitude checkout configuration
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        self::SUCCESS => __('Success'),
                        self::FRAUD => __('Fraud')
                    ],
                    'logoURL' => $this->contentAdapter->getLogoURL(),
                    'termsURL' => $this->contentAdapter->getTermsURL(),
                    'content' => $this->contentAdapter->getContent(),
                ]
            ]
        ];
    }
}
