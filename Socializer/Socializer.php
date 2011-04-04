<?php

namespace OpenSky\Bundle\GigyaBundle\Socializer;

use Buzz\Client\ClientInterface;
use Buzz\Message\Response;
use OpenSky\Bundle\GigyaBundle\Document\User;
use OpenSky\Bundle\GigyaBundle\Socializer\Buzz\MessageFactory;
use OpenSky\Bundle\GigyaBundle\Socializer\UserAction;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class Socializer implements SocializerInterface, UserProviderInterface
{
    const SIMPLE_SHARE = 'simpleShare';
    const MULTI_SELECT = 'multiSelect';

    static public $shareChoices = array(
        self::SIMPLE_SHARE => 'Simple Share',
        self::MULTI_SELECT => 'Multi Select',
    );

    private $apiKey;
    private $providers = array();
    private $userActions = array();
    private $client;
    private $factory;

    public function __construct($apiKey, array $providers = array(), ClientInterface $client, MessageFactory $factory)
    {
        $this->apiKey  = (string) $apiKey;
        $this->providers = $providers;
        $this->client  = $client;
        $this->factory = $factory;
    }

    /**
     * @param string $share
     * @return boolean $isShareValid
     */
    static public function isShareValid($share)
    {
        return array_key_exists($share, static::$shareChoices);
    }

    /**
     * @return string $apiKey
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return array $providers
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * @param string $key
     * @return boolean $hasUserActionByKey
     */
    public function hasUserActionByKey($key)
    {
        return array_key_exists($key, $this->userActions);
    }

    /**
     * @param string $key
     * @return UserAction $userAction
     */
    public function getUserActionByKey($key)
    {
        return $this->userActions[$key];
    }

    /**
     * @param UserAction $userAction
     * @param string $key
     */
    public function addUserActionByKey($userAction, $key)
    {
        $this->userActions[$key] = $userAction;
    }

    /**
     * @param string $provider
     * @param string $redirect
     *
     * @return Buzz\Message\Response
     */
    public function login($provider)
    {
        $response = $this->factory->getResponse();

        $this->client->send($this->factory->getLoginRequest($provider), $response);

        return $response;
    }

    /**
     * @return array|null
     */
    public function getAccessToken()
    {
        $response = $this->factory->getResponse();
        $request  = $this->factory->getAccessTokenRequest();

        $this->client->send($request, $response);

        $result = json_decode($response->getContent(), true);

        if (isset($result['error'])) {
            return null;
        }

        return $result;
    }

    public function getUser($token)
    {
        $response = $this->factory->getResponse();
        $request  = $this->factory->getUserInfoRequest($token);

        $this->client->send($request, $response);

        libxml_use_internal_errors(true);

        $result = simplexml_load_string($response->getContent());

        if (!$result) {
            throw new \Exception('Gigya API returned invalid response');
        }

        if ((string) $result->errorCode) {
            throw new AuthenticationException((string) $result->errorMessage, (string) $result->errorDetails, (string) $result->errorCode);
        }

        $user = new User((string) $result->UID, strtolower((string) $result->loginProvider));

        foreach ($result->identities->children() as $identity) {
            if ((string) $identity->provider === $user->getProvider()) {
                $properties = array(
                    'nickname', 'firstName', 'lastName', 'gender', 'age',
                    'email', 'city', 'state', 'zip', 'country'
                );

                foreach ($properties as $property) {
                    if (isset($identity->{$property})) {
                        $user->{'set'.ucfirst($property)}((string) $identity->{$property});
                    }
                }

                $urls = array(
                    'thumbnailURL' => 'thumbnailUrl',
                    'profileURL'   => 'profileUrl',
                    'photoURL'     => 'photoUrl',
                );

                foreach ($urls as $property => $setter) {
                    if (isset($identity->{$property})) {
                        $user->{'set'.ucfirst($setter)}((string) $identity->{$property});
                    }
                }

                if (isset($identity->{'birthMonth'}) &&
                    isset($identity->{'birthDay'}) &&
                    isset($identity->{'birthYear'})) {
                    $user->setBirthday(\DateTime::createFromFormat('n-j-Y H:i', sprintf('%s-%s-%s 00:00', (string) $identity->{'birthMonth'}, (string) $identity->{'birthDay'}, (string) $identity->{'birthYear'})));
                }
            }
        }

        return $user;
    }

    public function loadUser(UserInterface $user)
    {
//        $response = $this->factory->getResponse();
//        $request  = $this->factory->getUserInfoReloadRequest($user->getUsername());
//
//        $this->client->send($request, $response);
//
//        libxml_use_internal_errors(true);
//
//        $result = simplexml_load_string($response->getContent());
//
//        if (!$result) {
//            var_dump($result); exit('asd');
//
//            throw new \Exception('Gigya API returned invalid response');
//        }
//
//        if ((string) $result->errorCode) {
//            exit((string) $result->errorMessage);
//            throw new AuthenticationException((string) $result->errorMessage, (string) $result->errorDetails, (string) $result->errorCode);
//        }
//
//        $user = new User((string) $result->UID, (string) $result->loginProvider);
//
//        foreach ($result->identities as $identity) {
//            if ((string) $identity->provider === $user->getProvider()) {
//                $properties = array(
//                    'nickname', 'photoUrl', 'thumbnailUrl', 'firstName',
//                    'lastName', 'gender', 'age', 'email', 'city', 'state',
//                    'zip', 'profileUrl'
//                );
//
//                foreach ($properties as $property) {
//                    if (isset($identity->{$property})) {
//                        $user->{'set'.ucfirst($property)}((string) $identity->{$property});
//                    }
//                }
//            }
//        }
//        var_dump($user); exit('asd');
//
        return $user;
    }

    public function loadUserByUsername($username)
    {
        // TODO Auto-generated method stub

    }

    public function supportsClass($class)
    {
        return $class === 'OpenSky\Bundle\GigyaBundle\Document\User';
    }

}
