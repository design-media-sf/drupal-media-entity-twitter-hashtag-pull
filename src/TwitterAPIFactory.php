<?php

namespace Drupal\media_entity_twitter_hashtag_pull;

/**
 * Helper class to construct a Twitter API communications object.
 */
class TwitterAPIFactory {

  /**
   * Constructs a new API object.
   *
   * @param string[] $credentials
   *   The credentials to communicate to the Twitter API with:
   *   - consumer_key: Consumer key token.
   *   - consumer_secret: Consumer secret.
   *   - oauth_access_token: Oauth access token.
   *   - oauth_access_token_secret: Oauth access secret.
   *
   * @return \TwitterAPIExchange
   *   The API object.
   */
  public function fromCredentials(array $credentials) {
    return new \TwitterAPIExchange($credentials);
  }

}
