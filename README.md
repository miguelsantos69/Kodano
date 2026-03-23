# MobileCoupon module
The MobileCoupon module extends the standard functionality of discount coupons in Magento 2, allowing you to restrict their use exclusively to mobile devices.

#### Key features:

- Add an attribute specifying whether a coupon is “on_mobile”
- Device context validation (mobile / desktop)
- Block the use of mobile coupons on non-mobile devices
- Integration with the API and Cart Price Rules

#### The module provides 3 REST API endpoints:
- get all mobile coupons: ```GET /V1/mobile-coupons/search```
- create new mobile coupon  ```POST /V1/mobile-coupons```
- delete mobile coupon by ID ```DELETE /V1/mobile-coupons/:couponID```
