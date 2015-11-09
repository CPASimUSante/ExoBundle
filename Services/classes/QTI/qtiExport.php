<?php

/**
 * To export a question in QTI
 *
 */

namespace UJM\ExoBundle\Services\classes\QTI;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


abstract class qtiExport
{

    protected $doctrine;
    protected $tokenStorageInterface;
    protected $container;
    protected $qtiRepos;
    protected $node;
    protected $document;
    protected $responseDeclaration = array();
    protected $nbResponseDeclaration = 0;
    protected $outcomeDeclaration;
    protected $modalFeedback;
    protected $itemBody;
    protected $question;
    protected $resourcesLinked = array();

    /**
     * Constructor
     *
     * @access public
     *
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine Dependency Injection
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorageInterface Dependency Injection
     * @param \Symfony\Component\DependencyInjection\Container $container
     *
     */
    public function __construct(Registry $doctrine, TokenStorageInterface $tokenStorageInterface, $container)
    {
        $this->doctrine              = $doctrine;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $this->container             = $container;
        $this->document              = new \DOMDocument();
        $this->document->preserveWhiteSpace = false;
        $this->document->formatOutput = true;
    }

    /**
     * Generate object tags for the attached files
     *
     * @access protected
     *
     */
    protected function objetcTags()
    {
        $prompt = $this->document->getElementsByTagName('prompt')->item(0);
        $txt = html_entity_decode($prompt->nodeValue);
        $regex = '(<a.*?</a>)';
        preg_match_all($regex, $txt, $matches);
        foreach ($matches[0] as $matche) {
            $url = substr($matche, stripos($matche, 'href="/') + 7, stripos($matche, '">') - 10);
            $url = explode('/', $url);
            $nodeId = $url[6];
            echo $nodeId."<br>";

        }
    }

    /**
     * Generate head of QTI
     *
     * @access protected
     *
     * @param String $identifier type question
     * @param String $title title of question
     *
     */
    protected function qtiHead($identifier, $title)
    {
        $this->node = $this->document->CreateElement('assessmentItem');
        $this->node->setAttribute("xmlns", "http://www.imsglobal.org/xsd/imsqti_v2p1");
        $this->node->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $this->node->setAttribute("xsi:schemaLocation", "http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd");
        $this->node->setAttribute("identifier", $identifier);
        $this->node->setAttribute("title", $title);
        $this->node->setAttribute("adaptive", "false");
        $this->node->setAttribute("timeDependent", "false");
        $this->document->appendChild($this->node);
    }

    /**
     * Add a new tag responseDeclaration to node
     *
     * @access potected
     *
     * @param String $baseType
     *
     */
    protected function qtiResponseDeclaration($identifier, $baseType, $cardinality)
    {
        $this->responseDeclaration[$this->nbResponseDeclaration] = $this->document->CreateElement('responseDeclaration');

        $newRespDec = $this->responseDeclaration[$this->nbResponseDeclaration];
        $newRespDec->setAttribute("identifier", $identifier);
        $newRespDec->setAttribute("cardinality", $cardinality);
        $newRespDec->setAttribute("baseType", $baseType);
        $this->node->appendChild($newRespDec);

        $this->nbResponseDeclaration++;
    }

    /**
     * add the tag outcomeDeclaration to the node
     *
     * @access protected
     *
     *
     */
    protected function qtiOutComeDeclaration()
    {
        $this->outcomeDeclaration = $this->document->CreateElement('outcomeDeclaration');
        $this->outcomeDeclaration->setAttribute("identifier", "SCORE");
        $this->outcomeDeclaration->setAttribute("cardinality", "single");
        $this->outcomeDeclaration->setAttribute("baseType", "float");
        $this->node->appendChild($this->outcomeDeclaration);
    }

    /**
     * add the tag modalFeedback to the node
     *
     * @access protected
     *
     * @param String $feedBack
     *
     */
    protected function qtiFeedBack($feedBack)
    {
        $this->modalFeedback = $this->document->CreateElement('modalFeedback');
        $this->modalFeedback->setAttribute("outcomeIdentifier","FEEDBACK");
        $this->modalFeedback->setAttribute("identifier","COMMENT");
        $this->modalFeedback->setAttribute("showHide","show");
        if(strpos($feedBack,'<img') !== false){
           $feedBack=$this->qtiImage($feedBack);
           $feedBackNew = $this->document->importNode($feedBack, true);
           $this->modalFeedback->appendChild($feedBackNew);
        }
        else{
            $modalFeedbacktxt = $this->document->CreateTextNode($feedBack);
            $this->modalFeedback->appendChild($modalFeedbacktxt);
        }
        $this->node->appendChild($this->modalFeedback);
    }

