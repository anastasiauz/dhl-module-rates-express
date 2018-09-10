<?php
/**
 * See LICENSE.md for license details.
 */
namespace Dhl\ExpressRates\Model\Rate\Processor;

use Dhl\Express\Api\Data\ShippingProductsInterface;
use Dhl\ExpressRates\Model\Config\ModuleConfigInterface;
use Dhl\ExpressRates\Model\Rate\RateProcessorInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Method;

/**
 * A rate processor to remove the shipping price if certain conditions are met.
 *
 * @package  Dhl\ExpressRates\Model
 * @author   Rico Sonntag <rico.sonntag@netresearch.de>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     http://www.netresearch.de/
 */
class FreeShipping implements RateProcessorInterface
{
    /**
     * The module configuration.
     *
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * Constructor.
     *
     * @param ModuleConfigInterface $moduleConfig The module configuration
     */
    public function __construct(
        ModuleConfigInterface $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @inheritdoc
     */
    public function processMethods(array $methods, $request = null)
    {
        if ($request === null) {
            return $methods;
        }

        $productsSubTotal = $this->getBaseSubTotalInclTax($request);
        $domesticBaseSubTotal = $this->moduleConfig->getDomesticFreeShippingSubTotal();
        $intlBaseSubTotal = $this->moduleConfig->getInternationalFreeShippingSubTotal();

        /** @var Method $method */
        foreach ($methods as $method) {
            if ($this->isDomesticShipping($method)
                && $this->moduleConfig->isDomesticFreeShippingEnabled()
                && $this->isEnabledDomesticProduct($method)
            ) {
                $configuredSubTotal = $domesticBaseSubTotal;
            } elseif (!$this->isDomesticShipping($method)
                      && $this->moduleConfig->isInternationalFreeShippingEnabled()
                      && $this->isEnabledInternationalProduct($method)
            ) {
                $configuredSubTotal = $intlBaseSubTotal;
            } else {
                continue;
            }

            if ($productsSubTotal >= $configuredSubTotal) {
                $method->setPrice(0.0);
                $method->setCost(0.0);
            }
        }

        return $methods;
    }

    /**
     * Returns the base sub total value including tax. Checks if the value of virtual products should
     * be included in the sum.
     *
     * @param RateRequest $request The rate request
     *
     * @return float
     */
    private function getBaseSubTotalInclTax(RateRequest $request)
    {
        if ($this->moduleConfig->isFreeShippingVirtualProductsIncluded()) {
            return $request->getBaseSubtotalInclTax();
        }

        $baseSubTotal = 0.0;

        if ($request->getAllItems()) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($request->getAllItems() as $item) {
                if (!$item->getProduct()->isVirtual()) {
                    $baseSubTotal += $item->getBasePriceInclTax();
                }
            }
        }

        return $baseSubTotal;
    }

    /**
     * Returns whether the given method applies to domestic shipping or not.
     *
     * @param Method $method The rate method
     *
     * @return bool
     */
    private function isDomesticShipping(Method $method)
    {
        return \in_array($method->getMethod(), ShippingProductsInterface::PRODUCTS_DOMESTIC, true);
    }

    /**
     * Returns whether the product is enabled in the configuration or not.
     *
     * @param Method $method The rate method
     *
     * @return bool
     */
    private function isEnabledDomesticProduct(Method $method)
    {
        return \in_array(
            $method->getData('method'),
            $this->moduleConfig->getDomesticFreeShippingProducts(),
            true
        );
    }

    /**
     * Returns whether the product is enabled in the configuration or not.
     *
     * @param Method $method The rate method
     *
     * @return bool
     */
    private function isEnabledInternationalProduct(Method $method)
    {
        return \in_array(
            $method->getData('method'),
            $this->moduleConfig->getInternationalFreeShippingProducts(),
            true
        );
    }
}
