<?php

namespace Protein\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Protein\UserBundle\Entity\User;

class SecurityController extends Controller
{

    public function loginAction(Request $request)
    {
        if ($this->getUser() instanceof User) {
            // return $this->redirect('/');
            return $this->redirectToRoute('protein_core_home', array('_page'=>'private'));
        }

        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@ProteinUser/Security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
            'preload'       => [],
        ));
    }


    public function apiloginAction(Request $request)
    {
        $user = $this->getUser();

        $page = 1;
        $per_page = 3;
        list($athletes, $pagination) = $this->get('api_functions')->getAthletesPagination($page, $per_page);

    return $this->json(['successes'=>[
        'athletes'=>$athletes,
        'pagination'=> [
            'athletes'=> $pagination,
        ],
        'loggedUser' => [
            'user' => [
                'id' => $user->getId(), 
                'username' => $user->getUsername()
            ]
        ],
    ]]);
    }

}
