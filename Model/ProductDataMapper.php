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
        $this->mapConfigurableProductOptions($extensionAttributes);
    }

    private function mapConfigurableProductOptions(ProductExtensionInterface $extensionAttributes)
    {
        if (null === $options = $extensionAttributes->getConfigurableProductOptions()) {
            return;
        }

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

        $this->ensureProductOptionsHaveValueIndexes($options);
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
    private function ensureProductOptionsHaveValueIndexes(array $configurableProductOptions) {
        foreach ($configurableProductOptions as $option) {
            if ($option->getValues() === null) {
                $value = $this->optionValueFactory->create()->setValueIndex(1);
                $option->setValues([$value]);
            }
        }
    }
}