    /**
     * add a tag p in itemBody for the description
     *
     * @access protected
     *
     */
    protected function qtiDescription(){
        $describe = $this->question->getDescription();
        //Check if there are image
        if ($describe != NULL && $describe != '') {
            if (strpos($describe, '<img') !== false) {
                $describe = $this->qtiImage($describe);
                $describeTagNew = $this->document->importNode($describe, true);
                $describeTag= $this->document->appendChild($describeTagNew);
            }
            else
            {
                $describeTag = $this->document->createCDATASection($describe);
            }
            $this->itemBody->appendChild($describeTag);
        }
    }

    /**
     * Gestion de l'export d'images au format QTI
     * @param String $txt
     * @return DOMElement
     */
    protected function qtiImage($txt){
        $DOMdoc = new \DOMDocument();
        $DOMdoc->loadHTML($txt);
        $searchImg= $DOMdoc->getElementsByTagName('img');

        foreach($searchImg as $img){
            $object = $DOMdoc->CreateElement('object');
            //Recupere les infos de l'image
            $alt=$img->getAttribute('alt');
            $object->setAttribute("data", $alt);
            $objecttxt = $DOMdoc->CreateTextNode($alt);
            $object->appendChild($objecttxt);

            $type=explode('.', $alt);
            $mimetype=end($type);
            $mimetype = "image/". $mimetype;
            $object->setAttribute("type", $mimetype);

            //Copie l'image dans l'archive
            $src=$img->getAttribute('src');
            $this->getPictureInTxt($alt,$src);
            //Cretion d'un tableau pour remplacer les balises
            $elements[]=array($object,$img);
        }
        //Remplace les balise image par les balise object
        foreach ($elements as $el){
          //el[0] = node object et el[1] = node image
          $el[1]->parentNode->replaceChild($el[0], $el[1]);
        }
        //On recupere la balise p
        $body = $DOMdoc->getElementsByTagName('body')->item(0);

        return $body;

    }

    private function getPictureInTxt($pictureName,$url){
        $dest = $this->qtiRepos->getUserDir().$pictureName;
        $urlExplode=explode('/', $url);
        $idNode=end($urlExplode);
        $objSrc=  $this->doctrine->getManager()->getRepository('ClarolineCoreBundle:Resource\File')->findOneBy(array('resourceNode' => $idNode));
        $src=$this->container->getParameter('claroline.param.files_directory').'/'.$objSrc->getHashName();
        copy($src, $dest);
        $ressource = array ('name' => $pictureName, 'url' => $src);
        $this->resourcesLinked[] = $ressource;
    }

    /**
     * add the tag itemBody in node
     *
     * @access protected
     *
     */
    protected function itemBodyTag()
    {
        $this->itemBody = $this->document->CreateElement('itemBody');
        $this->node->appendChild($this->itemBody);
        $this->qtiDescription();
    }

    /**
     *
     * @access protected
     *
     * @param array of String $docLinked attached files
     *
     * @return BinaryFileResponse QTI zip
     *
     */
    protected function getResponse()
    {
        $tmpFileName = $this->qtiRepos->getUserDir().'zip/'.$this->question->getId().'_qestion_qti.zip';
        $zip = new \ZipArchive();
        $zip->open($tmpFileName, \ZipArchive::CREATE);
        $zip->addFile($this->qtiRepos->getUserDir().$this->question->getId().'_qestion_qti.xml', $this->question->getId().'_qestion_qti.xml');

        foreach ($this->resourcesLinked as $file) {
            $zip->addFile($file['url'], $file['name']);
        }

        $zip->close();

        $qtiServ = $this->container->get('ujm.qti_services');
        $response = $qtiServ->createZip($tmpFileName, $this->question->getId());

        return $response;
    }

    /**
     * abstract method to export the question
     *
     * @access public
     * @param \UJM\ExoBundle\Entity\Interaction $interaction
     * @param qtiRepository $qtiRepos
     */
    abstract public function export(\UJM\ExoBundle\Entity\Interaction $interaction, qtiRepository $qtiRepos);

    /**
     * abstract method
     * Add the tag correctResponse in responseDeclaration
     *
     * @access protected
     */
    abstract protected function correctResponseTag();

    /**
     * abstract method
     * Add the tag prompt
     *
     * @access protected
     */
    abstract protected function promptTag();
}
