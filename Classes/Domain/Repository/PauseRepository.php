<?php

namespace Medpzl\ClubdataCart\Domain\Repository;

class PauseRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function findPause($date)
    {
        $query = $this->createQuery();
        $query->setOrderings(['fromdate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
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
