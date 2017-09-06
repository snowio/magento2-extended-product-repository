<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class CachedProductRepository
{
    private $productRepository;
    private $productsById = [];
    private $productsBySku = [];

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function findBySku(array $skus) : ProductSet
    {
        $products = $this->getProductsBySku($skus);
        $missingSkus = array_diff($skus, array_keys($products));

        if (!empty($missingSkus)) {
            $additionalProducts = $this->loadProducts('sku', $missingSkus);
            foreach ($additionalProducts as $product) {
                $this->addProduct($product);
                $products[] = $product;
            }
        }

        return new ProductSet($products);
    }

    /**
     * @return ProductInterface[]
     */
    private function loadProducts($field, array $idsOrSkus)
    {
        $searchCriteria = (new SearchCriteria())
            ->setFilterGroups([
                (new FilterGroup)->setFilters([
                    (new Filter)
                        ->setField($field)
                        ->setConditionType('in')
                        ->setValue($idsOrSkus),
                ]),
            ]);

        $result = $this->productRepository->getList($searchCriteria);

        return $result->getItems();
    }

    /**
     * @return ProductInterface[]
     */
    private function getProductsBySku(array $skus)
    {
        $flippedSkus = array_flip($skus);

        return array_intersect_key($this->productsBySku, $flippedSkus);
    }

    private function addProduct(ProductInterface $product)
    {
        $this->productsById[$product->getId()] = $product;
        $this->productsBySku[$product->getSku()] = $product;
    }
}
