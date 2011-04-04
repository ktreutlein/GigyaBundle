<?php

namespace OpenSky\Bundle\GigyaBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class GigyaFactory extends AbstractFactory
{
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        if (isset($config['provider'])) {
            $authProviderId = 'gigya.auth.'.$id;

            $container
                ->setDefinition($authProviderId, new DefinitionDecorator('gigya.auth'))
                ->addArgument(new Reference($userProviderId))
                ->addArgument(new Reference('security.user_checked'))
            ;

            return $authProviderId;
        }

        return 'gigya.auth';
    }

    protected function getListenerId()
    {
        return 'gigya.security.authentication.listener';
    }

    public function getKey()
    {
        return 'gigya';
    }

    public function getPosition()
    {
        return 'pre_auth';
    }
}
