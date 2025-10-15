<?php

namespace Medpzl\ClubdataCart\Domain\Repository;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Order Product Repository
 *
 * @author Daniel Lorenz <ext.cart@extco.de>
 */
class PauseRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function findPause($date)
    {
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($now);
        $query = $this->createQuery();
        $query->setOrderings(['fromdate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
        $and_constraints = [];
        $and_constraints[]=$query->greaterThanOrEqual('todate', $date);
        $and_constraints[]=$query->lessThan('fromdate', $date);
        if ($and_constraints) {
            $query->matching($query->logicalAnd($and_constraints));
        }
        $result = $query->execute();
        return $result;
    }

    public function findProgram($uid)
    {
        $query = $this->createQuery();
        $where = 'where uid =' . $uid;
        $query->statement('SELECT uid,title,datetime from tx_ckclubdata_domain_model_program ' . $where);
        $rows = $query->execute(true);
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($rows);
        //exit;
        return $rows;
    }
}
