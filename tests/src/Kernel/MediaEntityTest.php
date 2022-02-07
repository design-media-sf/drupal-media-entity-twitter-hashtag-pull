<?php

namespace Drupal\Tests\media_entity_twitter_hashtag_pull\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media_entity_twitter_hashtag_pull\TwitterAPIFactory;
use Drupal\Tests\media_entity_twitter_hashtag_pull\Traits\MediaEntityTwitterHashtagPullMockTrait;

/**
 * Tests media entity interactions.
 *
 * @group media_entity_twitter_hashtag_pull
 * @requires module media_entity_twitter
 */
class MediaEntityTest extends KernelTestBase {

  use MediaEntityTwitterHashtagPullMockTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'media',
    'media_entity_twitter',
    'media_entity_twitter_hashtag_pull',
    'system',
    'user',
  ];

  /**
   * Field name of the source field in tests.
   *
   * @var string
   */
  protected $sourceField;

  /**
   * Fake Twitter API credentials used in tests.
   *
   * @var string[]
   */
  protected $credentials;

  /**
   * Third party settings used in tests.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['media_entity_twitter', 'system']);

    $this->sourceField = mb_strtolower($this->randomMachineName());
    $this->credentials = [
      'consumer_key'              => $this->randomString(),
      'consumer_secret'           => $this->randomString(),
      'oauth_access_token'        => $this->randomString(),
      'oauth_access_token_secret' => $this->randomString(),
    ];
    $this->settings = [
      'usernames' => [$this->randomMachineName(), $this->randomMachineName()],
      'hashtags' => [$this->randomMachineName(), $this->randomMachineName()],
      'count'     => rand(2, 5),
    ];

    MediaType::create([
      'id' => 'tweet',
      'source' => 'twitter',
      'source_configuration' => [
        'source_field'    => $this->sourceField,
        'use_twitter_api' => TRUE,
      ] + $this->credentials,
      'third_party_settings' => [
        'media_entity_twitter_hashtag_pull' => $this->settings,
      ],
    ])->save();

    $storage = FieldStorageConfig::create([
      'field_name'  => $this->sourceField,
      'entity_type' => 'media',
      'type'        => 'string_long',
    ]);
    $storage->save();
    FieldConfig::create(['field_storage' => $storage, 'bundle' => 'tweet'])->save();
  }

  /**
   * Tests tracking of the newest tweet ID per bundle.
   *
   * @see media_entity_twitter_hashtag_pull_media_insert()
   * @see media_entity_twitter_hashtag_pull_media_update()
   * @see media_entity_twitter_hashtag_pull_media_delete()
   * @see media_entity_twitter_hashtag_pull_media_type_delete()
   */
  public function testNewestTweetState() {
    $state = $this->container->get('state');
    $entity = Media::create([
      'bundle' => 'tweet',
      $this->sourceField => 'https://twitter.com/drupal/status/1299031518027939841',
    ]);

    $value = $state->get('media_entity_twitter_hashtag_pull.tweet');
    $this->assertNull($value, 'State still null when media created.');

    $entity->save();

    $value = $state->get('media_entity_twitter_hashtag_pull.tweet');
    $this->assertEquals(1299031518027939841, $value, 'State updated on save.');

    Media::create([
      'bundle' => 'tweet',
      $this->sourceField => 'https://twitter.com/drupal/status/1298563366009659393',
    ])->save();

    $value = $state->get('media_entity_twitter_hashtag_pull.tweet');
    $this->assertEquals(1299031518027939841, $value, 'State not updated with older tweet.');

    $entity = Media::create([
      'bundle' => 'tweet',
      $this->sourceField => 'https://twitter.com/drupal/status/1299248854232072193',
    ]);
    $entity->save();

    $value = $state->get('media_entity_twitter_hashtag_pull.tweet');
    $this->assertEquals(1299248854232072193, $value, 'State updated with newer tweet.');

    $entity->delete();

    $value = $state->get('media_entity_twitter_hashtag_pull.tweet');
    $this->assertEquals(1299031518027939841, $value, 'State updated newest tweet on delete.');

    MediaType::load('tweet')->delete();

    $value = $state->get('media_entity_twitter_hashtag_pull.tweet');
    $this->assertNull($value, 'State deleted when media type deleted.');
  }

  /**
   * Tests twitter media entity creation from cron run.
   *
   * @see media_entity_twitter_hashtag_pull_cron()
   * @see \Drupal\media_entity_twitter_hashtag_pull\Plugin\QueueWorker\MediaEntityTwitterHashtagPullFetch
   */
  public function testCron() {
    $since = rand(1, 1000);
    $this->container->get('state')->set('media_entity_twitter_hashtag_pull.tweet', $since);

    $exchange_0 = $this->createApiMock(
      "?screen_name={$this->settings['usernames'][0]}&count={$this->settings['count']}&include_rts=1&since_id=$since",
      file_get_contents(__DIR__ . '/../../fixtures/user_timeline.json'),
    );
    $exchange_1 = $this->createApiMock(
      "?screen_name={$this->settings['usernames'][1]}&count={$this->settings['count']}&include_rts=1&since_id=$since",
      file_get_contents(__DIR__ . '/../../fixtures/user_timeline_0.json'),
    );

    $factory = $this->createMock(TwitterAPIFactory::class);
    $factory
      ->method('fromCredentials')
      ->with($this->credentials)
      ->willReturn($exchange_0, $exchange_1);
    $this->container->set('media_entity_twitter_hashtag_pull.twitter_api_factory', $factory);

    $this->container->get('cron')->run();

    $media = Media::loadMultiple();
    $this->assertCount(4, $media, 'Media entities created.');
  }

}
