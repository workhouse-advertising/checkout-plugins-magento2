<?php
namespace Latitude\Checkout\Model\Util;

use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;

/**
 * Class Convert
 * @package Latitude\Checkout\Model\Util
 */
class Convert {
    public function toPrice($val) {
        if(empty($val)) {
            return 0;
        }

        return round((float)$val, 2);
    }
}
