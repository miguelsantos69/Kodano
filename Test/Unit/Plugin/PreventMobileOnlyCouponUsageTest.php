<?php
declare(strict_types=1);

namespace Kodano\MobileCoupon\Test\Unit\Plugin;

use Kodano\MobileCoupon\Plugin\PreventMobileOnlyCouponUsage;
use Kodano\MobileCoupon\Api\Service\DeviceContextValidatorInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreventMobileOnlyCouponUsageTest extends TestCase
{
    private DeviceContextValidatorInterface|MockObject $deviceContext;
    private PreventMobileOnlyCouponUsage $plugin;

    protected function setUp(): void
    {
        $this->deviceContext = $this->createMock(DeviceContextValidatorInterface::class);
        $this->plugin = new PreventMobileOnlyCouponUsage($this->deviceContext);
    }

    public function testReturnsFalseWhenRuleIsMobileOnlyAndRequestNotMobile(): void
    {
        $rule = $this->createMock(Rule::class);
        $address = $this->createMock(Address::class);
        $subject = $this->createMock(Utility::class);

        $this->deviceContext->method('isMobileOnlyRule')->with($rule)->willReturn(true);
        $this->deviceContext->method('isMobileRequest')->willReturn(false);

        $proceed = function() {
            throw new \RuntimeException('Should not call proceed');
        };

        $result = $this->plugin->aroundCanProcessRule($subject, $proceed, $rule, $address);
        $this->assertFalse($result);
    }

    public function testCallsProceedWhenRuleIsMobileOnlyAndRequestIsMobile(): void
    {
        $rule = $this->createMock(Rule::class);
        $address = $this->createMock(Address::class);
        $subject = $this->createMock(Utility::class);

        $this->deviceContext->method('isMobileOnlyRule')->willReturn(true);
        $this->deviceContext->method('isMobileRequest')->willReturn(true);

        $proceed = function(Rule $r, Address $a): bool {
            return true;
        };

        $result = $this->plugin->aroundCanProcessRule($subject, $proceed, $rule, $address);
        $this->assertTrue($result);
    }

    public function testCallsProceedWhenRuleIsNotMobileOnly(): void
    {
        $rule = $this->createMock(Rule::class);
        $address = $this->createMock(Address::class);
        $subject = $this->createMock(Utility::class);

        $this->deviceContext->method('isMobileOnlyRule')->willReturn(false);

        $proceed = function(Rule $r, Address $a): bool {
            return false;
        };

        $result = $this->plugin->aroundCanProcessRule($subject, $proceed, $rule, $address);
        $this->assertFalse($result);
    }
}
