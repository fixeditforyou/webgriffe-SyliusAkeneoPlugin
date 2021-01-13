<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\AttributeHandler;

use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusAkeneoPlugin\AttributeHandler\MetricAttributeHandler;
use Webgriffe\SyliusAkeneoPlugin\AttributeHandler\MetricAttributeHandlerInterface;

class MetricAttributeHandlerSpec extends ObjectBehavior
{
    public function let(
    ) {
        $this->beConstructedWith();
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(MetricAttributeHandler::class);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(MetricAttributeHandlerInterface::class);
    }

    function it_supports_array_value()
    {
        $value = [
            'amount' => 23,
            'unit' => 'INCH',
        ];
        $this->supports($value)->shouldReturn(true);
    }

    function it_does_not_support_other_type_of_value()
    {
        $this->supports(new \stdClass())->shouldReturn(false);
    }

    function it_throws_exception_during_get_value_when_value_is_not_array()
    {
        $this->shouldThrow(
            new \InvalidArgumentException(sprintf('Expected an array. Got: %s', \stdClass::class))
        )->during('getValue', [new \stdClass()]);
    }

    function it_throws_exception_during_get_value_when_value_not_contains_amount_key()
    {
        $this->shouldThrow(
            new \LogicException('Amount key not found')
        )->during('getValue', [[
            'key' => 'value',
        ]]);
    }

    function it_throws_exception_during_get_value_when_value_not_contains_unit_key()
    {
        $this->shouldThrow(
            new \LogicException('Unit key not found')
        )->during('getValue', [[
            'amount' => 23,
            'key' => 'value',
        ]]);
    }

    function it_returns_23_inches_value_from_get_value()
    {
        $this->getValue([
            'amount' => 23,
            'unit' => 'INCH',
        ])->shouldReturn('23 INCH');
    }
}
