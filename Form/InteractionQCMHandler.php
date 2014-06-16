<?php

/**
 * ExoOnLine
 * Copyright or © or Copr. Université Jean Monnet (France), 2012
 * dsi.dev@univ-st-etienne.fr
 *
 * This software is a computer program whose purpose is to [describe
 * functionalities and technical features of your software].
 *
 * This software is governed by the CeCILL license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
*/

namespace UJM\ExoBundle\Form;

use UJM\ExoBundle\Entity\InteractionQCM;

class InteractionQCMHandler extends \UJM\ExoBundle\Form\InteractionHandler
{

    public function processAdd()
    {
        if ( $this->request->getMethod() == 'POST' ) {
            $this->form->handleRequest($this->request);

            if($this->validateNbClone() === FALSE) {
                return 'infoDuplicateQuestion';
            }

            if ( $this->form->isValid() ) {
                $this->onSuccessAdd($this->form->getData());

                return true;
            }
        }

        return false;
    }

    protected function onSuccessAdd($interQCM)
    {

        // \ pour instancier un objet du namespace global et non pas de l'actuel
        $interQCM->getInteraction()->getQuestion()->setDateCreate(new \Datetime());
        $interQCM->getInteraction()->getQuestion()->setUser($this->user);
        $interQCM->getInteraction()->setType('InteractionQCM');

        $pointsWrong = str_replace(',', '.', $interQCM->getScoreFalseResponse());
        $pointsRight = str_replace(',', '.', $interQCM->getScoreRightResponse());

        $interQCM->setScoreFalseResponse($pointsWrong);
        $interQCM->setScoreRightResponse($pointsRight);

        $this->em->persist($interQCM);
        $this->em->persist($interQCM->getInteraction()->getQuestion());
        $this->em->persist($interQCM->getInteraction());

        // On persiste tous les choices de l'interaction QCM.
        $ord = 1;
        foreach ($interQCM->getChoices() as $choice) {
            $choice->setOrdre($ord);
            //$interQCM->addChoice($choice);
            $choice->setInteractionQCM($interQCM);
            $this->em->persist($choice);
            $ord = $ord + 1;
        }

        $this->persistHints($interQCM);

        $this->em->flush();

        $this->addAnExericse($interQCM);

        $this->duplicateInter($interQCM);

    }

    public function processUpdate($originalInterQCM)
    {
        $originalChoices = array();
        $originalHints = array();

        // Create an array of the current Choice objects in the database
        foreach ($originalInterQCM->getChoices() as $choice) {
            $originalChoices[] = $choice;
        }
        foreach ($originalInterQCM->getInteraction()->getHints() as $hint) {
            $originalHints[] = $hint;
        }

        if ( $this->request->getMethod() == 'POST' ) {
            $this->form->handleRequest($this->request);

            if ( $this->form->isValid() ) {
                $this->onSuccessUpdate($this->form->getData(), $originalChoices, $originalHints);

                return true;
            }
        }

        return false;
    }

    protected function onSuccessUpdate()
    {
        $arg_list = func_get_args();
        $interQCM = $arg_list[0];
        $originalChoices = $arg_list[1];
        $originalHints = $arg_list[2];

        // filter $originalChoices to contain choice no longer present
        foreach ($interQCM->getChoices() as $choice) {
            foreach ($originalChoices as $key => $toDel) {
                if ($toDel->getId() == $choice->getId()) {
                    unset($originalChoices[$key]);
                }
            }
        }

        // remove the relationship between the choice and the interactionqcm
        foreach ($originalChoices as $choice) {
            // remove the choice from the interactionqcm
            $interQCM->getChoices()->removeElement($choice);

            // if you wanted to delete the Choice entirely, you can also do that
            $this->em->remove($choice);
        }

        $this->modifyHints($interQCM, $originalHints);

        $pointsWrong = str_replace(',', '.', $interQCM->getScoreFalseResponse());
        $pointsRight = str_replace(',', '.', $interQCM->getScoreRightResponse());

        $interQCM->setScoreFalseResponse($pointsWrong);
        $interQCM->setScoreRightResponse($pointsRight);

        $this->em->persist($interQCM);
        $this->em->persist($interQCM->getInteraction()->getQuestion());
        $this->em->persist($interQCM->getInteraction());

        // On persiste tous les choices de l'interaction QCM.
        //$ord = 1;
        foreach ($interQCM->getChoices() as $choice) {
            //$choice->setOrdre($ord);
            $interQCM->addChoice($choice);
            $this->em->persist($choice);
            //$ord++;
        }

        $this->em->flush();

    }
}