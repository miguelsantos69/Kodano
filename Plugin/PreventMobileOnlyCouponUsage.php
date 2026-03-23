<?php
declare(strict_types=1);

namespace Kodano\MobileCoupon\Plugin;

use Kodano\MobileCoupon\Api\Service\DeviceContextValidatorInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility;

class PreventMobileOnlyCouponUsage
{
    public function __construct(
        private readonly DeviceContextValidatorInterface $deviceContext
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCanProcessRule(
        Utility $subject,
        callable $proceed,
        Rule $rule,
        Address $address
    ): bool {

        if($this->deviceContext->isMobileOnlyRule($rule) && !$this->deviceContext->isMobileRequest()) {
            return false;
        }

        return $proceed($rule, $address);
    }
}
