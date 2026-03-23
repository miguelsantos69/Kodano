<?php
declare(strict_types=1);

namespace Kodano\MobileCoupon\Test\Unit\Service;

use Kodano\MobileCoupon\Api\Service\DeviceContextValidatorInterface;
use Kodano\MobileCoupon\Service\DeviceContextValidator;
use Magento\Framework\App\Request\Http;
use Magento\SalesRule\Model\Rule;
use PHPUnit\Framework\TestCase;

class DeviceContextValidatorTest extends TestCase
{
    private function createRequestMock(?string $headerValue): Http
    {
        $requestMock = $this->getMockBuilder(Http::class)
            ->onlyMethods(['getHeader'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->method('getHeader')
            ->with(DeviceContextValidatorInterface::HEADER_DEVICE_TYPE)
            ->willReturn($headerValue);

        return $requestMock;
    }

    public function testIsMobileOnlyRuleReturnsTrue(): void
    {
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->addMethods(['getOnMobile'])
            ->disableOriginalConstructor()
            ->getMock();

        $ruleMock->method('getOnMobile')->willReturn(true);

        $requestMock = $this->createRequestMock(null);
        $validator = new DeviceContextValidator($requestMock);

        $this->assertTrue($validator->isMobileOnlyRule($ruleMock));
    }

    public function testIsMobileOnlyRuleReturnsFalse(): void
    {
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->addMethods(['getOnMobile'])
            ->disableOriginalConstructor()
            ->getMock();

        $ruleMock->method('getOnMobile')->willReturn(false);

        $requestMock = $this->createRequestMock(null);
        $validator = new DeviceContextValidator($requestMock);

        $this->assertFalse($validator->isMobileOnlyRule($ruleMock));
    }

    public function testIsMobileRequestReturnsTrue(): void
    {
        $requestMock = $this->createRequestMock(DeviceContextValidatorInterface::DEVICE_MOBILE);
        $validator = new DeviceContextValidator($requestMock);

        $this->assertTrue($validator->isMobileRequest());
    }

    public function testIsMobileRequestReturnsFalse(): void
    {
        $requestMock = $this->createRequestMock('desktop');
        $validator = new DeviceContextValidator($requestMock);

        $this->assertFalse($validator->isMobileRequest());
    }

    public function testIsMobileRequestReturnsFalseWhenHeaderMissing(): void
    {
        $requestMock = $this->createRequestMock(null);
        $validator = new DeviceContextValidator($requestMock);

        $this->assertFalse($validator->isMobileRequest());
    }
}
