<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MovieController extends Controller
{
    
    /**
     * @Route("movies/{page}", name="movies", requirements={"page"="\d+"}, defaults={"page"="1"})
     */
    public function listMoviesAction($page)
    {
        $movieRepository = $this->getDoctrine()->getRepository("AppBundle:Movie");
        
        $numPerPage = 50;
        $offset = ($page - 1) * $numPerPage;
        
        $movies = $movieRepository->findBy(array(), array("year" => "DESC"), $numPerPage, $offset);
        
        $params = array(
            "movies" => $movies,
            "currentPage" => $page
        );
        
        return $this->render('movie/listMovies.html.twig', $params);
    }
    
    /**
     * @Route("/movie/{id}", name="movieDetails")
     */
    public function movieDetailsAction($id)
    {
        //pour faire un SELECT, on utilise le repository de notre entité
        $movieRepository = $this->getDoctrine()->getRepository("AppBundle:Movie");
        
        //récupère une seule idée, par son id
        $movie = $movieRepository->find($id);

        $params = array(
            "movie" => $movie
        );
        
        return $this->render("movie/movieDetails.html.twig", $params);
    }
}
