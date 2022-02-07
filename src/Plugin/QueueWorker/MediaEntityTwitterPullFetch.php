<?php

namespace Drupal\media_entity_twitter_pull\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_entity_twitter_pull\FeedFetcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches new tweets for a Twitter media entity bundle.
 *
 * @QueueWorker(
 *   id = "media_entity_twitter_pull_fetch",
 *   title = @Translation("Twitter API Fetch"),
 *   cron = {"time" = 60}
 * )
 */
class MediaEntityTwitterPullFetch extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The feed fetcher.
   *
   * @var \Drupal\media_entity_twitter_pull\FeedFetcherInterface
   */
  protected $feedFetcher;

  /**
   * The media entity storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * Tests the test access block.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\media_entity_twitter_pull\FeedFetcherInterface $feed_fetcher
   *   The feed fetcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, FeedFetcherInterface $feed_fetcher, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->feedFetcher = $feed_fetcher;
    $this->mediaStorage = $entity_type_manager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('media_entity_twitter_pull.feed_fetcher'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof MediaTypeInterface) {
      $source = $data->getSource();
      $source_config = $source->getConfiguration();

      if ($source->getPluginId() == 'twitter' && $source_config['use_twitter_api']) {
        $settings = $data->getThirdPartySettings('media_entity_twitter_pull');
        $settings += ['usernames' => [], 'count' => 10];

        $bundle_key = $this->mediaStorage->getEntityType()->getKey('bundle');
        $source_field = $source->getSourceFieldDefinition($data)->getName();
        $credentials = [
          'consumer_key' => $source_config['consumer_key'],
          'consumer_secret' => $source_config['consumer_secret'],
          'oauth_access_token' => $source_config['oauth_access_token'],
          'oauth_access_token_secret' => $source_config['oauth_access_token_secret'],
        ];
        $since = $this->state->get("media_entity_twitter_pull.{$data->id()}", 1);

        foreach ($settings['usernames'] as $username) {
          foreach ($this->feedFetcher->getUserTimelineTweets($username, $credentials, $settings['count'], $since) as $tweet_id) {
            $this->mediaStorage
              ->create([
                $bundle_key   => $data->id(),
                $source_field => "https://twitter.com/$username/status/$tweet_id",
              ])
              ->save();
          }
        }
      }
    }
  }

}
