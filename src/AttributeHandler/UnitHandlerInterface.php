<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeHandler;

interface UnitHandlerInterface
{
    /**
     * Returns the unit corresponding to a metric attribute, falling back to the unit value itself.
     *
     * @param array $attributeValue
     *
     * @return string
     */
    public function getUnit($attributeValue): string;
}
