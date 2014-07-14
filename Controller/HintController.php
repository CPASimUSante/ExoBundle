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

namespace UJM\ExoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use UJM\ExoBundle\Entity\Hint;
use UJM\ExoBundle\Entity\Paper;
use UJM\ExoBundle\Entity\LinkHintPaper;
use UJM\ExoBundle\Form\HintType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hint controller.
 *
 */
class HintController extends Controller
{

    /**
     * Finds and displays a Hint entity.
     *
     * @access public
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction()
    {
        $request = $this->container->get('request');
        $session = $this->getRequest()->getSession();

        if ($request->isXmlHttpRequest()) {
            $id = $request->request->get('id');

            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('UJMExoBundle:Hint')->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Hint entity.');
            }

            if (!$session->get('penalties')) {
                $penalties = array();
                $session->set('penalties', $penalties);
            }
            $penalties = $session->get('penalties');

            if (($request->request->get('paper') != null) && (!isset($penalties[$id]))) {
                $lhp = new LinkHintPaper(
                    $entity, $em->getRepository('UJMExoBundle:Paper')->find($session->get('paper'))
                );
                $lhp->setView(1);
                $em->persist($lhp);
                $em->flush();
            }

            $penalties[$id] = $entity->getPenalty();
            $session->set('penalties', $penalties);

            return $this->container->get('templating')->renderResponse(
                'UJMExoBundle:Hint:show.html.twig', array(
                'entity'      => $entity,
                )
            );
        } else {

            return 0;
        }
    }
}