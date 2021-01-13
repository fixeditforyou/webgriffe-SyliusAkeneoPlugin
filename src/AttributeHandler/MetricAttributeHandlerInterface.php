<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeHandler;

interface MetricAttributeHandlerInterface
{
    /**
     * @param mixed $value
     */
    public function supports($value): bool;

    /**
     * @param mixed $value
     *
     * @return string|array|bool
     */
    public function getValue($value);
}
