<?php

namespace Drupal\media_entity_twitter_hashtag_pull;

/**
 * Describes the interface for a tweet feed fetcher.
 */
interface FeedFetcherInterface {

  /**
   * Fetches tweet URLs from a user's timeline.
   *
   * @param string $username
   *   Twitter handle of the user to fetch timeline tweets for.
   * @param array $credentials
   *   Array of API credentials to use for Twitter request:
   *   - consumer_key: Consumer key token.
   *   - consumer_secret: Consumer secret.
   *   - oauth_access_token: Oauth access token.
   *   - oauth_access_token_secret: Oauth access secret.
   * @param int $count
   *   (optional) The number of tweets to fetch from the API.
   * @param int $since_id
   *   (optional) Limit fetched tweets newer than this ID.
   *
   * @return int[]
   *   List of tweet IDs.
   */
  public function getUserTimelineTweets($username, array $credentials, $count = 200, $since_id = 1);
  public function getHashtagTweets($hashtag, array $credentials, $count = 200, $since_id = 1);

}
