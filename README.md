# Message Scheduler Bundle

Schedule Symfony Messenger messages in the future.

The need for this bundle came on a project where we needed to check the expiry of an event on a per minute basis using
a cron job. So an event could end in 10 days at 10:05:00, but the cron job wouldn't know this. Instead, the cron job
runs every minute to check it. Checking it was a very memory intensive task, so this wasn't feasible in the long run.

Therefore, instead of checking every minute, we schedule a command to be run in the future on the time that we know the
event ends.

Problem solved 🎉

## Installation

TODO

## Usage

TODO
