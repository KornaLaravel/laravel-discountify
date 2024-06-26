<?php

declare(strict_types=1);

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Condition
 *
 * @method static \Safemood\Discountify\ConditionManager add(array $conditions)
 * @method static \Safemood\Discountify\ConditionManager define(string $slug, callable $condition, float $discount)
 * @method static \Safemood\Discountify\ConditionManager defineIf(string $slug,bool $condition, float $discount)
 * @method static array getConditions()
 * @method static array getItems()
 *
 * @see \Safemood\Discountify\ConditionManager
 */
class Condition extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Safemood\Discountify\ConditionManager::class;
    }
}
