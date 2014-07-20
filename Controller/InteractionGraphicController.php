<?php

namespace UJM\ExoBundle\Controller;
use Symfony\Component\Form\FormError;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use UJM\ExoBundle\Entity\InteractionGraphic;
use UJM\ExoBundle\Form\InteractionGraphicType;
use UJM\ExoBundle\Form\InteractionGraphicHandler;

/**
 * InteractionGraphic controller.
 *
 */
class InteractionGraphicController extends Controller
{

    /**
     * Creates a new InteractionGraphic entity.
     *
     */
    public function createAction()
    {
        $interGraph = new InteractionGraphic();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $form = $this->createForm(new InteractionGraphicType($user), $interGraph);

        $exoID = $this->container->get('request')->request->get('exercise');

        $formHandler = new InteractionGraphicHandler(
            $form, $this->get('request'), $this->getDoctrine()->getManager(),
            $this->container->get('ujm.exercise_services'),
            $user, $exoID
        );

         $graphicHandler = $formHandler->processAdd();
         if ($graphicHandler === TRUE) {
            $categoryToFind = $interGraph->getInteraction()->getQuestion()->getCategory();
            $titleToFind = $interGraph->getInteraction()->getQuestion()->getTitle();

            if ($exoID == -1) {

                return $this->redirect(
                    $this->generateUrl(
                        'ujm_question_index', array(
                            'categoryToFind' => base64_encode($categoryToFind),
                            'titleToFind' => base64_encode($titleToFind)
                        )
                    )
                );
            } else {
                return $this->redirect(
                    $this->generateUrl(
                        'ujm_exercise_questions',
                        array(
                            'id' => $exoID,
                            'categoryToFind' => $categoryToFind,
                            'titleToFind' => $titleToFind
                        )
                    )
                );
            }
         }

         if ($graphicHandler == 'infoDuplicateQuestion') {
            $form->addError(new FormError(
                    $this->get('translator')->trans('infoDuplicateQuestion')
                    ));
        }

        $formWithError = $this->render(
            'UJMExoBundle:InteractionGraphic:new.html.twig', array(
            'entity' => $interGraph,
            'form'   => $form->createView(),
            'error'  => true,
            'exoID'  => $exoID
            )
        );

        $formWithError = substr($formWithError, strrpos($formWithError, 'GMT') + 3);

        return $this->render(
            'UJMExoBundle:Question:new.html.twig', array(
            'formWithError' => $formWithError,
            'exoID'  => $exoID,
            'linkedCategory' =>  $this->container->get('ujm.exercise_services')->getLinkedCategories()
            )
        );
    }

    /**
     * Edits an existing InteractionGraphic entity.
     *
     */
    public function updateAction($id)
    {
        $user  = $this->container->get('security.context')->getToken()->getUser();
        $exoID = $this->container->get('request')->request->get('exercise');
        $catID = -1;
        $docID = -1;

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('UJMExoBundle:InteractionGraphic')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InteractionGraphic entity.');
        }

        if ($user->getId() != $entity->getInteraction()->getQuestion()->getUser()->getId()) {
            $catID = $entity->getInteraction()->getQuestion()->getCategory()->getId();
            $docID = $entity->getDocument()->getId();
        }

        $editForm = $this->createForm(
            new InteractionGraphicType(
                $this->container->get('security.context')->getToken()->getUser(),
                $catID, $docID
            ), $entity
        );

        $formHandler = new InteractionGraphicHandler(
            $editForm, $this->get('request'), $this->getDoctrine()->getManager(),
            $this->container->get('ujm.exercise_services'),
            $this->container->get('security.context')->getToken()->getUser()
        );

        if ($formHandler->processUpdate($entity)) {
            if ($exoID == -1) {

                return $this->redirect($this->generateUrl('ujm_question_index'));
            } else {

                return $this->redirect(
                    $this->generateUrl(
                        'ujm_exercise_questions',
                        array(
                            'id' => $exoID,
                        )
                    )
                );
            }
        }

        return $this->forward(
            'UJMExoBundle:Question:edit', array(
                'id' => $entity->getInteraction()->getQuestion()->getId(),
                'form' => $editForm,
                'exoID' => $exoID
            )
        );
    }

    /**
     * Deletes a InteractionGraphic entity.
     *
     */
    public function deleteAction($id, $pageNow)
    {
        $em = $this->getDoctrine()->getManager();
        $interactionGraphic = $em->getRepository('UJMExoBundle:InteractionGraphic')->find($id);
        $coords = $em->getRepository('UJMExoBundle:Coords')->findBy(array('interactionGraphic' => $id));

        if (!$interactionGraphic) {
            throw $this->createNotFoundException('Unable to find InteractionGraphic entity.');
        }

        if (!$coords) {
            throw $this->createNotFoundException('Unable to find Coords link to interactiongraphic.');
        }

        $stop = count($coords);
        for ($i = 0; $i < $stop; $i++) {
            $em->remove($coords[$i]);
        }

        $em->remove($interactionGraphic);
        $em->flush();

        return $this->redirect($this->generateUrl('ujm_question_index', array('pageNow' => $pageNow)));
    }

    /**
     * Display the twig view to add a new picture to the user's document.
     *
     */
    public function savePicAction()
    {
        return $this->render('UJMExoBundle:InteractionGraphic:add_picture.html.twig');
    }

    /**
     * Get the adress of the selected picture in order to display it.
     *
     */
    public function displayPicAction()
    {
        $request = $this->container->get('request');

        if ($request->isXmlHttpRequest()) {
            $label = $request->request->get('value'); // Name of the picture
            $prefix = $request->request->get('prefix'); // Beginning of the src of the picture

            // If the sended label isn't empty, get the matching adress
            if ($label) {
                $repository = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Document');

                $pic = $repository->findOneBy(array('label' => $label));
                $suffix = substr($pic->getUrl(), 9); // Get the end of the src of the picture
            } else {
                $suffix = ""; // Else don't display anything
            }
        }

        $url = $prefix . $suffix; // Concatenate the beginning and the end of the src of the picture

        return new Response($url); // Send back the src if the picture
    }

    /**
     * Fired when compose an exercise
     *
     */
    public function responseGraphicAction()
    {
        $vars = array();
        $request = $this->container->get('request');
        $postVal = $req = $request->request->all();

        if ($postVal['exoID'] != -1) {
            $exercise = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Exercise')->find($postVal['exoID']);
            $vars['_resource'] = $exercise;
        }

        $exerciseSer = $this->container->get('ujm.exercise_services');
        $res = $exerciseSer->responseGraphic($request);

        $vars['point']   = $res['point']; // Score of the student without penalty
        $vars['penalty'] = $res['penalty']; // Penalty (hints)
        $vars['interG']  = $res['interG']; // The entity interaction graphic (for the id ...)
        $vars['coords']  = $res['coords']; // The coordonates of the right answer zones
        $vars['doc']     = $res['doc']; // The answer picture (label, src ...)
        $vars['total']   = $res['total']; // Score max if all answers right and no penalty
        $vars['rep']     = $res['rep']; // Coordonates of the answer zones of the student's answer
        $vars['score']   = $res['score']; // Score of the student (right answer - penalty)
        $vars['exoID']   = $postVal['exoID'];

        return $this->render('UJMExoBundle:InteractionGraphic:graphicOverview.html.twig', $vars);
    }

}
