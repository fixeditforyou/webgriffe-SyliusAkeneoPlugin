<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeHandler;

use Webmozart\Assert\Assert;

final class MetricAttributeHandler implements MetricAttributeHandlerInterface
{
    /**
     * @var UnitHandlerInterface
     */
    private $unitHandler;

    public function __construct(UnitHandlerInterface $unitHandler)
    {
        $this->unitHandler = $unitHandler;
    }

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

        $amount = $this->getAmount($value);
        $unit = $this->unitHandler->getUnit($value);

        return $amount . $unit;
    }

    private function getAmount(array $value): string
    {
        if (!array_key_exists('amount', $value)) {
            throw new \LogicException('Amount key not found');
        }

        return (string) $value['amount'];
    }
}
