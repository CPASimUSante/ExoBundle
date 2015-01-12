<?php

/**
 * To import a QCM question in QTI
 *
 */

namespace UJM\ExoBundle\Services\classes\QTI;

use UJM\ExoBundle\Entity\Coords;
use UJM\ExoBundle\Entity\Document;
use UJM\ExoBundle\Entity\InteractionGraphic;

class graphicImport extends qtiImport {

    protected $interactionGraph;

    /**
     * Implements the abstract method
     *
     * @access public
     * @param qtiRepository $qtiRepos
     * @param DOMElement $assessmentItem assessmentItem of the question to imported
     *
     */
    public function import(qtiRepository $qtiRepos, $assessmentItem) {
        $this->qtiRepos = $qtiRepos;
        $this->getQTICategory();
        $this->initAssessmentItem($assessmentItem);

        $this->createQuestion();

        $this->createInteraction();
        $this->interaction->setType('InteractionGraphic');
        $this->doctrine->getManager()->persist($this->interaction);
        $this->doctrine->getManager()->flush();

        $this->createInteractionGraphic();
    }

    /**
     * Create the InteractionGraphic object
     *
     * @access protected
     *
     */
    protected function createInteractionGraphic() {
        $spi = $this->assessmentItem->getElementsByTagName("selectPointInteraction")->item(0);
        $ob = $spi->getElementsByTagName('object')->item(0);

        $this->interactionGraph = new InteractionGraphic();
        $this->interactionGraph->setInteraction($this->interaction);
        $this->interactionGraph->setHeight($ob->getAttribute('height'));
        $this->interactionGraph->setWidth($ob->getAttribute('width'));

        $this->doctrine->getManager()->persist($this->interactionGraph);
        $this->doctrine->getManager()->flush();

        $this->createCoords();
        $this->createPicture($ob);
    }

    /**
     * Create the Coords
     *
     * @access protected
     *
     */
    protected function createCoords() {
        $am = $this->assessmentItem->getElementsByTagName("areaMapping")->item(0);

        foreach ($am->getElementsByTagName("areaMapEntry") as $areaMapEntry) {
            $tabCoords = explode(',', $areaMapEntry->getAttribute('coords'));
            $coords = new Coords();
            $x = $tabCoords[0] - $tabCoords[2];
            $y = $tabCoords[1] - $tabCoords[2];
            $coords->setValue($x.','.$y);
            $coords->setSize($tabCoords[2] * 2);
            $coords->setShape($areaMapEntry->getAttribute('shape'));
            $coords->setScoreCoords($areaMapEntry->getAttribute('mappedValue'));
            $coords->setColor('white');
            $coords->setInteractionGraphic($this->interactionGraph);
            $this->doctrine->getManager()->persist($coords);
            $this->doctrine->getManager()->flush();
        }
    }

    /**
     * Create the Document
     *
     * @param DOMELEMENT $ob object tag
     * @access protected
     *
     */
    protected function createPicture($objectTag) {

        $user = $this->container->get('security.context')->getToken()->getUser();
        $userDir = './uploads/ujmexo/users_documents/'.$user->getUsername();
        $this->cpPicture($objectTag->getAttribute('data'), $userDir);

        $document = new Document();
        $document->setLabel($objectTag->nodeValue);
        $document->setType($objectTag->getAttribute('type'));
        $document->setUrl($userDir.'/images/'.$objectTag->getAttribute('data'));
        $document->setUser($user);

        $this->doctrine->getManager()->persist($document);
        $this->doctrine->getManager()->flush();

        $this->interactionGraph->setDocument($document);
        $this->doctrine->getManager()->persist($this->interactionGraph);
        $this->doctrine->getManager()->flush();

    }

    /**
     * Copy the picture in the user's directory
     *
     * @param String $picture picture's name
     * @param String $userDir user's directory
     * @access protected
     *
     */
    protected function cpPicture($picture, $userDir) {
        $src = $this->qtiRepos->getUserDir().'/'.$picture;

        if (!is_dir('./uploads/ujmexo/')) {
            mkdir('./uploads/ujmexo/');
        }
        if (!is_dir('./uploads/ujmexo/users_documents/')) {
            mkdir('./uploads/ujmexo/users_documents/');
        }

        if (!is_dir($userDir)) {
            $dirs = array('audio','images','media','video');
            mkdir($userDir);

            foreach ($dirs as $dir) {
                mkdir($userDir.'/'.$dir);
            }
        }

        $dest = $userDir.'/images/'.$picture;
        $i = 1;
        while (file_exists($dest)) {
            $dest = $userDir.'/images/'.$i.'_'.$picture;
            $i++;
        }

        copy($src, $dest);
    }


    /**
     * Implements the abstract method
     *
     * @access protected
     *
     */
    protected function getPrompt()
    {
        $text = '';
        $ib = $this->assessmentItem->getElementsByTagName("itemBody")->item(0);
        if ($ib->getElementsByTagName("prompt")->item(0)) {
            $prompt = $ib->getElementsByTagName("prompt")->item(0);
            $text = $this->domElementToString($prompt);
            $text = str_replace('<prompt>', '', $text);
            $text = str_replace('</prompt>', '', $text);
            $text = html_entity_decode($text);
        }

        return $text;
    }
}
