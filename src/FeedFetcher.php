<?php

namespace Drupal\media_entity_twitter_hashtag_pull;

/**
 * The tweet feed fetcher service.
 */
class FeedFetcher implements FeedFetcherInterface {

  /**
   * Twitter API factory.
   *
   * @var \Drupal\media_entity_twitter_hashtag_pull\TwitterAPIFactory
   */
  protected $apiFactory;

  /**
   * Constructs a FeedFetcher.
   *
   * @param \Drupal\media_entity_twitter_hashtag_pull\TwitterAPIFactory $api_factory
   *   Twitter API factory.
   */
  public function __construct(TwitterAPIFactory $api_factory) {
    $this->apiFactory = $api_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserTimelineTweets($username, array $credentials, $count = 200, $since_id = 1) {
    $response = $this->apiFactory
      ->fromCredentials($credentials)
      ->setGetField("?screen_name=$username&count=$count&include_rts=1&since_id=$since_id")
      ->buildOauth('https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET')
      ->performRequest();

    $tweets = [];
    foreach (json_decode($response, TRUE) as $tweet) {
      $tweets[] = $tweet['id'];
    }

    return $tweets;
  }

  /**
   * {@inheritdoc}
   */
  public function getHashtagTweets($hashtag, array $credentials, $count = 200, $since_id = 1) {
    $response = $this->apiFactory
      ->fromCredentials($credentials)
      ->setGetField("?q=%23$hashtag&count=$count&result_type=recent&since_id=$since_id")
      ->buildOauth('https://api.twitter.com/1.1/search/tweets.json', 'GET')
      ->performRequest();

    $tweets = [];
    $decoded_response = json_decode($response, TRUE);
    $tweets_response = $decoded_response['statuses'];
    foreach ($tweets_response as $tweet) {
      $tweets[] = ["id" => $tweet['id'], "username" => $tweet['user']['screen_name']];
    }

    return $tweets;
  }

}