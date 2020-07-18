# Message Scheduler Bundle

[![Latest Version][ico-version]][link-packagist]
[![Latest Unstable Version][ico-unstable-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Coverage Status][ico-code-coverage]][link-code-coverage]
[![Quality Score][ico-code-quality]][link-code-quality]

Schedule Symfony Messenger messages in the future.

The need for this bundle came on a project where we needed to check the expiry of an event on a per minute basis using
a cron job. So an event could end in 10 days at 10:05:00, but the cron job wouldn't know this. Instead, the cron job
runs every minute to check it. Checking it was a very memory intensive task, so this wasn't feasible in the long run.

Therefore, instead of checking every minute, we schedule a command to be run in the future on the time that we know the
event ends.

Problem solved 🎉

## Installation

### Step 1: Download

```bash
$ composer require setono/message-scheduler-bundle
```

### Step 2: Enable the bundle

If you use [Symfony Flex](https://flex.symfony.com/) it will be enabled automatically.
Else you need to add it to the `config/bundles.php`:

```php
<?php
// config/bundles.php

return [
    // ...

    Setono\MessageSchedulerBundle\SetonoMessageSchedulerBundle::class => ['all' => true],

    // ...
];
```

### Step 3: Update your database schema

This bundle introduces a new entity, `ScheduledMessage`, therefore you need to add a migration:

```bash
$ php bin/console doctrine:migrations:diff
$ php bin/console doctrine:migrations:migrate
```

### Step 4: Setup a cron job for dispatching messages
This bundle introduces a command, `setono:message-scheduler:dispatch`, that you should run rather often. How often
depends on the needs of your application. Every minute is recommended. Here's a [crontab snippet](https://crontab.guru/#*_*_*_*_*) for you:

```bash
* * * * * php /absolute/path/to/bin/console setono:message-scheduler:dispatch
```

### Step 5: Configure Symfony Messenger
Because this bundle dispatches your messages using a command itself, it is rather important to send that command
asynchronously. This will make sure the cron job you set up in the previous step will run **fast**. 

```yaml
framework:
    messenger:
        routing:
            # Route all command messages to the async transport
            # This presumes that you have already set up an 'async' transport
            'Setono\MessageSchedulerBundle\Message\Command\CommandInterface': async
```

## Usage

TODO

[ico-version]: https://poser.pugx.org/setono/message-scheduler-bundle/v/stable
[ico-unstable-version]: https://poser.pugx.org/setono/message-scheduler-bundle/v/unstable
[ico-license]: https://poser.pugx.org/setono/message-scheduler-bundle/license
[ico-github-actions]: https://github.com/Setono/MessageSchedulerBundle/workflows/build/badge.svg
[ico-code-coverage]: https://img.shields.io/scrutinizer/coverage/g/Setono/MessageSchedulerBundle.svg
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Setono/MessageSchedulerBundle.svg

[link-packagist]: https://packagist.org/packages/setono/message-scheduler-bundle
[link-github-actions]: https://github.com/Setono/MessageSchedulerBundle/actions
[link-code-coverage]: https://scrutinizer-ci.com/g/Setono/MessageSchedulerBundle/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Setono/MessageSchedulerBundle
