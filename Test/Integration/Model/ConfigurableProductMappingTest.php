<?php

namespace SnowIO\ExtendedProductRepository\Test\Integration\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;

class ConfigurableProductMappingTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ObjectManagerInterface */
    private $objectManager;

    /** @var  ProductRepositoryInterface */
    private $productRepository;

    /** @var  ProductAttributeRepositoryInterface */
    private $attributeRepository;

    /** @var  ExtensionAttributesFactory */
    private $extensionAttributesFactory;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->attributeRepository = $this->objectManager->get(ProductAttributeRepositoryInterface::class);
        $this->extensionAttributesFactory = $this->objectManager->get(ExtensionAttributesFactory::class);
    }

    public function setUp()
    {
        $this->setUpConfigurableAttributes();
    }

    public function tearDown()
    {
        $this->tearDownConfigurableAttributes();
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

        $inputProductIds = array_map(function (string $sku) {
            return $this->getProductIdFromSku($sku);
        }, $inputProductExtensionAttributes->getConfigurableProductLinkedSkus());


        $outputProductIds = $outputProductExtensionAttributes->getConfigurableProductLinks();

        $this->assertEquals(0, count(array_diff($inputProductIds, $outputProductIds)));
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
                    ->setSku('test-configurable-product-red')
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


    private function persistAttributeOptions(
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


    private function setUpConfigurableAttributes()
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

        $this->persistAttributeOptions('int', 'select', $optionAttributes, $objectManager);

        //create a test product that the configurable products will link to
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
        /** @var ProductInterface$productFactory */
        $product = $objectManager->get(ProductInterface::class);
        /** @var \Magento\Catalog\Model\Product $product */
        $product->setTypeId('simple');
        $product->setExtensionAttributes(
            $objectManager
                ->get(ProductExtensionFactory::class)
                ->create());
        $product->setSku('test-product');
        $product->setName('Test Product');
        $product->setCustomAttribute('test_color', 0);
        $product->setCustomAttribute('test_size', 1);
        $product->setPrice(1.00);
        $product->setAttributeSetId(4);
        $productRepository->save($product);
        /** @var \Magento\Framework\Api\AttributeInterface $customAttribute */
        foreach ($product->getCustomAttributes() ?? [] as $customAttribute) {
            $data = $customAttribute->__toArray();
            \fwrite(\STDERR, \print_r($data, true));
        }
    }

    private function tearDownConfigurableAttributes()
    {
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
                $attributeRepository->deleteById($attributesCode);
            }
        } catch (\Exception $e) {
        }
    }

}
