<?php

namespace Inachis\Component\CoreBundle\Security;

use Inachis\Component\CoreBundle\Application;
use Inachis\Component\CoreBundle\Entity\UserManager;
use Inachis\Component\CoreBundle\Entity\Security\PersistentLoginManager;
use Inachis\Component\CoreBundle\Storage\Cookie;
use ParagonIE\Halite\Symmetric\Crypto;

class Authentication
{
    /**
     * @var UserManager Used for interaction with {@link User} entities
     */
    protected $userManager;
    /**
     * Default constructor for {@link Authentication} - instantiates a new
     * {@link UserManager}
     */
    public function __construct()
    {
        $this->userManager = new UserManager(Application::getInstance()->getService('em'));
    }
    /**
     * Returns the {@link UserManager} object
     * @return UserManager The {@link UserManager} object
     */
    public function getUserManager()
    {
        return $this->userManager;
    }
    /**
     * Returns result of testing if the current user is signed in
     * @return bool The result of testing if the current user is signed in
     */
    public function isAuthenticated()
    {
        return !empty(Application::getInstance()->getService('session')) &&
            Application::getInstance()->getService('session')->hasKey('user') &&
            !empty(Application::getInstance()->getService('session')->get('user')->getUsername());
    }
    /**
     * Attempts to sign the current user in
     * @param string $username The username to sign in with
     * @param string $password The password the user is attempting to sign in with
     * @return bool The result of attempting to sign the user in
     */
    public function login($username, $password)
    {
        $user = $this->userManager->getByUsername($username);
        Application::getInstance()->getService('em')->detach($user);
        $ok = !empty($user) && !empty($user->getId()) && $user->validatePasswordHash($password);
        if ($ok) {
            Application::getInstance()->getService('session')->set('user', $user);
            Application::getInstance()->getService('session')->regenerate();
            if (Application::getInstance()->shouldLogActivities()) {
                //Application::getInstance()->getService('log')->add('login', $user->getUsername(), $user->getId());
            }
        }
        return $ok;
    }
    /**
     * Checks to see if a cookie exists for persisting the session when returning to the site
     * @param string $userAgent The user's current user-agent to validate against
     * @return bool The result of attempting to sign back in
     */
    public function getSessionPersist($userAgent)
    {
        if (empty(Cookie::get('NX03')) || empty(Cookie::get('NX06'))) {
            return false;
        }
        $persistentLoginManager = new PersistentLoginManager(Application::getInstance()->getService('em'));
        $persistentLogin = $persistentLoginManager->validateTokenForUser(Cookie::get('NX03'), Cookie::get('NX06'));
        if (!empty($persistentLogin) && !empty($persistentLogin->getUserId())) {
            $userHash = hash(
                'sha512',
                $persistentLogin->getUserId() . $userAgent
            );
            if ($persistentLogin->getUserHash() === $userHash) {
                $user = $this->userManager->getById($persistentLogin->getUserId());
                Application::getInstance()->getService('em')->detach($user);
                if (!empty($user) && !empty($user->getId())) {
                    Application::getInstance()->getService('session')->set('user', $user);
                    Application::getInstance()->getService('session')->regenerate();
                    if (Application::getInstance()->shouldLogActivities()) {
                        //Application::getInstance()->getService('log')->add('login', $user->getUsername(), $user->getId());
                    }
                    return true;
                }
            }
        }
        Cookie::delete('NX03');
        Cookie::delete('NX06');
        return false;
    }
    /**
     * Sets a cookie on the user's machine to indicate their session should persist
     * @param string $userAgent The user's current user-agent to use for validating
     * @param string $domain The domain to set the cookie for
     */
    public function setSessionPersist($userAgent, $domain)
    {
        $userHash = hash(
            'sha512',
            Application::getInstance()->getService('session')->get('user')->getId() . $userAgent
        );
        $tokenHash = hash(
            'sha512',
            random_bytes(32)
        );
        $persistentLoginManager = new PersistentLoginManager(Application::getInstance()->getService('em'));
        $persistentLogin = $persistentLoginManager->create(array(
            'userId' => (string) Application::getInstance()->getService('session')->get('user')->getId(),
            'userHash' => $userHash,
            'tokenHash' => $tokenHash,
            'expires' => new \DateTime('+1 year')
        ));
        $persistentLoginManager->save($persistentLogin)->flush();
        Cookie::set('NX03', $userHash, Cookie::ONE_YEAR);
        Cookie::set('NX06', $tokenHash, Cookie::ONE_YEAR);
    }
    /**
     * Terminates the current user session
     */
    public function logout()
    {
        Application::getInstance()->getService('session')->end();
        Cookie::delete('NX03');
        Cookie::delete('NX06');
        Application::getInstance()->getService('session')->regenerate();
        if (Application::getInstance()->shouldLogActivities()) {
            //Application::getInstance()->getService('log')->add('logout', $user->getUsername(), $user->getId());
        }
    }

    /**
     * Creates a new user with the given properties
     * @param string $username The username for the user
     * @param string $password The password for the user
     * @param string[] $properties Additional properties to assign to the user
     * @return bool The result of attempting to create the new user
     * @throws \Exception
     */
    public function create($username, $password, $properties = array())
    {
        if (empty($username) || empty($password)) {
            throw new \Exception('Username and password cannot be empty');
        }
        $user = $this->userManager->getByUsername($username);
        if (!empty($user)) {
            return false;
        }
        $properties['username'] = $username;
        $user = $this->userManager->create($properties);
        $user->setPasswordHash($password);
        $this->userManager->save($user);
        return true;
    }
}
