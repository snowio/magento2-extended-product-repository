<?php
namespace SnowIO\ExtendedProductRepository\Model;

class GetAttributeValuesResult
{
    /**
     * @return \Magento\Framework\Api\AttributeInterface[]
     */
    public function getAttributeData() : array
    {

    }

    public function getMissingLabels() : array
    {

    }

    public function assertNoMissingLabels()
    {
        if ($this->getMissingLabels()) {
            throw new \RuntimeException;
        }
    }
}
