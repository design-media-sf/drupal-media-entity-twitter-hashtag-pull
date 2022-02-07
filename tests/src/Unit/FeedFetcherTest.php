<?php

namespace Drupal\Tests\media_entity_twitter_pull\Unit;

use Drupal\media_entity_twitter_pull\FeedFetcher;
use Drupal\media_entity_twitter_pull\TwitterAPIFactory;
use Drupal\Tests\media_entity_twitter_pull\Traits\MediaEntityTwitterPullMockTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\media_entity_twitter_pull\FeedFetcher
 * @group media_entity_twitter_pull
 */
class FeedFetcherTest extends UnitTestCase {

  use MediaEntityTwitterPullMockTrait;

  /**
   * @covers ::getUserTimelineTweets
   */
  public function testGetUserTimelineTweets() {
    $username = $this->randomMachineName();
    $count    = rand(1, 200);
    $since    = rand(201, 500);

    $exchange = $this->createApiMock(
      "?screen_name=$username&count=$count&include_rts=1&since_id=$since",
      file_get_contents(__DIR__ . '/../../fixtures/user_timeline.json')
    );

    $factory = $this->createMock(TwitterAPIFactory::class);
    $factory->method('fromCredentials')->willReturn($exchange);

    $result = (new FeedFetcher($factory))->getUserTimelineTweets($username, [], $count, $since);

    $this->assertArrayEquals([850007368138018817, 848930551989915648], $result, 'Returns a list of numerical tweet IDs.');
  }

}
