<?php
namespace SnowIO\ExtendedProductRepository\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use SnowIO\ExtendedProductRepository\Model\ProductDataMapper;

class ExtendedProductRepositoryPlugin
{
    private $dataMapper;

    public function __construct(ProductDataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function beforeSave(
        ProductRepositoryInterface $productRepository,
        ProductInterface $product,
        $saveOptions = false
    ) {
        $this->dataMapper->mapProductDataForSave($product);
        return [$product, $saveOptions];
    }
}
