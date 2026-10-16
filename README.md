<a href="https://beapi.fr">![Be API Github Banner](.github/banner-github.png)</a>

# Missed Schedule

Current version: `1.1.0`

Publish future posts when their publication date has passed and WordPress failed to do it.

WordPress schedules one `publish_future_post` event per scheduled post. When WP-Cron does not run at the right time (low traffic, a cron lock left behind, a failed loopback request), the post stays in the `future` status with a "Missed schedule" notice in the admin. This plugin looks for those posts and publishes them.

## Requirements

* WordPress 4.9+
* PHP 7.4+

## Installation

The plugin is a single PHP file meant to run as a must-use plugin. Installed through Composer it lands in its own folder, so a MU loader such as [BeAPI/wp-mu-loader](https://github.com/BeAPI/wp-mu-loader) or [Bedrock's autoloader](https://roots.io/bedrock/) is needed to load it.

### Composer

* Add the repository source if it is not already available through your package mirror: `{ "type": "git", "url": "https://github.com/BeAPI/missed-schedule" }`.
* Require the package: `composer require beapi/missed-schedule`.
* The package type is `wordpress-muplugin`, so [composer/installers](https://github.com/composer/installers) (1.x or 2.x) places it in your `mu-plugins` directory.

### Manual

* Copy `beapi-missed-schedule.php` into your `wp-content/mu-plugins/` directory.

## How it works

Two mechanisms run the same check, which publishes at most 10 posts per run, oldest first:

1. **A WP-Cron event**, `beapi_missed_scheduled_event`, every 5 minutes. Always registered. This is the recommended path, with WP-Cron disabled (`DISABLE_WP_CRON`) and a system cron running `wp cron event run --due-now`.
2. **A synchronous check on page load** (`wp_loaded`), throttled to one check per minute through a transient. Enabled by default only when `DISABLE_WP_CRON` is not `true`, since a site that relies on page loads to spawn WP-Cron needs the fallback. Skipped during cron requests.

The check reuses the core `check_and_publish_future_post()` callback, so status and date comparisons are the same as a normal scheduled publication. It filters on post type so the query uses the `type_status_date` index of the posts table.

### Constants

Force or disable the synchronous check regardless of `DISABLE_WP_CRON`:

```php
<?php
define( 'ENABLE_SYNC_MISSED_CHECK', true );  // Always check on page load.
define( 'ENABLE_SYNC_MISSED_CHECK', false ); // Never check on page load, WP-Cron only.
```

### Filters

| Filter | Default | Purpose |
|---|---|---|
| `beapi_missed_schedule_limit` | `10` | Maximum number of posts published by one check. |
| `beapi_missed_schedule_interval` | `300` | Seconds between two WP-Cron checks. Changing it takes effect after the current event is cleared (see below). |
| `beapi_missed_schedule_sync_throttle` | `60` | Minimum seconds between two synchronous checks. `0` checks on every request. |

### Removing the plugin

Must-use plugins have no deactivation hook, so the WP-Cron event stays registered after the file is removed. Clear it by hand:

```bash
wp cron event delete beapi_missed_scheduled_event
```

The same command, followed by a page load, re-registers the event with a new interval after changing `beapi_missed_schedule_interval`.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

# Who ?

Created by [Be API](https://beapi.fr), the French WordPress leader agency since 2009. Based in Paris, we are more than 30 people and always hiring some fun and talented guys. So we will be pleased to work with you.

This plugin is only maintained, which means we do not guarantee some free support. Consider reporting an [issue](../../issues) and be patient.

If you really like what we do or want to thank us for our quick work, feel free to [donate](https://www.paypal.me/BeAPI) as much as you want / can, even 1€ is a great gift for buying coffee :)

## License

This plugin is licensed under the [GPLv2 or later](LICENSE).
