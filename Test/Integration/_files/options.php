<?php
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

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

$multiSelectOptionAttributes = [
    'test_terrain' => [
        $objectManager->create(AttributeOptionInterface::class)->setStoreLabels([
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(0)
                ->setLabel('Desert'),
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(1)
                ->setLabel('Wüste'),
        ]),
        $objectManager->create(AttributeOptionInterface::class)->setStoreLabels([
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(0)
                ->setLabel('Forest'),
            $objectManager->create(AttributeOptionLabelInterface::class)
                ->setStoreId(1)
                ->setLabel('Wald'),
        ]),
    ]
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
        echo "{$attributeCode} Saved";
        $attributeManager->assign(4, 7, $attributeCode, 1);
    }
}

persistAttributeOptions('int', 'select', $optionAttributes, $objectManager);
persistAttributeOptions('int', 'multiselect', $multiSelectOptionAttributes, $objectManager);
