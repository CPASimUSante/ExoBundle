<?php

namespace UJM\ExoBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * WordResponseRepository
 *
 * repository methods below.
 */
class WordResponseRepository extends EntityRepository
{
    /**
     * Get the score max for an open question with one word
     *
     * @access public
     *
     * @param integer $interOpenId id InteractionOpen
     *
     * Return float
     */
    public function getScoreMaxOneWord($interOpenId)
    {
        $qb = $this->createQueryBuilder('wr');
        $qb->select('MAX(wr.score) AS max_score')
           ->join('wr.interactionopen', 'iopen')
           ->where($qb->expr()->in('iopen.id', $interOpenId));

        $res = $qb->getQuery()->getOneOrNullResult();

        return $res['max_score'];


        die('todo getScoreMaxOneWord in the open repository');
    }
}
