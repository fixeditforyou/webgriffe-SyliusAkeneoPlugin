<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class TranslatablePropertyValueHandler implements ValueHandlerInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var FactoryInterface */
    private $translationFactory;

    /** @var TranslationLocaleProviderInterface */
    private $localeProvider;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $translationPropertyPath;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        TranslationLocaleProviderInterface $localeProvider,
        string $akeneoAttributeCode,
        string $translationPropertyPath
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->translationFactory = $productTranslationFactory;
        $this->localeProvider = $localeProvider;
        $this->akeneoAttributeCode = $akeneoAttributeCode;
        $this->translationPropertyPath = $translationPropertyPath;
    }

    /**
     * @param mixed $subject
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return ($subject instanceof ProductInterface || $subject instanceof ProductVariantInterface) &&
            $attribute === $this->akeneoAttributeCode;
    }

    /**
     * @param mixed $subject
     */
    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductInterface && !$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This translatable property value handler only support instances of %s or %s, %s given.',
                    ProductInterface::class,
                    ProductVariantInterface::class,
                    is_object($subject) ? get_class($subject) : gettype($subject)
                )
            );
        }
        foreach ($value as $item) {
            $localeCode = $item['locale'];
            if (!$localeCode) {
                $this->setValueOnAllTranslations($subject, $item);

                continue;
            }
            $translation = $this->getOrCreateNewProductTranslation($subject, $localeCode);
            $this->setValueWithFallback($translation, $item['data']);
        }
    }

    private function setValueOnAllTranslations(TranslatableInterface $subject, array $value): void
    {
        foreach ($this->localeProvider->getDefinedLocalesCodes() as $localeCode) {
            $translation = $this->getOrCreateNewProductTranslation($subject, $localeCode);
            $this->setValueWithFallback($translation, $value['data']);
        }
    }

    /**
     * @param mixed $value
     */
    private function setValueWithFallback(TranslationInterface $translation, $value): void
    {
        if ($translation instanceof ProductVariantTranslationInterface) {
            $variant = $translation->getTranslatable();
            Assert::isInstanceOf($variant, ProductVariantInterface::class);
            if (!$this->propertyAccessor->isWritable($translation, $this->translationPropertyPath)) {
                $product = $variant->getProduct();
                Assert::isInstanceOf($product, ProductInterface::class);
                $translation = $this->getOrCreateNewProductTranslation($product, $translation->getLocale());
            }
        }
        $this->propertyAccessor->setValue(
            $translation,
            $this->translationPropertyPath,
            $value
        );
    }

    private function getOrCreateNewProductTranslation(
        TranslatableInterface $subject,
        string $localeCode
    ): TranslationInterface {
        $translation = $subject->getTranslation($localeCode);
        if ($translation->getLocale() !== $localeCode) {
            $translation = $this->translationFactory->createNew();
            Assert::isInstanceOf($translation, TranslationInterface::class);
            /** @var TranslationInterface $translation */
            $translation->setLocale($localeCode);
            $subject->addTranslation($translation);
        }

        return $translation;
    }
}
