<?php

namespace Medpzl\ClubdataCart\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class PauseRepository extends Repository
{
    public function findPause($date)
    {
        $query = $this->createQuery();
        $query->setOrderings(['fromdate' => QueryInterface::ORDER_ASCENDING]);
        $and_constraints = [];
        $and_constraints[] = $query->greaterThanOrEqual('todate', $date);
        $and_constraints[] = $query->lessThan('fromdate', $date);
        if ($and_constraints) {
            $query->matching($query->logicalAnd($and_constraints));
        }
        $result = $query->execute();
        return $result;
    }

    // TODO refactor this!
    public function findProgram($uid)
    {
        $query = $this->createQuery();
        $where = 'where uid =' . $uid;
        $query->statement('SELECT uid,title,datetime from tx_ckclubdata_domain_model_program ' . $where);
        $rows = $query->execute(true);
        return $rows;
    }
}
