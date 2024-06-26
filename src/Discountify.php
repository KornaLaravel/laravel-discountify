<?php

declare(strict_types=1);

namespace Safemood\Discountify;

use Safemood\Discountify\Concerns\HasCalculations;
use Safemood\Discountify\Concerns\HasConditions;
use Safemood\Discountify\Concerns\HasCoupons;
use Safemood\Discountify\Concerns\HasDynamicFields;
use Safemood\Discountify\Contracts\DiscountifyInterface;
use Safemood\Discountify\Events\DiscountAppliedEvent;

/**
 * Class Discountify
 *
 * @method array getConditions()
 * @method ConditionManager conditions()
 * @method float getGlobalTaxRate()
 * @method float getGlobalDiscount()
 * @method array getItems()
 * @method float subtotal()
 * @method float tax()
 * @method float taxAmount(bool $afterDiscount = false)
 * @method float total()
 * @method float totalWithDiscount(?float $globalDiscount = null)
 * @method self discount(float $globalDiscount)
 * @method self setFields(array $fields)
 * @method CouponManager coupons()
 * @method self addCoupon(array $coupon)
 * @method self removeCoupon(string $code)
 * @method self applyCoupon(string $code, int|string $userId = null)
 * @method array|null getCoupon(string $code)
 * @method float getCouponDiscount()
 * @method self removeAppliedCoupons()
 * @method self clearAppliedCoupons()
 * @method array getAppliedCoupons()
 */
class Discountify implements DiscountifyInterface
{
    use HasCalculations;
    use HasConditions;
    use HasCoupons;
    use HasDynamicFields;

    /**
     * @var array<mixed> The items in the cart.
     */
    protected array $items = [];

    /**
     * @var float The global discount percentage.
     */
    protected float $globalDiscount;

    /**
     * @var float The global tax rate.
     */
    protected float $globalTaxRate;

    /**
     * Discountify constructor.
     */
    public function __construct(
        protected ConditionManager $conditionManager,
        protected CouponManager $couponManager
    ) {
        $this->setGlobalDiscount(config('discountify.global_discount'));
        $this->setGlobalTaxRate(config('discountify.global_tax_rate'));
    }

    /**
     * Set the global discount.
     *
     * @param  float  $globalDiscount  The global discount percentage.
     */
    public function discount(float $globalDiscount): self
    {
        $this->globalDiscount = $globalDiscount;

        return $this;
    }

    /**
     * Calculate the total discount rate percentage based on conditions.
     */
    public function conditionDiscount(): float
    {
        return array_reduce(
            $this->conditionManager->getConditions(),
            function (float $discount, array $condition): float {
                $result = is_callable($condition['condition'])
                    ? $condition['condition']($this->items)
                    : $condition['condition'];

                if (config('discountify.fire_events')) {
                    event(new DiscountAppliedEvent($condition['slug'], $condition['discount'], $condition['condition']));
                }

                return (float) $discount + match (true) {
                    $result === true => $condition['discount'],
                    default => 0,
                };
            },
            0
        );
    }

    /**
     * Get the ConditionManager instance.
     */
    public function conditions(): ConditionManager
    {
        return $this->conditionManager;
    }

    /**
     * Get the CouponManager instance.
     */
    public function coupons(): CouponManager
    {
        return $this->couponManager;
    }

    /**
     * Get the global discount percentage.
     *
     * @return float The global discount percentage.
     */
    public function getGlobalDiscount(): float
    {
        return $this->globalDiscount;
    }

    /**
     * Get the global tax rate.
     *
     * @return float The global tax rate.
     */
    public function getGlobalTaxRate(): float
    {
        return $this->globalTaxRate;
    }

    /**
     * Get the items in the cart.
     *
     * @return array<mixed> The items in the cart.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Set the ConditionManager instance.
     *
     * @param  ConditionManager  $conditionManager  The ConditionManager instance to set.
     */
    public function setConditionManager(ConditionManager $conditionManager): self
    {
        $this->conditionManager = $conditionManager;

        return $this;
    }

    /**
     * Set the CouponManager instance.
     *
     * @param  CouponManager  $couponManager  The CouponManager instance to set.
     */
    public function setCouponManager(CouponManager $couponManager): self
    {
        $this->couponManager = $couponManager;

        return $this;
    }

    /**
     * Set the global discount.
     *
     * @param  float  $globalDiscount  The global discount percentage to set.
     */
    public function setGlobalDiscount(float $globalDiscount): self
    {
        $this->globalDiscount = $globalDiscount;

        return $this;
    }

    /**
     * Set the global tax rate.
     *
     * @param  float  $globalTaxRate  The global tax rate to set.
     */
    public function setGlobalTaxRate(float $globalTaxRate): self
    {
        $this->globalTaxRate = $globalTaxRate;

        return $this;
    }

    /**
     * Set the items in the cart.
     *
     * @param  array<mixed>  $items  The items to set in the cart.
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Calculate the subtotal of the cart.
     *
     * @return float The subtotal of the cart.
     */
    public function subtotal(): float
    {
        return $this->calculateSubtotal();
    }

    /**
     * Calculate the total tax of the cart.
     *
     * @param  float|null  $globalTaxRate  The global tax rate. Defaults to null.
     * @return float The total tax of the cart.
     */
    public function tax(?float $globalTaxRate = null): float
    {
        return $this->calculateTotalWithTaxes($globalTaxRate);
    }

    /**
     * Calculate the total tax amount of the cart.
     *
     * @param  float|null  $globalTaxRate  The global tax rate. Defaults to null.
     * @return float The total tax amount of the cart.
     */
    public function taxAmount(?float $globalTaxRate = null): float
    {
        return $this->calculateTaxAmount($globalTaxRate);
    }

    /**
     * Calculate the savings of the cart.
     *
     * @param  float|null  $globalDiscount  The global discount percentage. Defaults to null.
     * @return float The savings of the cart.
     */
    public function savings(?float $globalDiscount = null): float
    {
        return round($this->calculateSavings($globalDiscount), 3);
    }

    /**
     * Calculate the final total of the cart.
     *
     * @return float The final total of the cart.
     */
    public function total(): float
    {
        return round($this->calculateFinalTotal(), 3);
    }

    /**
     * Calculate the detailed final total of the cart.
     *
     * @return array The detailed final total of the cart.
     */
    public function totalDetailed(): array
    {
        return $this->calculateFinalTotalDetails();
    }

    /**
     * Calculate the total with applied discount.
     *
     * @param  float|null  $globalDiscount  The global discount percentage. Defaults to null.
     * @return float The total with applied discount.
     */
    public function totalWithDiscount(?float $globalDiscount = null): float
    {
        return $this->calculateTotalAfterDiscount($globalDiscount);
    }
}
