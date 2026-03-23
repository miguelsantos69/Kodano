<?php
declare(strict_types=1);

namespace Kodano\MobileCoupon\Api\Service;

use Magento\SalesRule\Model\Rule;

interface DeviceContextValidatorInterface
{
    public const HEADER_DEVICE_TYPE = 'X-Device-Type';
    public const DEVICE_MOBILE = 'mobile';

    public function isMobileOnlyRule(Rule $rule): bool;

    public function isMobileRequest(): bool;
}
