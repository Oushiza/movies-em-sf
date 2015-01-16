<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

use AppBundle\Entity\User;
use AppBundle\Form\RegistrationType;
use AppBundle\Form\LostPasswordType;
use AppBundle\Form\ChangePasswordType;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;


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
                $salt = $this->get('string_helper')->randomString(50);
                $user->setSalt($salt);
            
                //générer un token
                $token = $this->get('string_helper')->randomString(30);
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
                $em = $this->get("doctrine")->getManager();
                $em->persist($user);
                $em->flush();
            
        }
        
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
    
    /**
     * Cette page affiche et traite le formulaire où l'on demande son email à l'utilisateur
     * @Route("/forgot-password", name="forgotPassword")
     */
    public function forgotPasswordAction(Request $request)
    {
        $user = new User();
        $lostPasswordForm = $this->createForm(new LostPasswordType(), $user);
        $lostPasswordForm->handleRequest( $request );
        //si soumis, traiter le formulaire contenant l'email
        if ($lostPasswordForm->isValid()){
            //si l'email existe en base de donnée
            $userRepo = $this->getDoctrine()->getRepository("AppBundle:User");
            $foundUser = $userRepo->findOneByEmail( $user->getEmail() );
            if ($foundUser){
                //envoyer un message contenant un lien vers checkEmailToken
                $message = \Swift_Message::newInstance()
                        ->setCharset("utf-8")
                        ->setSubject('Password reset request')
                        ->setFrom(array('movies@movies.com' => "Movies"))
                        ->setTo( $foundUser->getEmail() )
                        ->setBody($this->renderView("email/forgot_password_email.html.twig", 
                            array("user" => $foundUser)), "text/html")
                    ;
                $this->get('mailer')->send($message);
                //le prévenir d'aller lire ses emails
                return $this->render("user/lost_password_check_email.html.twig");
            }
            else {
                //sinon
                    //prévenir l'utilisateur de l'erreur
                $this->addFlash("error", "This email is not registered here.");
            }
        }
        return $this->render("user/forgot_password.html.twig", array(
            "lostPasswordForm" => $lostPasswordForm->createView()
        ));
    }  
    
    /**
     * L'utilisateur ayant oublié son mdp aboutira sur cette page après avoir cliqué sur le lien reçu par email
     * Cette page redirige toujours vers une autre page
     * @Route("/check-email-token/{email}/{token}", name="checkEmailToken")
     */
    public function checkEmailTokenAction($email, $token)
    {
        
        //faire une requête en bdd pour récupérer l'utilisateur ayant cet email ET ce token
        $userRepo = $this->getDoctrine()->getRepository("AppBundle:User");
        $userFound= $userRepo->findOneBy(
            array("email" => $email, "token" => $token)
        );
        
        //** faire bcp de tests pour s'assurer qu'il n'y a pas de faille **
        //éventuellement, ralentir volontairement ce code pour limiter les attaques en brute force
        sleep(1);
        //si l'utilisateur est trouvé
        if ($userFound){
            //connecter programmatiquement l'utilisateur trouvé
            
            $token = new UsernamePasswordToken($userFound, null, "secured_area", $userFound->getRoles());
            $this->get("security.context")->setToken($token); //now the user is logged in
             
            //now dispatch the login event
            $request = $this->get("request");
            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
            //rediriger vers une autre page qui affichera et traitera le formulaire de nouveau mdp
            return $this->redirect($this->generateUrl("changePassword"));
        }
        //sinon
        //le rediriger vers l'accueil ou vers un site pour mécréant
        return $this->redirect( $this->generateUrl("listMovies") );
    } 
    
    /**
     * Cette page affiche et traite le formulaire de changement de mot de passe
     * L'utilisateur doit être connecté pour y accéder
     * @Route("/change-password", name="changePassword")
     */
    public function changePasswordAction(Request $request)
    {
        //récupère le user loggué depuis la session
        $user = $this->getUser();
        $changePasswordForm = $this->createForm(new ChangePasswordType(), $user);
        $changePasswordForm->handleRequest($request);
        if ($changePasswordForm->isValid()){
            //générer un nouveau token
            $token = $this->get('string_helper')->randomString(30);
            $user->setToken( $token );
            //hacher le mot de passe
            $encoder = $this->get('security.password_encoder');
            $encoded = $encoder->encodePassword( $user, $user->getPassword() );
            $user->setPassword( $encoded );
            //on change la dernière date de modif
            $user->setDateModified( new \DateTime() );
            //sauvegarde le User en bdd
            $em = $this->getDoctrine()->getManager();
            $em->persist( $user );
            $em->flush();
            $this->addFlash("success", "New password saved !");
            return $this->redirect( $this->generateUrl("homepage") );
        }
        return $this->render("user/change_password.html.twig", array("changePasswordForm" => $changePasswordForm->createView()));
    }
      
}
