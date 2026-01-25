# Changelog

## 4.0.0 - 2026-01-25

### Added

- `Innmind\UrlTemplate\Template::attempt()`
- `Innmind\UrlTemplate\Template::expansion()`

### Changed

- Requires `innmind/immutable:~6.0`
- Requires `innmind/url:~5.0`
- Requires PHP `8.4`
- `Innmind\UrlTemplate\Template::extract()` now returns an `Innmind\Immutable\Attempt`
- `Innmind\UrlTemplate\Template::matches()` now returns an `Innmind\Immutable\Attempt`

### Removed

- `Innmind\UrlTemplate\Template::expand()`, use `::expansion()` instead
- `Innmind\UrlTemplate\Exception\Exception`
- `Innmind\UrlTemplate\Exception\LogicException`
- `Innmind\UrlTemplate\Exception\DomainException`
- `Innmind\UrlTemplate\Exception\ExplodeExpressionCantBeMatched`

## 3.1.0 - 2023-09-16

### Added

- Support for `innmind/immutable` `5`

### Removed

- Support for PHP `8.1`
