<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeHandler;

use Webmozart\Assert\Assert;

final class MetricAttributeHandler implements MetricAttributeHandlerInterface
{
    /**
     * @param mixed $value
     */
    public function supports($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $value
     *
     * @return string|array|bool
     */
    public function getValue($value)
    {
        Assert::isArray($value);
        if (!array_key_exists('amount', $value)) {
            throw new \LogicException('Amount key not found');
        }
        if (!array_key_exists('unit', $value)) {
            throw new \LogicException('Unit key not found');
        }

        return $value['amount'] . ' ' . $value['unit'];
    }
}
