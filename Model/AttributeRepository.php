<?php
namespace SnowIO\ExtendedProductRepository\Model;

class AttributeRepository
{
    /**
     * Get the codes of any attributes which have option values
     *
     * @param string[]|null $attributeCodes
     * @return string[]
     */
    public function getAttributesWithOptions(array $attributeCodes = null) : array
    {

    }

    public function getAttributeValues(int $storeId, array $optionLabels) : GetAttributeValuesResult
    {

    }
}
