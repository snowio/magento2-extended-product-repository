<?php
namespace SnowIO\ExtendedProductRepository\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class SkuResolver
{
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Magento\Framework\DB\Adapter\AdapterInterface $dbAdapter;

    public function __construct(ResourceConnection $resourceConnection, AdapterInterface $dbAdapter = null)
    {
        $this->resourceConnection = $resourceConnection;
        $this->dbAdapter = $dbAdapter ?? $resourceConnection->getConnection();
    }

    public function getProductIds(array $skus): array
    {
        if (empty($skus)) {
            return [];
        }

        $select = $this->dbAdapter->select()
            ->from($this->getProductTableName(), ['sku', 'entity_id'])
            ->where('sku in (?)', $skus);

        return $this->dbAdapter->fetchPairs($select);
    }

    private function getProductTableName()
    {
        return $this->resourceConnection->getTableName('catalog_product_entity');
    }
}
