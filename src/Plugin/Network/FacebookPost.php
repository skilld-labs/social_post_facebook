<?php

namespace Drupal\social_post_facebook\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Social Post Facebook Network Plugin.
 *
 * @Network(
 *   id = "social_post_facebook",
 *   social_network = "Facebook",
 *   type = "social_post",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_post_facebook\Settings\FacebookPostSettings",
 *        "config_id": "social_post_facebook.settings"
 *      }
 *   }
 * )
 */
class FacebookPost extends SocialPostNetwork implements FacebookPostInterface {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Facebook connection.
   *
   * @var FacebookOAuth
   */
  protected $connection;

  /**
   * The tweet text.
   *
   * @var string
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('url_generator'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * FacebookPost constructor.
   *
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   Used to generate a absolute url for authentication.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator,
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  protected function initSdk() {
    $class_name = '\Abraham\FacebookOAuth\FacebookOAuth';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Facebook could not be found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_post_facebook\Settings\FacebookPostSettings $settings */
    $settings = $this->settings;

    return new FacebookOAuth($settings->getConsumerKey(), $settings->getConsumerSecret());
  }

  /**
   * {@inheritdoc}
   */
  public function post() {
    if (!$this->connection) {
      throw new SocialApiException('Call post() method from its wrapper doPost()');
    }

    $this->connection->post('statuses/update', ['status' => $this->status]);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function doPost($access_token, $access_token_secret, $status) {
    $this->connection = $this->getSdk2($access_token, $access_token_secret);
    $this->status = $status;
    return $this->post();
  }

  /**
   * {@inheritdoc}
   */
  public function getOauthCallback() {
    return $this->urlGenerator->generateFromRoute('social_post_facebook.callback', array(), array('absolute' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getSdk2($oauth_token, $oauth_token_secret) {
    /* @var \Drupal\social_post_facebook\Settings\FacebookPostSettings $settings */
    $settings = $this->settings;

    return new FacebookOAuth($settings->getConsumerKey(), $settings->getConsumerSecret(),
                $oauth_token, $oauth_token_secret);
  }

}