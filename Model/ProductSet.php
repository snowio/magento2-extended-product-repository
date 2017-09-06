<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductInterface;

class ProductSet
{
    /** @var ProductInterface[] */
    private $products = [];

    public function __construct(array $products)
    {
        $this->products = array_values($products);
    }

    public function getIds() : array
    {
        return array_map(function (ProductInterface $product) {
            return $product->getId();
        }, $this->products);
    }

    public function getSkus() : array
    {
        return array_map(function (ProductInterface $product) {
            return $product->getSku();
        }, $this->products);
    }
}