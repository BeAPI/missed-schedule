# Changelog

## 1.1.0 - 16 Oct 2026
* Throttle the synchronous check to one run per minute through a transient, and skip it during cron requests
* Filter the query on post type so it uses the `type_status_date` index, and compare GMT dates like core does
* Publish through the core `check_and_publish_future_post()` callback and only clear the single event once the post is published
* Register the WP-Cron event on `init` instead of at file load
* New filters: `beapi_missed_schedule_limit`, `beapi_missed_schedule_interval`, `beapi_missed_schedule_sync_throttle`
* Translatable cron schedule label, text domain `beapi-missed-schedule`
* Requirements raised to WordPress 4.9 and PHP 7.4
* README rewritten (how it works, constants, filters, cleanup), changelog added, tooling metadata cleaned up

## 1.0.3 - 16 Oct 2026
* Accept `composer/installers` 2.x (#12)
* Quality workflow on PHP 8.2 to 8.4, Psalm step removed

## 1.0.2 - 26 Feb 2026
* Dev tooling update (GrumPHP, PHPCS), Psalm removed
* README wording

## 1.0.1 - 04 Jun 2020
* Add README
* Fix plugin header and version

## 1.0 - 19 May 2020
* Refactor: namespace, function naming, query limit (#8)
* Rename plugin and files (#5), branding (#6), PHPDoc (#4)
* Run the check on `wp_loaded` so every plugin is loaded first (#7)

## 0.2 - 14 Jun 2018
* Fix `create_function()` deprecation on PHP 7.2 (#1)

## 0.1 - 13 Oct 2015
* Initial release
