<?php

namespace Drupal\social_post_facebook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post_facebook\FacebookPostAuthManager;
use Drupal\social_post_facebook\FacebookUserEntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Manages requests to Facebook.
 */
class FacebookPostController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * The facebook post auth manager.
   *
   * @var \Drupal\social_post_facebook\FacebookPostAuthManager
   */
  protected $authManager;

  /**
   * The Facebook user entity manager.
   *
   * @var \Drupal\social_post_facebook\FacebookUserEntityManager
   */
  protected $facebookEntity;

  /**
   * FacebookPostController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   The network plugin manager.
   * @param \Drupal\social_post_facebook\FacebookPostAuthManager $auth_manager
   *   The Facebook post auth manager.
   * @param \Drupal\social_post_facebook\FacebookUserEntityManager $facebook_entity
   *   The Facebook user entity manager.
   */
  public function __construct(NetworkManager $network_manager, FacebookPostAuthManager $auth_manager, FacebookUserEntityManager $facebook_entity) {
    $this->networkManager = $network_manager;
    $this->authManager = $auth_manager;
    $this->facebookEntity = $facebook_entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('facebook_post.auth_manager'),
      $container->get('facebook_user_entity.manager')
    );
  }

  /**
   * Redirects user to Facebook for authentication.
   *
   * @return \Zend\Diactoros\Response\RedirectResponse
   *   Redirects to Facebook.
   *
   * @throws \Abraham\FacebookOAuth\FacebookOAuthException
   */
  public function redirectToFacebook() {
    /* @var \Drupal\social_post_facebook\Plugin\Network\FacebookPost $network_plugin */
    $network_plugin = $this->networkManager->createInstance('social_post_facebook');

    /* @var \Abraham\FacebookOAuth\FacebookOAuth $connection */
    $connection = $network_plugin->getSdk();

    $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $network_plugin->getOauthCallback()));

    // Saves the request token values in session.
    $this->authManager->setOauthToken($request_token['oauth_token']);
    $this->authManager->setOauthTokenSecret($request_token['oauth_token_secret']);

    // Generates url for authentication.
    $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

    return new RedirectResponse($url);
  }

  /**
   * Callback function for the authentication process.
   *
   * @throws \Abraham\FacebookOAuth\FacebookOAuthException
   */
  public function callback() {
    $oauth_token = $this->authManager->getOauthToken();
    $oauth_token_secret = $this->authManager->getOauthTokenSecret();

    /* @var \Abraham\FacebookOAuth\FacebookOAuth $connection */
    $connection = $this->networkManager->createInstance('social_post_facebook')->getSdk2($oauth_token, $oauth_token_secret);

    // Gets the permanent access token.
    $access_token = $connection->oauth('oauth/access_token', array('oauth_verifier' => $this->authManager->getOauthVerifier()));

    // Save the user authorization tokens and store the current user id in $uid.
    $uid = $this->facebookEntity->saveUser($access_token);

    return $this->redirect('entity.user.edit_form', array('user' => $uid));
  }

}
