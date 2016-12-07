<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class AttributeOptionLabelCollection
{
    private $codeKeyAttributes;
    private $attributeValues;

    private function __construct(array $attributes)
    {
        $this->codeKeyAttributes = $attributes;
        $this->attributeValues = [];
    }

    /**
     * @param ProductAttributeInterface[] $attributes
     * @return AttributeOptionLabelCollection
     */
    public static function create(array $attributes) : AttributeOptionLabelCollection
    {
        $codeKeyAttributes = [];
        foreach ($attributes as $attribute) {
            $codeKeyAttributes[$attribute->getAttributeCode()] = $attribute;
        }
        return new AttributeOptionLabelCollection($codeKeyAttributes);
    }

    public function replaceOptionLabelsWithAttributeValues(ProductInterface $product)
    {
        if (!$extensionAttributes = $product->getExtensionAttributes()) {
            return;
        }

        if (!$extensionAttributes->getAttributeOptionLabels()) {
            return;
        }

        foreach ($extensionAttributes->getAttributeOptionLabels() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $value = $attribute->getValue();

            if (null === $value) {
                continue;
            }

            if (!isset($this->attributeValues[$attributeCode])) {
                if (!isset($this->codeKeyAttributes[$attributeCode])) {
                    continue;
                }

                /** @var ProductAttributeInterface $productAttribute */
                $productAttribute = $this->codeKeyAttributes[$attributeCode];
                $options = $productAttribute->getOptions();
                if (null === $options) {
                    continue;
                }

                foreach ($options as $option) {
                    $this->attributeValues[$attributeCode][(string)$option->getLabel()] = $option->getValue();
                }
            }

            $value = $this->getAttributeValue($attributeCode, $value);

            $product->setCustomAttribute($attributeCode, $value);
        }
    }

    private function getAttributeValue(string $attributeCode, $labelOrLabels)
    {
        if (is_array($labelOrLabels)) {
            $values = [];

            foreach ($labelOrLabels as $label) {
                $values[] = $this->getValueForLabel($attributeCode, $label);
            }

            return $values;
        }

        return $this->getValueForLabel($attributeCode, $labelOrLabels);
    }

    private function getValueForLabel(string $attributeCode, string $label)
    {
        $value = $this->attributeValues[$attributeCode][$label] ?? null;

        if (null === $value) {
            throw new \RuntimeException('Missing label ' . $label . ' for ' . $attributeCode);
        }

        return $value;
    }
}