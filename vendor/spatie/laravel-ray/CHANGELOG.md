# Changelog

All notable changes to `laravel-ray` will be documented in this file

## 1.32.6 - 2023-07-19

### What's Changed

- Bump dependabot/fetch-metadata from 1.5.1 to 1.6.0 by @dependabot in https://github.com/spatie/laravel-ray/pull/305
- feat: support raw sql by @innocenzi in https://github.com/spatie/laravel-ray/pull/306

### New Contributors

- @innocenzi made their first contribution in https://github.com/spatie/laravel-ray/pull/306

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.32.5...1.32.6

## 1.32.5 - 2023-06-23

- fix displaying Mailables

## 1.32.4 - 2023-03-23

### What's Changed

- Avoid making DB connection unless necessary by @crynobone in https://github.com/spatie/laravel-ray/pull/295

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.32.3...1.32.4

## 1.32.3 - 2023-03-03

- display correct origin when using `invade`

## 1.32.2 - 2023-02-06

### What's Changed

- Bump shivammathur/setup-php from 2.23.0 to 2.24.0 by @dependabot in https://github.com/spatie/laravel-ray/pull/291
- Bump dependabot/fetch-metadata from 1.3.5 to 1.3.6 by @dependabot in https://github.com/spatie/laravel-ray/pull/292
- Add context to ApplicationLogPayload by @bilfeldt in https://github.com/spatie/laravel-ray/pull/293

### New Contributors

- @bilfeldt made their first contribution in https://github.com/spatie/laravel-ray/pull/293

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.32.1...1.32.2

## 1.32.1 - 2023-01-26

### What's Changed

- Make DB connection optional by @lentex in https://github.com/spatie/laravel-ray/pull/290

### New Contributors

- @lentex made their first contribution in https://github.com/spatie/laravel-ray/pull/290

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.32.0...1.32.1

## 1.32.0 - 2023-01-11

- add support for Laravel 10

## 1.31.0 - 2022-09-20

### What's Changed

- Added in comment to docblock for linux docker users by @jaetoole in https://github.com/spatie/laravel-ray/pull/271
- @ray blade directive completion for Laravel Idea(PhpStorm) by @adelf in https://github.com/spatie/laravel-ray/pull/273

### New Contributors

- @jaetoole made their first contribution in https://github.com/spatie/laravel-ray/pull/271
- @adelf made their first contribution in https://github.com/spatie/laravel-ray/pull/273

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.30.0...1.31.0

## 1.30.0 - 2022-07-29

### What's Changed

- Add `send_deprecated_notices_to_ray` to config stub by @squatto in https://github.com/spatie/laravel-ray/pull/267
- Feat: Slow query configuration by @fullstackfool in https://github.com/spatie/laravel-ray/pull/269

### New Contributors

- @fullstackfool made their first contribution in https://github.com/spatie/laravel-ray/pull/269

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.29.7...1.30.0

## 1.29.7 - 2022-05-27

## What's Changed

- Fixes https://github.com/spatie/laravel-ray/issues/250 by @dfox288 in https://github.com/spatie/laravel-ray/pull/251

## New Contributors

- @dfox288 made their first contribution in https://github.com/spatie/laravel-ray/pull/251

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.29.6...1.29.7

## 1.29.6 - 2022-04-15

## What's Changed

- ignore php 8.1 deprecation notices by @Nielsvanpach in https://github.com/spatie/laravel-ray/pull/247

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.29.5...1.29.6

## 1.29.5 - 2022-04-05

## What's Changed

- Fix undefined payload when using queue driver other than sync by @stein-j in https://github.com/spatie/laravel-ray/pull/245

## New Contributors

- @stein-j made their first contribution in https://github.com/spatie/laravel-ray/pull/245

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.29.4...1.29.5

## 1.29.4 - 2022-02-22

## What's Changed

- check if ApplicationLogPayload can be loaded by @ThomasEnssner in https://github.com/spatie/laravel-ray/pull/242

