<?php

namespace Drupal\Tests\media_entity_twitter_hashtag_pull\Traits;

/**
 * Helper functions for creating mocks used in tests.
 */
trait MediaEntityTwitterHashtagPullMockTrait {

  /**
   * Creates a mock object for the Twitter API exchange object.
   *
   * @param string $params
   *   The parameter string expected to be called with.
   * @param string $response
   *   The mocked response.
   *
   * @return \TwitterAPIExchange|\PHPUnit\Framework\MockObject\MockObject
   *   The mock API object.
   */
  protected function createApiMock($params, $response) {
    $exchange = $this->createMock('TwitterAPIExchange');
    $exchange
      ->expects($this->once())
      ->method('setGetField')
      ->with($params)
      ->willReturnSelf();
    $exchange
      ->expects($this->once())
      ->method('buildOauth')
      ->with('https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET')
      ->willReturnSelf();
    $exchange
      ->expects($this->once())
      ->method('performRequest')
      ->willReturn($response);

    return $exchange;
  }

}
