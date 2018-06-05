<?php

namespace Protein\UserBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Protein\UserBundle\Entity\User;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param Request $request
     * @return Response never null
     */
    public function onLogoutSuccess(Request $request)
    {
        if($request->getMethod() == 'GET'){
            return new RedirectResponse($this->urlGenerator->generate('protein_user_login'));
        }
        return new JsonResponse(array('successes'=>array('done', $request->getMethod())));
    }
}
