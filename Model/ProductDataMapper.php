<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ProductDataMapper
{
    /** @var AttributeInterface[] */
    private $attributesById;
    /** @var AttributeInterface[] */
    private $attributesByCode;
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
        $this->productRepository = $productRepository;
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
                if (!$attributeId = $this->getAttributeId($attributeCode)) {
                    throw new LocalizedException(new Phrase('No attribute exists with code %1.', [$attributeCode]));
                }
                $option->setAttributeId($attributeId);
            }
            if (null === $option->getLabel()) {
                $option->setLabel($this->getAttributeById($option->getAttributeId())->getDefaultFrontendLabel());
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
            $attributeCode = $this->getAttributeById($option->getAttributeId())->getAttributeCode();
            $distinctValueIndexes = $simpleProducts->getDistinctCustomAttributeValues($attributeCode);
            $valueObjects = array_map(function (int $valueIndex) use ($attributeCode) {
                return $this->optionValueFactory->create()->setValueIndex($valueIndex);
            }, $distinctValueIndexes);
            $option->setValues($valueObjects);
        }
    }

    private function getAttributeId(string $attributeCode)
    {
        if (!isset($this->attributesByCode[$attributeCode])) {
            return null;
        }

        return $this->attributesByCode[$attributeCode]->getAttributeId();
    }

    private function getAttributeById(string $attributeId) : AttributeInterface
    {
        if (!isset($this->attributesById[$attributeId])) {
            throw new LocalizedException(new Phrase('No attribute exists with ID %1.', [$attributeId]));
        }

        return $this->attributesById[$attributeId];
    }
}
