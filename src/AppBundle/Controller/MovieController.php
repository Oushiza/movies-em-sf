<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class MovieController extends Controller
{
    
    /**
     * @Route("movies/{page}", name="movies", requirements={"page"="\d+"}, defaults={"page"="1"})
     */
    public function listMoviesAction(Request $request, $page)
    {
        $numPerPage = 50;
        
        $movieRepository = $this->getDoctrine()->getRepository("AppBundle:Movie");
        
        // Compte total de film et pagination max
        $moviesNumber = $movieRepository->countAll();
        $maxPages = ceil($moviesNumber/$numPerPage);
        
        // Bouton search par date
        $minYear = $request->query->get('minYear');
        $maxYear = $request->query->get('maxYear');
        $searchByDate = $movieRepository->findByYear($minYear, $maxYear, $page, $numPerPage);
        
        $params = array(
            "movies" => $searchByDate,
            "moviesNumber" => $moviesNumber,
            "numPerPage" => $numPerPage,
            "currentPage" => $page,
            "maxPages" => $maxPages
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
