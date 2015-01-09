<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

use AppBundle\Entity\User;
use AppBundle\Form\RegistrationType;



class UserController extends Controller
{
    /**
     * @Route("/register", name="register")
     */
    public function registerAction(Request $request){
        
        //on crée un utilisateur vide (une instance de notre entité User)
        $user = new User();
        
        //on récupère une instance de notre formulaire
        //ce form est associé ) l'utilisateur vide
        $registrationForm = $this->createForm(new RegistrationType(), $user);
        
        //traite le formulaire
        $registrationForm->handleRequest($request);
        
        //si les données sont valides
        if($registrationForm->isValid()){
            //hydrate les autres propriétés de notre User

                //générer un salt
                $salt = md5(uniqid());
                $user->setSalt($salt);
            
                //générer un token
                $token = md5(uniqid());
                $user->setToken($token);
                
                //hacher le mot de passe
                //sha512, 5000 fois
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $user->getPassword());
                $user->setPassword($encoded);
                
                //date d'inscription et date de modification
                $user->setDateRegistered(new \DateTime());
                $user->setDateModified(new \DateTime());
                
                //assigne toujours ce rôle aux utilisateurs du front-office
                $user->setRoles( array("ROLE_USER"));
            
            //sauvegarde le User en bdd
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
            
        }
        
        dump($user);
        
        $params = array(
            "registrationForm" => $registrationForm->createView()
        );
        
        return $this->render('user/register.html.twig', $params);
    }
    
     /**
     * @Route("/login", name="login_route")
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                Security::AUTHENTICATION_ERROR
            );
        } elseif (null !== $session && $session->has(Security::AUTHENTICATION_ERROR)) {
            $error = $session->get(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(Security::LAST_USERNAME);

        return $this->render(
            'user/login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $lastUsername,
                'error'         => $error,
            )
        );
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
    }
    
    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
    }
    
    
}
