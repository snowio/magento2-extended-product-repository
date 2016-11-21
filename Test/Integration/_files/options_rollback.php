<?php
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$attributesCodes = ['test_colour', 'test_size'];
/** @var ProductAttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
foreach ($attributesCodes as $attributesCode) {
    try {
        $productAttribute = $attributeRepository->deleteById($attributesCode);
    } catch (Exception $e) {
    }
}
