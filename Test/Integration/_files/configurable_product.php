<?php
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

//create attributes color and size and assign them the same attribute set id
$optionAttributes = [
    'test_colour' => [ // options
        $objectManager->create(AttributeOptionInterface::class)->setStoreLabels([
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(0)
                ->setLabel('Red'),
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(1)
                ->setLabel('Rot'),
        ]),
        $objectManager->create(AttributeOptionInterface::class)->setStoreLabels([
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(0)
                ->setLabel('Blue'),
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(1)
                ->setLabel('Blau'),
        ]),
    ],
    'test_size' => [
        $objectManager->create(AttributeOptionInterface::class)->setStoreLabels([
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(0)
                ->setLabel('Small'),
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(1)
                ->setLabel('Klein'),
        ]),
        $objectManager->create(AttributeOptionInterface::class)->setStoreLabels([
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(0)
                ->setLabel('large'),
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(1)
                ->setLabel('groß'),
        ]),
    ],
];

function persistAttributeOptions(
    string $backendType,
    string $frontendInput,
    array $optionAttributes,
    ObjectManagerInterface $objectManager
) {
    /** @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository */
    $attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
    /** @var \Magento\Catalog\Api\ProductAttributeOptionManagementInterface $attributeOptionManager */
    $attributeOptionManager = $objectManager->get(ProductAttributeOptionManagementInterface::class);
    /** @var ProductAttributeManagementInterface $attributeManager */
    $attributeManager = $objectManager->get(ProductAttributeManagementInterface::class);
    foreach ($optionAttributes as $attributeCode => $attributeOptions) {
        /** @var \Magento\Catalog\Api\Data\ProductAttributeInterface $productAttribute */
        $productAttribute = $objectManager->create(ProductAttributeInterface::class);
        $productAttribute->setAttributeCode($attributeCode);
        $productAttribute->setBackendType($backendType);
        $productAttribute->setFrontendInput($frontendInput);
        /** @var AttributeFrontendLabelInterface $frontEndLabelsDefaultStore */
        $frontEndLabelsDefaultStore = $objectManager->create(AttributeFrontendLabelInterface::class);
        $frontEndLabelsDefaultStore->setLabel("$attributeCode Label");
        $frontEndLabelsDefaultStore->setStoreId(0);
        /** @var AttributeFrontendLabelInterface $frontEndLabelsDefaultStore */
        $frontEndLabelsTestStore = $objectManager->create(AttributeFrontendLabelInterface::class);
        $frontEndLabelsTestStore->setLabel("$attributeCode Etikette");
        $frontEndLabelsTestStore->setStoreId(1);
        $productAttribute->setFrontendLabels([$frontEndLabelsDefaultStore, $frontEndLabelsTestStore]);
        $productAttribute->setSourceModel('eav/entity_attribute_source_table');
        $productAttribute->setIsUserDefined(true);
        $productAttribute->setOptions($attributeOptions);
        $attributeRepository->save($productAttribute);
        $attributeManager->assign(4, 7, $attributeCode, 1);
    }
}

persistAttributeOptions('int', 'select', $optionAttributes, $objectManager);

//create a test product that the configurable products will link to
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var ProductInterface$productFactory */
$product = $objectManager->get(ProductInterface::class);
/** @var \Magento\Catalog\Model\Product $product */
$product->setTypeId('simple');
$product->setExtensionAttributes($objectManager->get(ProductExtensionFactory::class)->create()->setAttributeOptionLabels([
    'test_colour' => $objectManager->create(AttributeInterface::class)
        ->setAttributeCode('test_colour')
        ->setValue('Rot'),
    'test_size' => $objectManager->create(AttributeInterface::class)
        ->setAttributeCode('test_size')
        ->setValue('groß'),
]));
$product->setSku('test-product');
$product->setName('Test Product');
$product->setPrice(1.00);
$product->setAttributeSetId(4);
$productRepository->save($product);
