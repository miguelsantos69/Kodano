<?php
declare(strict_types=1);

namespace Kodano\MobileCoupon\Service;

use Kodano\MobileCoupon\Api\Service\MobileCouponServiceInterface;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as CouponCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\SalesRule\Model\Spi\CouponResourceInterface;
use Psr\Log\LoggerInterface;

class MobileCouponService implements MobileCouponServiceInterface
{
    public function __construct(
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly CouponCollectionFactory $couponCollectionFactory,
        private readonly CouponRepositoryInterface $couponRepository,
        private readonly RuleCollectionFactory $ruleCollection,
        private readonly CouponResourceInterface $resourceModel,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function getList(): array
    {
        try {
            $criteria = $this->searchCriteriaBuilder
                ->addFilter('on_mobile', 1)
                ->create();

            $rules = $this->ruleRepository->getList($criteria)->getItems();

            $ruleIds = [];

            foreach ($rules as $rule) {
                $ruleIds[] = $rule->getRuleId();
            }

            if (!$ruleIds) {
                return [];
            }

            $couponCollection = $this->couponCollectionFactory->create();
            $couponCollection->addFieldToFilter('rule_id', ['in' => $ruleIds]);

            $codes = [];

            foreach ($couponCollection as $coupon) {
                $codes[] = $coupon->getCode();
            }
        } catch (Exception $exception) {
            $this->logger->error($exception);
            throw new LocalizedException(
                __('Error occurred when fetching mobile coupons', $exception->getMessage())
            );
        }

        return $codes;
    }

    /**
     * @throws LocalizedException
     */
    public function addMobileCoupon(CouponInterface $coupon): array
    {
        try {
            $collection = $this->ruleCollection->create();
            $collection->addFieldToFilter('rule_id', $coupon->getRuleId());
            $rule = $collection->getFirstItem();
            if (!$rule->getOnMobile()) {
                throw new LocalizedException(__('Specified rule does not allow mobile coupons'));
            }
            $coupon = $this->couponRepository->save($coupon);
        } catch (Exception $exception) {
            $this->logger->error($exception);
            throw new LocalizedException(
                __('Error occurred when creating mobile coupons', $exception->getMessage())
            );
        }
        return [$coupon];
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteMobileCoupon(int $couponId): bool
    {
        /** @var Coupon $coupon */
        $coupon = $this->couponCollectionFactory->create()
            ->load($couponId);

        if (!$coupon->getCouponId()) {
            throw new NoSuchEntityException();
        }
        $collection = $this->ruleCollection->create();
        $collection->addFieldToFilter('rule_id', $coupon->getRuleId());
        $rule = $collection->getFirstItem();
        if (!$rule->getOnMobile()) {
            throw new LocalizedException(__('You cannot delete the coupon—it is not mobile'));
        }
        $this->resourceModel->delete($coupon);
        return true;
    }
}
