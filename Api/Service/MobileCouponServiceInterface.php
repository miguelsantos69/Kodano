<?php

namespace Kodano\MobileCoupon\Api\Service;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Api\Data\RuleInterface;

interface MobileCouponServiceInterface
{
    /**
     * @return RuleInterface[]
     */
    public function getList(): array;

    /**
     * @param CouponInterface $coupon
     * @return CouponInterface[]
     * @throws NoSuchEntityException
     */
    public function addMobileCoupon(CouponInterface $coupon): array;

    /**
     * @param int $couponId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function deleteMobileCoupon(int $couponId): bool;
}
