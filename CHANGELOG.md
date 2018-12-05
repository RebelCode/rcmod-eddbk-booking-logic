# Change log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [[*next-version*]] - YYYY-MM-DD

## [0.1-alpha8] - 2018-12-05
### Changed
- Using the new collective `rebelcode/booking-system` package.
- Updated booking validator conditions to conform to bookings and sessions having multiple resources.

## [0.1-alpha7] - 2018-08-01
### Changed
- Draft bookings no longer block new bookings.
- Non-blocking booking statuses are now saved in config.
- Now compatible with latest version of `rebelcode/rcmod-booking-logic` module.

## [0.1-alpha6] - 2018-06-13
### Changelog
- Now depending on `wp_bookings_cqrs`.
- Bookings with `cancelled` status no longer block other bookings.

## [0.1-alpha5] - 2018-06-11
### Changed
- Statuses have now available transitions to themselves.

### Added
- Now overriding the unbooked sessions condition to ignore `in_cart` bookings.

## [0.1-alpha4] - 2018-06-04
### Added
- Now restricting `complete` transition to past bookings only.

## [0.1-alpha3] - 2018-05-24
### Fixed
- The booking collision condition now excludes the booking, which is being checked for collision.

## [0.1-alpha2] - 2018-05-24
### Changed
- Bookings with `in_cart` status no longer collide, meaning that there can be more than one such booking for the same resource.
- Bookings with status `approved` and `scheduled` are no longer exepmt from colliding.
- Booking collision expressions are now created by a separate, injected factory, making it overridable separately from the general validation logic.

## [0.1-alpha1] - 2018-05-21
Initial version.
