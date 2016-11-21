<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductInterface;

class ProductSet implements \IteratorAggregate
{
    /** @var ProductInterface[] */
    private $products = [];

    public function __construct(array $products)
    {
        $this->products = array_values($products);
    }

    public function toArray() : array
    {
        return $this->products;
    }

    public function getIterator() : \Iterator
    {
        foreach ($this->products as $product) {
            yield $product;
        }
    }

    public function getIds() : array
    {
        return array_map(function (ProductInterface $product) {
            return $product->getId();
        }, $this->products);
    }

    public function getDistinctCustomAttributeValues(string $attributeCode) : array
    {
        $productAttributeValues = [];
        foreach ($this->products as $product) {
            if ($attribute = $product->getCustomAttribute($attributeCode)) {
                $value = $attribute->getValue();
                if (null !== $value) {
                    $productAttributeValues[] = $value;
                }
            }
        }

        return array_unique($productAttributeValues);
    }
}
