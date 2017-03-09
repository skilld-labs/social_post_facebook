<?php

namespace Drupal\social_post_facebook\Settings;

/**
 * Defines an interface for Social Post Facebook settings.
 */
interface FacebookPostSettingsInterface {

  /**
   * Gets the consumer key.
   *
   * @return string
   *   The consumer key.
   */
  public function getConsumerKey();

  /**
   * Gets the consumer secret.
   *
   * @return string
   *   The consumer secret.
   */
  public function getConsumerSecret();

}
