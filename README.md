## CONTENTS OF THIS FILE

- Introduction
- Requirements
- Installation
- Configuration

## INTRODUCTION

Automatically creates new Twitter media entities from Twitter when there are new
Tweets. This module checks periodically via cron and thus created Twitter media
entities on your Drupal website will not be in real time.

## REQUIREMENTS

This module requires the following modules:

- [Media entity Twitter](https://www.drupal.org/project/media_entity_twitter)

## INSTALLATION

- Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.

## CONFIGURATION

- Create a media type that uses the Twitter source if there is not one on the
  site already:

  - Ensure that "Whether to use Twitter api to fetch tweets or not." source
    configuration option is set to "Yes" and also the API keys have been set.

    This module uses the same credentials to look for more tweets via the
    Twitter API.

- On the media type form, there is an "Automatic Pull" vertical tab settings
  panel. Use this to configure automatic tweet fetching:

  - Usernames field is a comma-separated list of Twitter users to check for
    tweets on their respective timeline.

  - Hashtags field is a comma-separated list of Twitter hashtags to filter
    and fetch.

  - Count field is a number between 1 and 200 for the amount of tweets to fetch
    per Twitter user per cron run.
