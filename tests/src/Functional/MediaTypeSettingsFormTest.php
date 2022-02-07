<?php

namespace Drupal\Tests\media_entity_twitter_pull\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests settings form.
 *
 * @group media_entity_twitter_pull
 * @requires module media_entity_twitter
 */
class MediaTypeSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_entity_twitter_pull'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    MediaType::create([
      'id' => 'tweet',
      'label' => 'Tweet',
      'source' => 'twitter',
      'source_configuration' => ['source_field' => 'field_media_twitter'],
    ])->save();

    $storage = FieldStorageConfig::create([
      'field_name'  => 'field_media_twitter',
      'entity_type' => 'media',
      'type'        => 'string_long',
    ]);
    $storage->save();
    FieldConfig::create(['field_storage' => $storage, 'bundle' => 'tweet'])->save();
  }

  /**
   * Tests third-party settings.
   *
   * @see media_entity_twitter_pull_form_media_form_alter()
   * @see media_entity_twitter_pull_media_builder()
   */
  public function testThirdPartySettings() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));

    $usernames = $this->randomMachineName();
    $count = rand(1, 200);

    $edit = [
      'media_entity_twitter_pull[usernames]' => $usernames,
      'media_entity_twitter_pull[count]' => $count,
    ];
    $this->drupalPostForm('admin/structure/media/manage/tweet', $edit, t('Save'));

    $settings = MediaType::load('tweet')->getThirdPartySettings('media_entity_twitter_pull');
    $this->assertEqualsCanonicalizing(['usernames' => [$usernames], 'count' => $count], $settings, 'Settings are saved.');

    $usernames = [$this->randomMachineName(), $this->randomMachineName()];
    $edit = ['media_entity_twitter_pull[usernames]' => "$usernames[0],$usernames[1]"];
    $this->drupalPostForm('admin/structure/media/manage/tweet', $edit, t('Save'));

    $settings = MediaType::load('tweet')->getThirdPartySetting('media_entity_twitter_pull', 'usernames');
    $this->assertEqualsCanonicalizing($usernames, $settings, 'Comma-separated usernames are parsed successfully.');

    $this->drupalGet('admin/structure/media/manage/tweet');
    $this->assertSession()->fieldValueEquals('media_entity_twitter_pull[usernames]', "$usernames[0], $usernames[1]");

    $usernames = [$this->randomMachineName(), $this->randomMachineName()];
    $edit = ['media_entity_twitter_pull[usernames]' => "$usernames[0]+: ;-$usernames[1];+-("];
    $this->drupalPostForm('admin/structure/media/manage/tweet', $edit, t('Save'));

    $settings = MediaType::load('tweet')->getThirdPartySetting('media_entity_twitter_pull', 'usernames');
    $this->assertEqualsCanonicalizing($usernames, $settings, 'Usernames with separated by non-word characters are parsed successfully.');

    $this->drupalGet('admin/structure/media/manage/tweet');
    $this->assertSession()->fieldValueEquals('media_entity_twitter_pull[usernames]', "$usernames[0], $usernames[1]");

    $edit = ['media_entity_twitter_pull[usernames]' => ''];
    $this->drupalPostForm('admin/structure/media/manage/tweet', $edit, t('Save'));

    $settings = MediaType::load('tweet')->getThirdPartySettings('media_entity_twitter_pull');
    $this->assertEmpty($settings, 'Saving with empty usernames deletes settings.');
  }

}
