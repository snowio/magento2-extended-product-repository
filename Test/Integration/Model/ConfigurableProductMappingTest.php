<?php

namespace SnowIO\ExtendedProductRepository\Test\Integration\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

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

    /**
     * @dataProvider getStandardCaseTestData
     * @magentoDataFixture SnowIO/ExtendedProductRepository/Test/Integration/_files/configurable_product.php
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
}
