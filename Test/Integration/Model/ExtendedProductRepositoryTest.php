<?php
namespace SnowIO\ExtendedProductRepository\Test\Integration\Model;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

class ExtendedProductRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ProductRepositoryInterface */
    private $productRepositoryInterface;

    /** @var  ObjectManagerInterface */
    private $objectManager;

    /** @var  ProductAttributeRepositoryInterface */
    private $attributeRepository;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepositoryInterface = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->attributeRepository = $this->objectManager->get(ProductAttributeRepositoryInterface::class);
    }

    /**
     * @param AttributeInterface[] $customAttributes
     * @magentoDataFixture SnowIO/ExtendedProductRepository/Test/Integration/_files/options.php
     * @dataProvider getStandardCaseTestData
     */
    public function testStandardCase(int $storeId, ProductExtensionInterface $productExtension)
    {
        $sku = 'test-product-' . mt_rand(1000, 9999);

        /** @var ProductInterface $input */
        $input = $this->objectManager->create(ProductInterface::class);
        $input->setTypeId('simple');
        $input->setSku($sku);
        $input->setName('testProduct' . $sku);
        $input->setPrice(1.00);
        $input->setAttributeSetId(4);
        $input->setExtensionAttributes($productExtension);

        $this->productRepositoryInterface->save($input);

        $output = $this->productRepositoryInterface->get($sku);
        foreach ($productExtension->getAttributeOptionLabels() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $outputAttribute = $output->getCustomAttribute($attributeCode);
            $this->assertNotNull($outputAttribute);
            $outputValues = explode(',', $outputAttribute->getValue());
            $assignedLabels = array_map(function ($outputValue) use ($attributeCode) {
                return $this->getAttributeOptionLabel($attributeCode, $outputValue);
            }, $outputValues);
            $this->assertSame((array)$attribute->getValue(), $assignedLabels);
        }
    }

    public function getStandardCaseTestData()
    {
        return [
            [ // test case 1
                $storeId = 1,
                $this->objectManager->get(ProductExtensionFactory::class)->create()->setAttributeOptionLabels([
                    $this->objectManager->create(AttributeInterface::class)
                        ->setAttributeCode('test_colour')
                        ->setValue('Rot'),
                    $this->objectManager->create(AttributeInterface::class)
                        ->setAttributeCode('test_size')
                        ->setValue('groß'),
                    $this->objectManager->create(AttributeInterface::class)
                        ->setAttributeCode('test_terrain')
                        ->setValue(['Wüste', 'Wald']),
                ]),
            ],
        ];
    }

    private function getAttributeOptionLabel(string $attributeCode, int $value)
    {
        $attributeDefinition = $this->attributeRepository->get($attributeCode);

        foreach ($attributeDefinition->getOptions() as $option) {
            if ((int)$option->getValue() !== $value) {
                continue;
            }

            return $option->getLabel();
        }

        throw new \RuntimeException('Label not found.');
    }
}