## New Contributors

- @ThomasEnssner made their first contribution in https://github.com/spatie/laravel-ray/pull/242

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.29.3...1.29.4

## 1.29.3 - 2022-02-15

- correctly display mailables that are written to the log in Laravel 9

## 1.29.2 - 2022-02-13

## What's Changed

- Fix deprecated by @TiiFuchs in https://github.com/spatie/laravel-ray/pull/240

## New Contributors

- @TiiFuchs made their first contribution in https://github.com/spatie/laravel-ray/pull/240

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.29.1...1.29.2

## 1.29.1 - 2022-02-09

- moved dependency

## 1.29.0 - 2022-01-13

- automatically set project name

## 1.28.0 - 2022-01-11

1.28.0

- allow Laravel 9

## 1.28.0 - 2022-01-11

- allow Laravel 9

## 1.27.2 - 2021-12-27

- Fix: make sure there is always a `VarDumper` handler registered to output to HTML or CLI

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.27.1...1.27.2

## 1.27.1 - 2021-12-27

## What's Changed

- Register `DumpRecorder` only once and keep original handler connected by @AlexVanderbist in https://github.com/spatie/laravel-ray/pull/233

## New Contributors

- @AlexVanderbist made their first contribution in https://github.com/spatie/laravel-ray/pull/233

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.27.0...1.27.1

## 1.27.0 - 2021-12-26

## What's Changed

- Slow Query Logging by @patinthehat in https://github.com/spatie/laravel-ray/pull/232

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.26.5...1.27.0

## 1.26.5 - 2021-12-21

## What's Changed

- add support for Symfony 6 by @Nielsvanpach in https://github.com/spatie/laravel-ray/pull/231

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.26.4...1.26.5

## 1.26.4 - 2021-12-10

## What's Changed

- Added DeprecatedNoticeWatcher that piggy backs off of the Application… by @JuanRangel in https://github.com/spatie/laravel-ray/pull/229

## New Contributors

- @JuanRangel made their first contribution in https://github.com/spatie/laravel-ray/pull/229

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.26.3...1.26.4

## 1.26.3 - 2021-11-22

## What's Changed

- Fix typo in ray.php docblock by @iDiegoNL in https://github.com/spatie/laravel-ray/pull/227

## New Contributors

- @iDiegoNL made their first contribution in https://github.com/spatie/laravel-ray/pull/227

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.26.2...1.26.3

## 1.26.2 - 2021-11-15

## What's Changed

- Check if Laravel has been bound with `Facade\FlareClient\Flare` by @crynobone in https://github.com/spatie/laravel-ray/pull/224

**Full Changelog**: https://github.com/spatie/laravel-ray/compare/1.26.1...1.26.2

## 1.26.1 - 2021-10-01

- fix #217 error with duplicate queries log (#220)

## 1.26.0 - 2021-09-27

