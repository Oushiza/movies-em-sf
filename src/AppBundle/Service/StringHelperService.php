<?php

    namespace AppBundle\Service;

    class StringHelperService {
        /* juste pour l'exemple !! */

        public $doctrine;
        public $mailer;

        /* juste pour l'exemple !! */

        public function __construct($doctrine, $mailer) {
            $this->doctrine = $doctrine;
            $this->mailer = $mailer;
        }

        public function randomString($numChars = 30) {
            $string = "";
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            for ($i = 0; $i < $numChars; $i++) {
                $rand = rand(0, strlen($chars) - 1);
                $randomChar = $chars[$rand];
                $string .= $randomChar;
            }
            //juste pour l'exemple
            $em = $this->doctrine->getManager();
            //$em->persist()
            return $string;
        }

    }
    