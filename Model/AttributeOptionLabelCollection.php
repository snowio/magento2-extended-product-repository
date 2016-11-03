<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class AttributeOptionLabelCollection
{
    private $attributeValues;

    private function __construct(array $attributeValues)
    {
        $this->attributeValues = $attributeValues;
    }

    /**
     * @param ProductAttributeInterface[] $attributes
     */
    public static function create(array $attributes) : AttributeOptionLabelCollection
    {
        $attributeValues = [];

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $options = $attribute->getOptions();
            if (null === $options) {
                continue;
            }

            $attributeValues[$attributeCode] = [];

            foreach ($options as $option) {
                $attributeValues[$attributeCode][(string)$option->getLabel()] = $option->getValue();
            }
        }

        return new AttributeOptionLabelCollection($attributeValues);
    }

    public function replaceOptionLabelsWithAttributeValues(ProductInterface $product)
    {
        if (!$extensionAttributes = $product->getExtensionAttributes()) {
            return;
        }

        foreach ($extensionAttributes->getAttributeOptionLabels() ?? [] as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $value = $attribute->getValue();

            if (isset($this->attributeValues[$attributeCode])) {
                // this attribute supports options
                if (null !== $value) {
                    $value = $this->getAttributeValue($attributeCode, $value);
                }
            }

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
