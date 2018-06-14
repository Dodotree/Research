<?php

namespace Protein\UserBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Protein\UserBundle\Entity\User;

class LastLoginListener implements EventSubscriberInterface
{
    protected $em;

    public function __construct( $em)
    {
        $this->em = $em;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        );
    }

    /**
    * onAuthenticationSuccess
    *
    * @param InteractiveLoginEvent $event
    */
    public function onAuthenticationSuccess( AuthenticationEvent $event )
    {
        $this->updateLastLogin( $event );
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $this->updateLastLogin( $event );
    }

    public function updateLastLogin( $event ){
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof User) {
            $user->setLastLogin(new \DateTime());
            $this->em->persist($user);
            $this->em->flush($user);
        }
    }
}
