<?php
namespace SnowIO\ExtendedProductRepository\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use SnowIO\ExtendedProductRepository\Model\ProductDataMapper;
use SnowIO\ExtendedProductRepository\Model\ProductDataMapperFactory;

class ExtendedProductRepositoryPlugin
{
    private $dataMapperFactory;
    private $productAttributeManagement;

    public function __construct(
        ProductDataMapperFactory $dataMapperFactory,
        ProductAttributeManagementInterface $productAttributeManagement
    ) {
        $this->dataMapperFactory = $dataMapperFactory;
        $this->productAttributeManagement = $productAttributeManagement;
    }

    public function beforeSave(ProductRepositoryInterface $productRepository,ProductInterface $product, $saveOptions = false)
    {
        $attributeSetId = $product->getAttributeSetId();
        $attributes = $this->productAttributeManagement->getAttributes($attributeSetId);
        /** @var ProductDataMapper $dataMapper */
        $dataMapper = $this->dataMapperFactory->create(['attributes' => $attributes]);
        $dataMapper->mapProductDataForSave($product);

        return [$product, $saveOptions];
    }

}
