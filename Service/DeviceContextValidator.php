<?php
declare(strict_types=1);

namespace Kodano\MobileCoupon\Service;

use Kodano\MobileCoupon\Api\Service\DeviceContextValidatorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\SalesRule\Model\Rule;

class DeviceContextValidator implements DeviceContextValidatorInterface
{
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    public function isMobileOnlyRule(Rule $rule): bool
    {
        return (bool) $rule->getOnMobile();
    }

    /**
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function isMobileRequest(): bool
    {
        return $this->request->getHeader(self::HEADER_DEVICE_TYPE) === self::DEVICE_MOBILE;
    }
}
