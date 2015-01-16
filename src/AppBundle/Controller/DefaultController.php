<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT m.id
            FROM AppBundle:Movie m'
        );
        $ids = $query->getResult();
        $idCount = count($ids);
        $idRandom = rand(0, $idCount);
        
        $id = $ids[$idRandom]['id'];
        
        $posterRepository = $this->getDoctrine()->getRepository("AppBundle:Movie");
        $poster = $posterRepository->find($id);
        
        $params = array(
            "randomPoster" => $poster
        );
        
        return $this->render('default/index.html.twig', $params);
    }
    
}
