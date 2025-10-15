<?php

namespace Medpzl\ClubdataCart\Domain\Repository\Order;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class ProductRepository extends \Extcode\Cart\Domain\Repository\Order\ProductRepository
{
    public function findSku($uids)
    {
        $query = $this->createQuery();
        $query->setOrderings(['productType' => QueryInterface::ORDER_ASCENDING]);
        $and_constraints = [];
        $and_constraints[] = $query->in('sku', $uids);
        $query->matching($query->logicalAnd(...$and_constraints));

        return $query->execute();
    }
}
