<?php
namespace Protein\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends Controller
{
    public function defaultAction(Request $request){
        $user = $this->getUser();
    return $this->json([
        'successes'=> [
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
        ]] ]);
    }

    public function indexAction(Request $request){
        $user = $this->getUser();

        $page = 1;
        $per_page = 3;
        list($athletes, $pagination) = $this->get('api_functions')->getAthletesPagination($page, $per_page);

        return $this->json([
            'successes'=> [
                'athletes'=>$athletes,
                'pagination'=> [
                    'athletes'=> $pagination,
                ]
            ],
        ]);
    }
 
}
