<a href="https://beapi.fr">![Be API Github Banner](.github/banner-github.png)</a>

# Missed Schedule 

Publish future post when publication date is pasted and WP fail. Prefer WP-CRON CLI usage instead synchronous execution.

## Requirements

* WordPress > 4.4
* PHP > 5.6

## Customization and hooks

This plugin does not have a hook.

It is possible to force the publication synchronously with the following constant. Otherwise, the articles will be published via the WP_CRON tasks.

```
<?php 
if ( ! defined( 'ENABLE_SYNC_MISSED_CHECK' ) ) {
	define( 'ENABLE_SYNC_MISSED_CHECK', true );
}
```

# Who ?

Created by [Be API](https://beapi.fr), the French WordPress leader agency since 2009. Based in Paris, we are more than 30 people and always [hiring](https://beapi.workable.com) some fun and talented guys. So we will be pleased to work with you.

This plugin is only maintained, which means we do not guarantee some free support. Consider reporting an [issue](#issues--features-request--proposal) and be patient.

If you really like what we do or want to thank us for our quick work, feel free to [donate](https://www.paypal.me/BeAPI) as much as you want / can, even 1€ is a great gift for buying coffee :)

## License

This plugin is licensed under the [GPLv2 or later](LICENSE.md).
