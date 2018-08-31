<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\ExpressRates\Model\Rate;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Method;

/**
 * Interface RateProcessorInterface
 *
 * @package Dhl\ExpressRates\Model\Rate
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @copyright 2018 Netresearch GmbH & Co. KG
 * @link http://www.netresearch.de/
 */
interface RateProcessorInterface
{
    /**
     * Performs arbitrary updates on the rate method array and returns an array with updated methods
     *
     * @param Method[]         $methods List of rate methods
     * @param RateRequest|null $request The rate request
     *
     * @return Method[]
     */
    public function processMethods(array $methods, $request = null);
}
