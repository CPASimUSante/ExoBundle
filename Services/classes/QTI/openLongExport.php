<?php

/**
 * To export an open long response question in QTI
 *
 */

namespace UJM\ExoBundle\Services\classes\QTI;

class openLongExport extends openExport
{
    private $extendedTextInteraction;

    /**
     * overload the export method
     *
     * @access public
     * @param \UJM\ExoBundle\Entity\Interaction $interaction
     * @param qtiRepository $qtiRepos
     *
     */
    public function export(\UJM\ExoBundle\Entity\Interaction $interaction, qtiRepository $qtiRepos)
    {
        parent::export($interaction, $qtiRepos);
        $this->extendedTextInteractionTag();
        $this->promptTag($this->extendedTextInteraction);

        if(($this->interactionopen->getInteraction()->getFeedBack()!=Null)
                && ($this->interactionopen->getInteraction()->getFeedBack()!="") ){
            $this->qtiFeedBack($interaction->getFeedBack());
        }

        $this->document->save($this->qtiRepos->getUserDir().$this->question->getId().'_qestion_qti.xml');

        return $this->getResponse();
    }

    /**
     * add the tag extendedTextInteraction in itemBody
     *
     * @access private
     *
     */
    private function extendedTextInteractionTag()
    {
        $this->extendedTextInteraction = $this->document->CreateElement('extendedTextInteraction');
        $this->extendedTextInteraction->setAttribute("responseIdentifier", "RESPONSE");
        $this->itemBody->appendChild($this->extendedTextInteraction);
    }
}
