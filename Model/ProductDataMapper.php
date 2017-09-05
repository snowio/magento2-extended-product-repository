<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ProductDataMapper
{
    private $productRepository;
    private $attributeRepository;
    private $optionValueFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        AttributeRepository $attributeRepository,
        OptionValueInterfaceFactory $optionValueFactory
    ) {
        $this->productRepository = $productRepository;
        $this->attributeRepository = $attributeRepository;
        $this->optionValueFactory = $optionValueFactory;
    }

    public function mapProductDataForSave(ProductInterface $product)
    {
        if (!$extensionAttributes = $product->getExtensionAttributes()) {
            return;
        }

        $cachedProductRepository = new CachedProductRepository($this->productRepository);
        $this->mapConfigurableProductLinkedSkus($extensionAttributes, $cachedProductRepository);
        $this->mapConfigurableProductOptions($extensionAttributes, $cachedProductRepository);
    }

    private function mapConfigurableProductOptions(
        ProductExtensionInterface $extensionAttributes,
        CachedProductRepository $productRepository
    ) {
        if (null === $options = $extensionAttributes->getConfigurableProductOptions()) {
            return;
        }

        $simpleProductIds = $extensionAttributes->getConfigurableProductLinks() ?? [];

        foreach ($options as $option) {
            $_extensionAttributes = $option->getExtensionAttributes();
            if ($_extensionAttributes && null !== $attributeCode = $_extensionAttributes->getAttributeCode()) {
                if (!$attributeId = $this->attributeRepository->getAttributeId($attributeCode)) {
                    throw new LocalizedException(new Phrase('No attribute exists with code %1.', [$attributeCode]));
                }
                $option->setAttributeId($attributeId);
            }
            if (null === $option->getLabel()) {
                $option->setLabel($this->attributeRepository->getDefaultFrontendLabel($option->getAttributeId()));
            }
        }

        $this->ensureProductOptionsHaveValueIndexes($options, $simpleProductIds, $productRepository);
    }

    private function mapConfigurableProductLinkedSkus(
        ProductExtensionInterface $extensionAttributes,
        CachedProductRepository $productRepository
    ) {
        $configurableProductLinks = $extensionAttributes->getConfigurableProductLinks();
        $configurableProductLinkedSkus = $extensionAttributes->getConfigurableProductLinkedSkus();

        if (!isset($configurableProductLinkedSkus)) {
            if (!isset($configurableProductLinks)) {
                return;
            }
            $configurableProductLinkedSkus = [];
        } else {
            $configurableProductLinkedSkus = \array_unique($configurableProductLinkedSkus);
        }

        $newlyLinkedProducts = $productRepository->findBySku($configurableProductLinkedSkus);

        $skusOfNewlyLinkedProducts = $newlyLinkedProducts->getSkus();
        $missingSkus = \array_diff($configurableProductLinkedSkus, $skusOfNewlyLinkedProducts);
        if (!empty($missingSkus)) {
            throw new LocalizedException(new Phrase('Associated simple products do not exist: %1.', [\implode(', ', $missingSkus)]));
        }

        $linkedIds = array_unique(array_merge($configurableProductLinks ?? [], $newlyLinkedProducts->getIds()));
        $extensionAttributes->setConfigurableProductLinks($linkedIds);
    }

    /**
     * @param OptionInterface[] $configurableProductOptions
     * @param int[] $simpleProductIds
     */
    private function ensureProductOptionsHaveValueIndexes(
        array $configurableProductOptions,
        array $simpleProductIds,
        CachedProductRepository $productRepository
    ) {
        $optionsWithoutValues = array_filter($configurableProductOptions, function (OptionInterface $option) {
            return null === $option->getValues();
        });

        if (empty($optionsWithoutValues)) {
            return;
        }

        $simpleProductIds = \array_unique($simpleProductIds);
        $simpleProducts = $productRepository->findById($simpleProductIds);

        $foundIds = $simpleProducts->getIds();
        if (!empty(\array_diff($simpleProductIds, $foundIds))) {
            throw new \RuntimeException();
        }

        foreach ($optionsWithoutValues as $option) {
            $attributeCode = $this->attributeRepository->getAttributeCode($option->getAttributeId());
            $distinctValues = $simpleProducts->getDistinctCustomAttributeValues($attributeCode);
            $valueObjects = array_map(function ($value) use ($attributeCode) {
                return $this->optionValueFactory->create()->setValueIndex($value);
            }, $distinctValues);
            $option->setValues($valueObjects);
        }
    }
}
