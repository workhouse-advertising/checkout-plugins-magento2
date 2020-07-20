<?php
namespace Lmerchant\Checkout\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as MonoLogger;

class Handler extends Base
{
    protected $level = MonoLogger::DEBUG;

    public function __construct(DriverInterface $filesystem, $filePath = null)
    {
        $now = new \DateTime('now');
        $strToday = $now->format('Y-m-d');
        $this->fileName = "/var/log/lmerchant_checkout_{$strToday}.log";
        parent::__construct($filesystem, $filePath);
    }
}
