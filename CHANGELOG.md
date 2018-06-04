# Change log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [[*next-version*]] - YYYY-MM-DD
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
