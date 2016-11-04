<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory;

class ProductDataMapper
{
    /** @var AttributeInterface[] */
    private $attributesById;
    /** @var AttributeInterface[] */
    private $attributesByCode;
    private $optionLabelCollection;
    private $productRepository;
    private $optionValueFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        OptionValueInterfaceFactory $optionValueFactory,
        array $attributes
    ) {
        $attributeIds = array_map(
            function (AttributeInterface $attribute) {
                return $attribute->getAttributeId();
            },
            $attributes
        );
        $this->attributesById = array_combine($attributeIds, $attributes);
        $attributeCodes = array_map(
            function (AttributeInterface $attribute) {
                return $attribute->getAttributeCode();
            },
            $attributes
        );
        $this->attributesByCode = array_combine($attributeCodes, $attributes);

        $this->optionLabelCollection = AttributeOptionLabelCollection::create($attributes);
        $this->productRepository = $productRepository;
        $this->optionValueFactory = $optionValueFactory;
    }

    public function mapProductDataForSave(ProductInterface $product)
    {
        $this->optionLabelCollection->replaceOptionLabelsWithAttributeValues($product);

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
                $attributeId = $this->getAttributeId($attributeCode);
                $option->setAttributeId($attributeId);
            }
            if (null === $option->getLabel()) {
                $option->setLabel($this->getAttributeLabel($option->getAttributeId()));
            }
        }

        $this->ensureProductOptionsHaveValueIndexes($options, $simpleProductIds, $productRepository);
    }

    private function mapConfigurableProductLinkedSkus(
        ProductExtensionInterface $extensionAttributes,
        CachedProductRepository $productRepository
    ) {
        $linkedIds = $extensionAttributes->getConfigurableProductLinks() ?? [];
        $linkedSkus = $extensionAttributes->getConfigurableProductLinkedSkus() ?? [];
        $newlyLinkedProducts = $productRepository->findBySku($linkedSkus);
        $linkedIds = array_unique(array_merge($linkedIds, $newlyLinkedProducts->getIds()));
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

        $simpleProducts = $productRepository->findById($simpleProductIds);

        foreach ($optionsWithoutValues as $option) {
            $attributeCode = $this->getAttributeCode($option->getAttributeId());
            $distinctValueIndexes = $simpleProducts->getDistinctCustomAttributeValues($attributeCode);
            $valueObjects = array_map(function (int $valueIndex) use ($attributeCode) {
                return $this->optionValueFactory->create()->setValueIndex($valueIndex);
            }, $distinctValueIndexes);
            $option->setValues($valueObjects);
        }
    }

    private function getAttributeId(string $attributeCode) : int
    {
        if (!isset($this->attributesByCode[$attributeCode])) {
            throw new \RuntimeException("No attribute exists with the code '$attributeCode'.");
        }

        return $this->attributesByCode[$attributeCode]->getAttributeId();
    }

    private function getAttributeCode(string $attributeId) : string
    {
        if (!isset($this->attributesById[$attributeId])) {
            throw new \RuntimeException("No attribute exists with the ID '$attributeId'.");
        }

        return $this->attributesById[$attributeId]->getAttributeCode();
    }

    private function getAttributeLabel(int $attributeId) : string
    {
        if (!isset($this->attributesById[$attributeId])) {
            throw new \RuntimeException("No attribute exists with the ID '$attributeId'.");
        }

        return $this->attributesById[$attributeId]->getDefaultFrontendLabel();
    }
}
