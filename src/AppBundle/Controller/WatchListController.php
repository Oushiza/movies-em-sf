<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;

class WatchListController extends Controller
{
    /**
     * @Route("/movie/{id}/{actionType}", name="addMovie")
     */
    public function watchListAction(Request $request,$id, $actionType)
    {
        $movieRepository = $this->getDoctrine()->getRepository("AppBundle:Movie");
        $em = $this->getDoctrine()->getManager();
        
        $user = $this->getUser();
        $movie = $this->find($id);

        $user->addMovie($movie);

        $em->persist($user);
        $em->flush();

        $user->getMovie();
        
        return $this->render('movie/movieDetails.html.twig');
    }
    
    /**
     * @Route("/idee/vote/{ideaId}/{voteType}", name="vote")
     */
    public function voteAction(Request $request, $ideaId, $voteType)
    {
        //pour faire un SELECT, on utilise le repository de notre entité
        $ideaRepository = $this->getDoctrine()->getRepository("AppBundle:Idea");
        
        //gestionnaires d'entité pour sauvegarder les idées, les votes, ...
        $em = $this->getDoctrine()->getManager();
        
        //récupère une seule idée, par son id
        $idea = $ideaRepository->find($ideaId);
        
        //recupere topus les votes passés sur cette idée
        $votes = $idea->getVotes();
        
        //recherche un vote ayant la même ip que la personne qui essaie actuellement de voter
        foreach ($votes as $vote){
            //ip trouvé ! donc déja voté
            if($vote->getIp() == $request->getClientIp()){
                $this->addFlash("error", "Vous avez déjà voté pour cette idée !");
                //on redirige vers la précedente tout de suite
                return $this->redirect($this->generateUrl("ideaDetails", array("id"=>$ideaId)));
            }
        }
        
        //enregistre le vote en bdd (pour prevenir plus tard les votes multiples)
        $vote = new Vote();
        $vote->setDateVoted(new \DateTime());
        $vote->setIp($request->getClientIp());
        $vote->setType($voteType);
        //on crée l'associtation entre le vote et l'idée sur laquelle on vote en lui passant l'Objet Idea au complet
        $vote->setIdea($idea);
        $em->persist($vote);
        
        if ($voteType == "like"){
            $newLikesCount = $idea->getLikesCount() + 1;
            $idea->setLikesCount($newLikesCount);
        } else if ($voteType == "dislike"){
            $newDislikesCount = $idea->getDislikesCount() + 1;
            $idea->setDislikesCount($newDislikesCount);
        }
        
        $em->persist($idea);
        $em->flush();
        
        $this->addFlash("success", "Merci pour votre vote !");
        
        return $this->redirect($this->generateUrl("ideaDetails", array("id"=> $ideaId)));
    }
    
}