- feature duplicate queries (#216)

## 1.25.2 - 2021-09-10

- enhance metadata instead of overriding it in when sending a request (#215)

## 1.25.1 - 2021-09-07

- add support for zbateson/mail-mime-parser v2 (#214)

## 1.25.0 - 2021-08-27

- add tags to cache payload (#210)

## 1.24.2 - 2021-07-23

- fix origin of query builder ray calls (now for real)

## 1.24.1 - 2021-07-23

- fix origin of query builder ray calls

## 1.24.0 - 2021-07-23

- add `ray` macro on query builder

## 1.23.0 - 2021-06-24

- allow multiple mailables ([#204](https://github.com/spatie/laravel-ray/pull/204))

## 1.22.0 - 2021-06-24

- when a query exception occurs, the query itself will also be sent to Ray.

## 1.21.0 - 2021-06-22

- add `countQueries`

## 1.20.2 - 2021-06-21

- fix `mailable` when using `Mail::fake`

## 1.20.1 - 2021-06-15

- fix origin of stringable

## 1.20.0 - 2021-06-15

- add support for stringables

## 1.19.1 - 2021-06-11

- better HTTP Client logging (#201)

## 1.19.0 - 2021-06-04

- add http logging methods

## 1.18.0 - 2021-03-23

- colorize high severity messages (#197)

## 1.17.4 - 2021-04-30

- check if an exception is passed before log dumping

## 1.17.3 - 2021-04-29

- the package won't send dumps to Ray when dump sending is disabled

## 1.17.2 - 2021-04-06

- Laravel Octane Compatibility (#178)

## 1.17.1 - 2021-03-14

- send exceptions by default

## 1.17.0 - 2021-03-13

- enable/disable sending exceptions to Ray (#173)

## 1.16.0 - 2021-03-12

- allow using `env()`  when config is not available (#172)

## 1.15.1 - 2021-03-10

- fix handling of null logs (#171)

## 1.15.0 - 2021-03-09

- add `env` method

## 1.14.0 - 2021-03-04

- add support for hostname

## 1.13.0 - 2021-02-22

- add exception watcher

## 1.12.6 - 2021-02-10

- replace spaces with underscores in `env()` calls (#154)

## 1.12.5 - 2021-02-10

- fix "Package spatie/laravel-ray is not installed" exception (#156)

## 1.12.4 - 2021-02-10

- handle edge case where ray proxy would not be set

## 1.12.3 - 2021-02-08

- chain colours on `show*` methods (#149)

## 1.12.2 - 2021-02-07

- ignore errors caused by using `storage_path`

## 1.12.1 - 2021-02-05

- register watchers on boot (#138)

## 1.12.0 - 2021-02-03

- remove enabled methods (#132)

## 1.11.2 - 2021-02-02

- do not blow up when using `Mail::fake()`

## 1.11.1 - 2021-02-01

- update config file

## 1.11.0 - 2021-01-31

- add view requests
- add view cache

## 1.10.1 - 2021-01-31

- display logged exceptions

## 1.10.0 - 2021-01-29

- add view methods

## 1.9.3 - 2021-01-28

- internals cleanup

## 1.9.2 - 2021-01-28

- improve dependencies

## 1.9.1 - 2021-01-25

- improve service provider

## 1.9.0 - 2021-01-22

- add `showJobs`

## 1.8.0 - 2021-01-19

- the package will now select the best payload type when passing something to `ray()`

## 1.7.1 - 2021-01-17

- lower dependencies

## 1.7.0 - 2021-01-15

- make `model` more flexible

## 1.6.1 - 2021-01-15

- better support for logged mailables

## 1.6.0 - 2021-01-15

- add `markdown` method

## 1.5.2 - 2021-01-13

- fix headers on response payload

## 1.5.1 - 2021-01-13

- make the test response macro chainable

## 1.5.0 - 2021-01-13

- add `testResponse` method

## 1.4.0 - 2021-01-13

- let the `model` call accepts multiple models.

## 1.3.6 - 2021-01-13

- update `str_replace()` calls in `ray:publish-config` with `env()` usage (#82)

## 1.3.5 - 2021-01-12

- improve recognizing mails in logs

## 1.3.4 - 2021-01-09

- add `env()` vars for each Laravel config setting (#55)

## 1.3.3 - 2021-01-09

- add `enabled()` and `disabled()` methods (#54)

## 1.3.2 - 2021-01-09

- fix frame for `rd` function

## 1.3.1 - 2021-01-09

- fix broken `queries()`-method (#51)

## 1.3.0 - 2021-01-08

- Add `PublishConfigCommand`

## 1.2.0 - 2021-01-08

- add support for `local_path` and `remote_path` settings

## 1.1.0 - 2021-01-07

- add support for Lumen (#22)

## 1.0.3 - 20201-01-07

- fix incompatibilities on Windows (#20)
- fix host settings (#14)

## 1.0.2 - 2021-01-07

- fix deps

## 1.0.1 - 2021-01-07

- fix deps

## 1.0.0 - 2021-01-07

- initial release
