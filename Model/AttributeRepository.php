<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject\IdentityInterface;

class AttributeRepository
{
    private $attributeRepository;
    private $searchCriteriaBuilder;
    private $cache;

    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CacheInterface $cache
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->cache = $cache;
    }

    public function getAttributeId($attributeCode)
    {
        $cacheKey = "attribute_id\\$attributeCode";

        if (!$attributeId = $this->loadFromCache($cacheKey)) {
            if (!$attribute = $this->attributeRepository->get($attributeCode)) {
                return null;
            }
            $attributeId = $attribute->getAttributeId();
            $this->saveToCache($cacheKey, $attributeId, $attribute);
        }

        return $attributeId;
    }

    public function getAttributeCode($attributeId)
    {
        $cacheKey = "attribute_code\\$attributeId";

        if (!$attributeCode = $this->loadFromCache($cacheKey)) {
            if (!$attribute = $this->getAttributeById($attributeId)) {
                return null;
            }
            $attributeCode = $attribute->getAttributeCode();
            $this->saveToCache($cacheKey, $attributeCode, $attribute);
        }

        return $attributeCode;
    }

    public function getDefaultFrontendLabel($attributeId)
    {
        $cacheKey = "default_frontend_label\\$attributeId";

        if (!$frontendLabel = $this->loadFromCache($cacheKey)) {
            if (!$attribute = $this->getAttributeById($attributeId)) {
                return null;
            }
            $frontendLabel = $attribute->getDefaultFrontendLabel();
            $this->saveToCache($cacheKey, $frontendLabel, $attribute);
        }

        return $frontendLabel;
    }

    /**
     * @return null|\Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    private function getAttributeById($attributeId)
    {
        $this->searchCriteriaBuilder->create(); // this is the only way to ensure that the builder is empty
        $this->searchCriteriaBuilder->addFilter('attribute_id', $attributeId);
        $products = $this->attributeRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        return \array_shift($products);
    }

    private function loadFromCache($key)
    {
        $absoluteCacheKeyHash = \md5(__CLASS__ . "/$key");
        return $this->cache->load($absoluteCacheKeyHash);
    }

    private function saveToCache($key, $data, ProductAttributeInterface $attribute)
    {
        if ($attribute instanceof IdentityInterface) {
            $tags = $attribute->getIdentities();
        } else {
            $tags = ["EAV_ATTRIBUTE_{$attribute->getAttributeId()}"];
        }

        $absoluteCacheKeyHash = \md5(__CLASS__ . "/$key");
        $this->cache->save($data, $absoluteCacheKeyHash, $tags);
    }
}
