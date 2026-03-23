<?php
declare(strict_types=1);

namespace Kodano\MobileCoupon\Test\Unit\Service;

use Kodano\MobileCoupon\Service\MobileCouponService;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon as CouponResource;
use Magento\SalesRule\Model\ResourceModel\Coupon\Collection as CouponCollection;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as CouponCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MobileCouponServiceTest extends TestCase
{
    private RuleRepositoryInterface|MockObject $ruleRepository;
    private SearchCriteriaBuilder|MockObject $searchCriteriaBuilder;
    private CouponCollectionFactory|MockObject $couponCollectionFactory;
    private CouponRepositoryInterface|MockObject $couponRepository;
    private RuleCollectionFactory|MockObject $ruleCollectionFactory;
    private CouponResource|MockObject $resourceModel;
    private LoggerInterface|MockObject $logger;
    private MobileCouponService $service;

    protected function setUp(): void
    {
        $this->ruleRepository = $this->createMock(RuleRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->couponCollectionFactory = $this->createMock(CouponCollectionFactory::class);
        $this->couponRepository = $this->createMock(CouponRepositoryInterface::class);
        $this->ruleCollectionFactory = $this->createMock(RuleCollectionFactory::class);
        $this->resourceModel = $this->createMock(CouponResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new MobileCouponService(
            $this->ruleRepository,
            $this->searchCriteriaBuilder,
            $this->couponCollectionFactory,
            $this->couponRepository,
            $this->ruleCollectionFactory,
            $this->resourceModel,
            $this->logger
        );
    }

    // ========================
    // TESTY getList()
    // ========================
    public function testGetListReturnsEmptyIfNoRules(): void
    {
        $criteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($criteriaMock);

        $ruleListMock = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);
        $ruleListMock->method('getItems')->willReturn([]);
        $this->ruleRepository->method('getList')->with($criteriaMock)->willReturn($ruleListMock);

        $this->assertSame([], $this->service->getList());
    }

    public function testGetListReturnsCouponCodes(): void
    {
        $criteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($criteriaMock);

        $ruleMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->addMethods(['getRuleId'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock->method('getRuleId')->willReturn(42);

        $ruleListMock = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);
        $ruleListMock->method('getItems')->willReturn([$ruleMock]);
        $this->ruleRepository->method('getList')->willReturn($ruleListMock);

        $couponMock = $this->getMockBuilder(Coupon::class)
            ->onlyMethods(['getCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $couponMock->method('getCode')->willReturn('MOBILE123');

        $couponCollectionMock = $this->createMock(CouponCollection::class);
        $couponCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $couponCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([$couponMock]));

        $this->couponCollectionFactory->method('create')->willReturn($couponCollectionMock);

        $this->assertSame(['MOBILE123'], $this->service->getList());
    }

    public function testGetListThrowsLocalizedExceptionOnError(): void
    {
        $criteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($criteriaMock);

        $this->ruleRepository->method('getList')->willThrowException(new \Exception('DB error'));
        $this->logger->expects($this->once())->method('error');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Error occurred when fetching mobile coupons');

        $this->service->getList();
    }

    // ========================
    // TESTY addMobileCoupon()
    // ========================
    public function testAddMobileCouponSavesSuccessfully(): void
    {
        $couponMock = $this->createMock(CouponInterface::class);
        $couponMock->method('getRuleId')->willReturn(42);

        $ruleMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->addMethods(['getOnMobile'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock->method('getOnMobile')->willReturn(true);

        $ruleCollectionMock = $this->createMock(RuleCollection::class);
        $ruleCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $ruleCollectionMock->method('getFirstItem')->willReturn($ruleMock);
        $this->ruleCollectionFactory->method('create')->willReturn($ruleCollectionMock);

        $this->couponRepository->method('save')->with($couponMock)->willReturn($couponMock);

        $result = $this->service->addMobileCoupon($couponMock);
        $this->assertSame([$couponMock], $result);
    }

    public function testAddMobileCouponThrowsLocalizedExceptionOnError(): void
    {
        $couponMock = $this->createMock(CouponInterface::class);
        $couponMock->method('getRuleId')->willReturn(42);

        $ruleMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->addMethods(['getOnMobile'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock->method('getOnMobile')->willReturn(true);

        $ruleCollectionMock = $this->createMock(RuleCollection::class);
        $ruleCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $ruleCollectionMock->method('getFirstItem')->willReturn($ruleMock);
        $this->ruleCollectionFactory->method('create')->willReturn($ruleCollectionMock);

        $this->couponRepository->method('save')->willThrowException(new \Exception('DB error'));
        $this->logger->expects($this->once())->method('error');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Error occurred when creating mobile coupons');

        $this->service->addMobileCoupon($couponMock);
    }

    public function testAddMobileCouponThrowsExceptionIfRuleNotMobile(): void
    {
        $couponMock = $this->createMock(CouponInterface::class);
        $couponMock->method('getRuleId')->willReturn(42);

        $ruleMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->addMethods(['getOnMobile'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock->method('getOnMobile')->willReturn(false);

        $ruleCollectionMock = $this->createMock(RuleCollection::class);
        $ruleCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $ruleCollectionMock->method('getFirstItem')->willReturn($ruleMock);
        $this->ruleCollectionFactory->method('create')->willReturn($ruleCollectionMock);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Error occurred when creating mobile coupons');

        $this->service->addMobileCoupon($couponMock);
    }

    // ========================
    // TESTY deleteMobileCoupon()
    // ========================
    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function testDeleteMobileCouponDeletesSuccessfully(): void
    {
        $couponMock = $this->getMockBuilder(Coupon::class)
            ->onlyMethods(['getCouponId', 'getRuleId'])
            ->disableOriginalConstructor()
            ->getMock();
        $couponMock->method('getCouponId')->willReturn(99);
        $couponMock->method('getRuleId')->willReturn(42);

        $ruleMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->addMethods(['getOnMobile'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock->method('getOnMobile')->willReturn(true);

        $ruleCollectionMock = $this->createMock(RuleCollection::class);
        $ruleCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $ruleCollectionMock->method('getFirstItem')->willReturn($ruleMock);

        $couponCollectionMock = $this->createMock(CouponCollection::class);
        $couponCollectionMock->method('load')->with(99)->willReturn($couponMock);

        $this->ruleCollectionFactory->method('create')->willReturn($ruleCollectionMock);
        $this->couponCollectionFactory->method('create')->willReturn($couponCollectionMock);

        $this->resourceModel->expects($this->once())->method('delete')->with($couponMock);

        $result = $this->service->deleteMobileCoupon(99);
        $this->assertTrue($result);
    }

    public function testDeleteMobileCouponThrowsNoSuchEntityIfNotFound(): void
    {
        $couponMock = $this->getMockBuilder(Coupon::class)
            ->onlyMethods(['getCouponId'])
            ->disableOriginalConstructor()
            ->getMock();
        $couponMock->method('getCouponId')->willReturn(null);

        $couponCollectionMock = $this->createMock(CouponCollection::class);
        $couponCollectionMock->method('load')->willReturn($couponMock);
        $this->couponCollectionFactory->method('create')->willReturn($couponCollectionMock);

        $this->expectException(NoSuchEntityException::class);
        $this->service->deleteMobileCoupon(99);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testDeleteMobileCouponThrowsExceptionIfRuleNotMobile(): void
    {
        $couponMock = $this->getMockBuilder(Coupon::class)
            ->onlyMethods(['getCouponId', 'getRuleId'])
            ->disableOriginalConstructor()
            ->getMock();
        $couponMock->method('getCouponId')->willReturn(99);
        $couponMock->method('getRuleId')->willReturn(42);

        $ruleMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->addMethods(['getOnMobile'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock->method('getOnMobile')->willReturn(false);

        $ruleCollectionMock = $this->createMock(RuleCollection::class);
        $ruleCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $ruleCollectionMock->method('getFirstItem')->willReturn($ruleMock);

        $couponCollectionMock = $this->createMock(CouponCollection::class);
        $couponCollectionMock->method('load')->willReturn($couponMock);

        $this->ruleCollectionFactory->method('create')->willReturn($ruleCollectionMock);
        $this->couponCollectionFactory->method('create')->willReturn($couponCollectionMock);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('You cannot delete the coupon—it is not mobile');

        $this->service->deleteMobileCoupon(99);
    }
}
