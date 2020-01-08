<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class ProductOptionValueHandlerSpec extends ObjectBehavior
{
    private const VARIANT_CODE = 'variant-code';

    private const PRODUCT_CODE = 'product-code';

    private const OPTION_CODE = 'option-code';

    private const VALUE_CODE = 'value-code';

    private const EN_LABEL = 'EN Label';

    private const IT_LABEL = 'IT Label';

    function let(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductOptionInterface $productOption,
        ApiClientInterface $apiClient,
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        RepositoryInterface $productOptionValueRepository
    ) {
        $productVariant->getCode()->willReturn(self::VARIANT_CODE);
        $productVariant->getProduct()->willReturn($product);
        $product->getCode()->willReturn(self::PRODUCT_CODE);
        $product->getOptions()->willReturn(new ArrayCollection([$productOption->getWrappedObject()]));
        $productOption->getCode()->willReturn(self::OPTION_CODE);
        $apiClient
            ->findAttributeOption(self::OPTION_CODE, self::VALUE_CODE)
            ->willReturn(
                [
                    'code' => self::VALUE_CODE,
                    'attribute' => self::OPTION_CODE,
                    'sort_order' => 4,
                    'labels' => ['en_US' => self::EN_LABEL, 'it_IT' => self::IT_LABEL],
                ]
            );
        $productOptionRepository->findOneBy(['code' => self::OPTION_CODE])->willReturn($productOption);
        $this->beConstructedWith(
            $apiClient,
            $productOptionRepository,
            $productOptionValueFactory,
            $productOptionValueTranslationFactory,
            $productOptionValueRepository
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ProductOptionValueHandler::class);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::OPTION_CODE, [])->shouldReturn(true);
    }

    function it_does_not_support_other_type_of_subject()
    {
        $this->supports(new \stdClass(), self::OPTION_CODE, [])->shouldReturn(false);
    }

    function it_supports_option_code_of_parent_product(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::OPTION_CODE, [])->shouldReturn(true);
    }

    function it_does_not_support_different_attribute_than_option_code_of_parent_product(
        ProductVariantInterface $productVariant
    ) {
        $this->supports($productVariant, 'other-attribute', [])->shouldReturn(false);
    }

    function it_throws_exception_during_handle_when_subject_is_not_product_variant()
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'This option value handler only supports instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::OPTION_CODE, []]);
    }

    function it_throws_exception_during_handle_when_value_has_an_invalid_number_of_values(
        ProductVariantInterface $productVariant
    ) {
        $value = [
            [
                'scope' => null,
                'locale' => 'it_IT',
                'data' => 'IT-value',
            ],
            [
                'scope' => null,
                'locale' => 'en_US',
                'data' => 'EN-value',
            ],
        ];

        $this->shouldThrow(
            new \RuntimeException(
                sprintf(
                    'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                    '"%s". More than one value is set for this attribute on Akeneo but this handler only supports ' .
                    'single value for product options.',
                    self::VARIANT_CODE,
                    self::PRODUCT_CODE,
                    self::OPTION_CODE
                )
            )
        )->during('handle', [$productVariant, self::OPTION_CODE, $value]);
    }

    function it_throws_an_exception_during_handle_if_attribute_option_does_not_exists_on_akeneo(
        ProductVariantInterface $productVariant,
        ApiClientInterface $apiClient
    ) {
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => self::VALUE_CODE,
            ],
        ];
        $apiClient->findAttributeOption(self::OPTION_CODE, self::VALUE_CODE)->willReturn(null);

        $this->shouldThrow(
            new \RuntimeException(
                sprintf(
                    'Cannot handle option value on Akeneo product "%s", the option of the parent product "%s" is ' .
                    '"%s". The option value for this variant is "%s" but there is no such option on Akeneo.',
                    self::VARIANT_CODE,
                    self::PRODUCT_CODE,
                    self::OPTION_CODE,
                    self::VALUE_CODE
                )
            )
        )->during('handle', [$productVariant, self::OPTION_CODE, $value]);
    }

    function it_throws_an_exception_if_product_option_does_not_exists_on_sylius(
        ProductVariantInterface $productVariant,
        ProductOptionRepositoryInterface $productOptionRepository
    ) {
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => self::VALUE_CODE,
            ],
        ];
        $productOptionRepository->findOneBy(['code' => self::OPTION_CODE])->willReturn(null);

        $this->shouldThrow(
            new \RuntimeException(
                sprintf(
                    'Cannot import Akeneo product "%s", the option of the parent product "%s" is ' .
                    '"%s" but this doesn\'t exist on Sylius and it should (it should was created during Product model ' .
                    'import).',
                    self::VARIANT_CODE,
                    self::PRODUCT_CODE,
                    self::OPTION_CODE
                )
            )
        )->during('handle', [$productVariant, self::OPTION_CODE, $value]);
    }

    function it_returns_product_option_value_from_factory_with_all_translation_if_does_not_already_exists(
        ProductVariantInterface $productVariant,
        ProductOptionValueInterface $productOptionValue,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        ProductOptionValueTranslationInterface $englishProductOptionValueTranslation,
        ProductOptionValueTranslationInterface $italianProductOptionValueTranslation,
        ProductOptionInterface $productOption,
        RepositoryInterface $productOptionValueRepository
    ) {
        $value = [
            [
                'scope' => null,
                'locale' => null,
                'data' => self::VALUE_CODE,
            ],
        ];
        $productOptionValueFactory->createNew()->willReturn($productOptionValue);
        $productOptionValueTranslationFactory->createNew()->willReturn(
            $englishProductOptionValueTranslation,
            $italianProductOptionValueTranslation
        );

        $this->handle($productVariant, self::OPTION_CODE, $value);

        $productOptionValue->setCode('option-code_value-code')->shouldHaveBeenCalled();
        $productOptionValue->setOption($productOption)->shouldHaveBeenCalled();
        $englishProductOptionValueTranslation->setLocale('en_US')->shouldHaveBeenCalled();
        $englishProductOptionValueTranslation->setValue(self::EN_LABEL)->shouldHaveBeenCalled();
        $italianProductOptionValueTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $italianProductOptionValueTranslation->setValue(self::IT_LABEL)->shouldHaveBeenCalled();
        $productVariant->addOptionValue($productOptionValue)->shouldHaveBeenCalled();
        $productOptionValueRepository->add($productOptionValue)->shouldHaveBeenCalled();
    }
}