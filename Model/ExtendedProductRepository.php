<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use function SnowIO\ExtendedProductRepository\applyCustomAttributes;
use function SnowIO\ExtendedProductRepository\getCustomAttributeCodes;
use function SnowIO\ExtendedProductRepository\getCustomAttributeValues;

class ExtendedProductRepository implements ProductRepositoryInterface
{
    private $productRepository;
    private $dataMapper;

    public function __construct(ProductRepositoryInterface $productRepository, ProductDataMapper $dataMapper)
    {
        $this->productRepository = $productRepository;
        $this->dataMapper = $dataMapper;
    }

    public function save(ProductInterface $product, $saveOptions = false)
    {
        return $this->productRepository->save($this->dataMapper->mapOptionLabelsToValues($product), $saveOptions);
    }

    public function get($sku, $editMode = false, $storeId = null, $forceReload = false)
    {
        return $this->productRepository->get($sku, $editMode, $storeId, $forceReload);
    }

    public function getById($productId, $editMode = false, $storeId = null, $forceReload = false)
    {
        return $this->productRepository->getById($productId, $editMode, $storeId, $forceReload);
    }

    public function delete(ProductInterface $product)
    {
        return $this->productRepository->delete($product);
    }

    public function deleteById($sku)
    {
        return $this->productRepository->deleteById($sku);
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        return $this->productRepository->getList($searchCriteria);
    }
}
