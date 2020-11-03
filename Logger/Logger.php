<?php
namespace Latitude\Checkout\Logger;

use \Magento\Framework\Exception as Exception;
use Monolog\Logger as MonoLogger;

class Logger extends MonoLogger
{
    public function __construct($name, array $handlers = [], array $processors = [])
    {
        parent::__construct($name, $handlers, $processors);
        
        $version = $this->_getVersion();
        
        $this->pushProcessor(function ($record) use ($version) {
            $record['extra']['magentoVersion'] = $version;
            return $record;
        });
    }

    private function _getVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('\Magento\Framework\App\ProductMetadataInterface');
        return $productMetadata->getVersion();
    }
}
