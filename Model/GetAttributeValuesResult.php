<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductInterface;

class GetAttributeValuesResult
{
    private $attributeValues;
    private $missingLabels;

    public function __construct(array $attributeValues, array $missingLabels)
    {
        $this->attributeValues = $attributeValues;
        $this->missingLabels = $missingLabels;
    }

    public function getMissingLabels() : array
    {
        return $this->missingLabels;
    }

    public function assertNoMissingLabels()
    {
        if ($this->getMissingLabels()) {
            throw new \RuntimeException;
        }
    }

    public function applyValuesToProduct(ProductInterface $product) : ProductInterface
    {
        $product = clone $product;

        foreach ($this->attributeValues as $attributeCode => $value) {
            $product->setCustomAttribute($attributeCode, $value);
        }

        return $product;
    }
}
