<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Store\Model\StoreManagerInterface;
use function SnowIO\ExtendedProductRepository\applyCustomAttributes;
use function SnowIO\ExtendedProductRepository\getCustomAttributeCodes;
use function SnowIO\ExtendedProductRepository\getCustomAttributeValues;

class ExtendedProductRepository implements ProductRepositoryInterface
{
    private $productRepository;
    private $dataMapper;
    private $storeManager;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductDataMapper $dataMapper,
        StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->dataMapper = $dataMapper;
        $this->storeManager = $storeManager;
    }

    public function save(ProductInterface $product, $saveOptions = false)
    {
        $storeId = $this->storeManager->getStore(true)->getId();
        $product = $this->dataMapper->mapOptionLabelsToValues($storeId, $product);

        return $this->productRepository->save($product, $saveOptions);
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
