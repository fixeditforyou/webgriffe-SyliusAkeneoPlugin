<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webmozart\Assert\Assert;

final class ProductOptionsResolver implements ProductOptionsResolverInterface
{
    /** @var ApiClientInterface */
    private $apiClient;

    /** @var ProductOptionRepositoryInterface */
    private $productOptionRepository;

    /** @var FactoryInterface */
    private $productOptionFactory;

    /** @var FactoryInterface */
    private $productOptionTranslationFactory;

    public function __construct(
        ApiClientInterface $apiClient,
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionFactory,
        FactoryInterface $productOptionTranslationFactory
    ) {
        $this->apiClient = $apiClient;
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionTranslationFactory = $productOptionTranslationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $akeneoProduct): array
    {
        /** @var string|null $parentCode */
        $parentCode = $akeneoProduct['parent'] ?? null;
        if (!$parentCode) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot resolve product options for Akeneo product "%s" because it does not belong to any ' .
                    'product model.',
                    $akeneoProduct['identifier'] ?? '?'
                )
            );
        }
        $productResponse = $this->apiClient->findProductModel($parentCode);
        if (!$productResponse) {
            throw new \RuntimeException(sprintf('Cannot find product model "%s" on Akeneo.', $parentCode));
        }
        $familyCode = $productResponse['family'];
        $familyVariantCode = $productResponse['family_variant'];
        $familyVariantResponse = $this->apiClient->findFamilyVariant($familyCode, $familyVariantCode);
        if (!$familyVariantResponse) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot find family variant "%s" within family "%s" on Akeneo.',
                    $familyVariantCode,
                    $familyCode
                )
            );
        }
        $productOptions = [];
        foreach ($familyVariantResponse['variant_attribute_sets'][0]['axes'] as $position => $attributeCode) {
            /** @var ProductOptionInterface|null $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);
            if ($productOption) {
                $productOptions[] = $productOption;

                continue;
            }
            $productOption = $this->productOptionFactory->createNew();
            Assert::isInstanceOf($productOption, ProductOptionInterface::class);
            $productOption->setCode($attributeCode);
            $productOption->setPosition($position);
            $attributeResponse = $this->apiClient->findAttribute($attributeCode);
            if (!$attributeResponse) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot resolve product options for product "%s" because one of its variant attributes, ' .
                        '"%s", cannot be found on Akeneo.',
                        $akeneoProduct['identifier'],
                        $attributeCode
                    )
                );
            }
            foreach ($attributeResponse['labels'] as $locale => $label) {
                $productOptionTranslation = $productOption->getTranslation($locale);
                if ($productOptionTranslation->getLocale() === $locale) {
                    $productOptionTranslation->setName($label);

                    continue;
                }
                /** @var ProductOptionTranslationInterface $newProductOptionTranslation */
                $newProductOptionTranslation = $this->productOptionTranslationFactory->createNew();
                $newProductOptionTranslation->setLocale($locale);
                $newProductOptionTranslation->setName($label);
                $productOption->addTranslation($newProductOptionTranslation);
            }
            $this->productOptionRepository->add($productOption);
            $productOptions[] = $productOption;
        }

        return $productOptions;
    }
}
