<?php

namespace SnowIO\ExtendedProductRepository\Test\Integration\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use SnowIO\ExtendedProductRepository\Test\TestCase;

class ConfigurableProductMappingTest extends TestCase
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    private \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository;

    private \Magento\Framework\Api\ExtensionAttributesFactory $extensionAttributesFactory;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->attributeRepository = $this->objectManager->get(ProductAttributeRepositoryInterface::class);
        $this->extensionAttributesFactory = $this->objectManager->get(ExtensionAttributesFactory::class);
    }

    /**
     * @dataProvider getStandardCaseTestData
     */
    public function testStandardCase(ProductInterface $configurableProduct)
    {
        $this->productRepository->save($configurableProduct);
        $output = $this->productRepository->get($configurableProduct->getSku());
        $inputProductExtensionAttributes  = $configurableProduct->getExtensionAttributes();
        $inputConfigurableProductOptions  = $inputProductExtensionAttributes->getConfigurableProductOptions();
        $outputProductExtensionAttributes = $output->getExtensionAttributes();
        $outputConfigurableProductOptions = $outputProductExtensionAttributes->getConfigurableProductOptions();

        $inputAttributeIds = [];
        foreach ($inputConfigurableProductOptions as $configurableProductOption) {
            $inputAttributeIds[] = $this->getAttributeIdFromCode(
                $configurableProductOption
                    ->getExtensionAttributes()
                    ->getAttributeCode()
            );
        }

        $outputAttributeIds = [];
        foreach ($outputConfigurableProductOptions as $outputConfigurableProductOption) {
            $outputAttributeIds[] = $outputConfigurableProductOption->getAttributeId();
        }

        $this->assertEquals(0, count(array_diff($inputAttributeIds, $outputAttributeIds)));

        $inputProductIds = array_map(fn(string $sku) => $this->getProductIdFromSku($sku), $inputProductExtensionAttributes->getConfigurableProductLinkedSkus());


        $outputProductIds = $outputProductExtensionAttributes->getConfigurableProductLinks();

        $this->assertEquals(0, count(array_diff($inputProductIds, $outputProductIds)));
    }

    /**
     * @dataProvider getNonExistentSimpleProductTestData
     */
    public function testMissingSimpleProduct(ProductInterface $configurableProduct, array $nonExistentSkus)
    {
        try {
            $this->productRepository->save($configurableProduct);
        } catch (LocalizedException $e) {
            $rawMessage = $e->getRawMessage();
            $this->assertSame('Associated simple products do not exist: %1.', $rawMessage);
            $nonExistentSkusString = $e->getParameters()[0];
            $actualNonExistentSkus = \explode(', ', $nonExistentSkusString);
            $this->assertEquals($nonExistentSkus, $actualNonExistentSkus);
            return;
        }

        $this->fail('Expected exception was not thrown.');
    }

    private function getProductIdFromSku(string $sku)
    {
        $productId = $this->productRepository->get($sku)->getId();

        if ($productId === null) {
            throw new \RuntimeException('Product id does not exist');
        }

        return $productId;
    }

    private function getAttributeIdFromCode(string $attributeCode)
    {
        $attributeCode = $this->attributeRepository->get($attributeCode)->getAttributeId();
        if ($attributeCode === null) {
            throw new \RuntimeException('Attribute code does not exist');
        }
        return $attributeCode;
    }

    public function getStandardCaseTestData()
    {
        return [
            [ //test case 1
                $this->objectManager->create(ProductInterface::class)
                    ->setSku('test-configurable-product-1')
                    ->setTypeId('configurable')
                    ->setName('Test Configurable')
                    ->setAttributeSetId(4)
                    ->setExtensionAttributes(
                        $this->extensionAttributesFactory->create(\Magento\Catalog\Api\Data\ProductInterface::class)
                            ->setConfigurableProductOptions(
                                [
                                    $this->objectManager
                                        ->create(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class)
                                        ->setExtensionAttributes(
                                            $this->extensionAttributesFactory
                                                ->create(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class)
                                                ->setAttributeCode('test_size')
                                        ),
                                    $this->objectManager
                                        ->create(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class)
                                        ->setExtensionAttributes(
                                            $this->extensionAttributesFactory
                                                ->create(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class)
                                                ->setAttributeCode('test_colour')
                                        ),
                                ]
                            )
                            ->setConfigurableProductLinkedSkus(['test-product'])
                    )

            ]
        ];
    }

    public function getNonExistentSimpleProductTestData()
    {
        return [
            [ //test case 1
                $this->objectManager->create(ProductInterface::class)
                    ->setSku('test-configurable-product-2')
                    ->setTypeId('configurable')
                    ->setName('Test Configurable')
                    ->setAttributeSetId(4)
                    ->setExtensionAttributes(
                        $this->extensionAttributesFactory->create(\Magento\Catalog\Api\Data\ProductInterface::class)
                            ->setConfigurableProductOptions(
                                [
                                    $this->objectManager
                                        ->create(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class)
                                        ->setExtensionAttributes(
                                            $this->extensionAttributesFactory
                                                ->create(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class)
                                                ->setAttributeCode('test_size')
                                        ),
                                    $this->objectManager
                                        ->create(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class)
                                        ->setExtensionAttributes(
                                            $this->extensionAttributesFactory
                                                ->create(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class)
                                                ->setAttributeCode('test_colour')
                                        ),
                                ]
                            )
                            ->setConfigurableProductLinkedSkus(['some-non-existent-product'])
                    ),
                ['some-non-existent-product']
            ]
        ];
    }

    private static function persistAttributeOptions(string $backendType, string $frontendInput, array $optionAttributes)
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository */
        $attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
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

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public static function setUpBeforeClass(): void 
    {
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

        self::persistAttributeOptions('int', 'select', $optionAttributes);

        //create a test product that the configurable products will link to
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $objectManager->get(ProductInterfaceFactory::class)->create();
        $product->setTypeId('simple');
        $product->setExtensionAttributes(
            $objectManager
                ->get(ProductExtensionFactory::class)
                ->create());
        $product->setSku('test-product');
        $product->setName('Test Product');
        $product->setCustomAttribute('test_colour', 100);
        $product->setCustomAttribute('test_size', 100);
        $product->setPrice(1.00);
        $product->setAttributeSetId(4);
        $productRepository->save($product);
    }
}
