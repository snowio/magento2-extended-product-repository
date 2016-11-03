<?php
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$attributesCodes = ['test_colour', 'test_size'];
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
try {
    if (null !== $productRepository->get('test-product')->getId()) {
        $productRepository->deleteById('test-product');
    }
    /** @var ProductAttributeRepositoryInterface $attributeRepository */
    $attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
    foreach ($attributesCodes as $attributesCode) {
    $productAttribute = $attributeRepository->deleteById($attributesCode);
    }
} catch (Exception $e) {

}