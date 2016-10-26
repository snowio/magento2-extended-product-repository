<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use function SnowIO\ExtendedProductRepository\applyCustomAttributes;
use function SnowIO\ExtendedProductRepository\getCustomAttributeCodes;
use function SnowIO\ExtendedProductRepository\getCustomAttributeValues;

class ProductDataMapper
{
    private $attributeRepository;

    public function __construct(AttributeRepository $attributeRepository)
    {
        $this->attributeRepository = $attributeRepository;
    }

    public function mapOptionLabelsToValues(ProductInterface $product) : ProductInterface
    {
        $customAttributes = getCustomAttributeCodes($product);
        $attributesWithOptions = $this->attributeRepository->getAttributesWithOptions($customAttributes);
        $optionLabels = getCustomAttributeValues($product, $attributesWithOptions);
        $result = $this->attributeRepository->getAttributeValues($optionLabels);
        $result->assertNoMissingLabels();
        $product = applyCustomAttributes($product, $result->getAttributeData());

        return $product;
    }
}
