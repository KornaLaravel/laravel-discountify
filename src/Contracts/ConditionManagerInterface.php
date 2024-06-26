<?php

declare(strict_types=1);

namespace Safemood\Discountify\Contracts;

use Safemood\Discountify\ConditionManager;

interface ConditionManagerInterface
{
    public function add(array $conditions): ConditionManager;

    public function define(string $slug, callable $condition, float $discount, bool $skip = false): ConditionManager;

    public function defineIf(string $slug, bool $condition, float $discount): ConditionManager;

    public function getConditions(): array;
}
