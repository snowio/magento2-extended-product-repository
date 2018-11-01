<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Catalog\Api\Data\SpecialPriceInterface;
use Magento\CatalogStaging\Model\ResourceModel\Product\Price\SpecialPrice;

class ProductDataMapper
{
    private $attributeRepository;
    private $optionValueFactory;
    private $skuResolver;
    private $stagingSpecialPriceModel;

    public function __construct(
        AttributeRepository $attributeRepository,
        OptionValueInterfaceFactory $optionValueFactory,
        SkuResolver $skuResolver,
        SpecialPrice $stagingSpecialPriceModel
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->optionValueFactory = $optionValueFactory;
        $this->skuResolver = $skuResolver;
        $this->stagingSpecialPriceModel = $stagingSpecialPriceModel;
    }

    public function mapProductDataForSave(ProductInterface $product)
    {
        if (!$extensionAttributes = $product->getExtensionAttributes()) {
            return;
        }

        $this->mapConfigurableProductLinkedSkus($extensionAttributes);
        $this->mapConfigurableProductOptions($extensionAttributes);
        $this->mapProductSpecialPrices($extensionAttributes);
    }

    /**
     * Set special prices for product from extension attribute payload (if exists).
     *
     * @author Liam Toohey (lt@amp.co)
     * @param ProductExtensionInterface $extensionAttributes
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function mapProductSpecialPrices(ProductExtensionInterface $extensionAttributes)
    {
        if (null === $prices = $extensionAttributes->getSpecialPrice()) {
            return;
        }

        foreach ($prices as $price) {
            if (!$this->validatePricePayload($price)) {
                throw new LocalizedException(new Phrase(
                    'Missing data from special_price extension attribute payload'
                ));
            }
        }

        /**
         * $prices = [
         *     \Magento\Catalog\Api\Data\SpecialPriceInterface,
         *     \Magento\Catalog\Api\Data\SpecialPriceInterface,
         *     ...
         * ]
         */
        $this->stagingSpecialPriceModel->update($prices);
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

    private function mapConfigurableProductLinkedSkus(ProductExtensionInterface $extensionAttributes)
    {
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

        $matchedProductIds = $this->skuResolver->getProductIds($configurableProductLinkedSkus);

        $matchedSkus = \array_keys($matchedProductIds);
        $missingSkus = \array_diff($configurableProductLinkedSkus, $matchedSkus);
        if (!empty($missingSkus)) {
            throw new LocalizedException(new Phrase('Associated simple products do not exist: %1.', [\implode(', ', $missingSkus)]));
        }

        $linkedIds = \array_unique(\array_merge($configurableProductLinks ?? [], $matchedProductIds));
        $extensionAttributes->setConfigurableProductLinks($linkedIds);
    }

    /**
     * @param OptionInterface[] $configurableProductOptions
     * @param int[] $simpleProductIds
     */
    private function ensureProductOptionsHaveValueIndexes(array $configurableProductOptions)
    {
        foreach ($configurableProductOptions as $option) {
            if ($option->getValues() === null) {
                $value = $this->optionValueFactory->create()->setValueIndex(1);
                $option->setValues([$value]);
            }
        }
    }

    /**
     * @author Liam Toohey (lt@amp.co)
     * @param SpecialPriceInterface $price
     * @return bool
     */
    private function validatePricePayload(SpecialPriceInterface $price)
    {
        if (
            !$price->getPrice() ||
            !$price->getStoreId() ||
            !$price->getSku() ||
            !$price->getPriceFrom() ||
            !$price->getPriceTo()
        ) {
            return false;
        }

        return true;
    }
}
