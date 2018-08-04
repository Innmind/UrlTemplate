# UrlTemplate

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/UrlTemplate/build-status/develop) |

[RFC6570](https://tools.ietf.org/html/rfc6570) implementation.

## Installation

```sh
composer require innmind/url-template
```

## Usage

```php
use Innmind\UrlTemplate\Template;
use Innmind\Immutable\Map;
use Innmind\Url\UrlInterface;

$url = Template::of('http://example.com/dictionary/{term:1}/{term}')->expand(
    (new Map('string', 'variable'))
        ->put('term', 'dog')
);
$url instanceof UrlInterface; // true
(string) $url; // http://example.com/dictionary/d/dog

$variables = Template::of('http://example.com/dictionary/{term:1}/{term}')->extract(
    Url::fromString('http://example.com/dictionary/d/dog')
);
$variables; // MapInterface<string, string>
$variables->size(); // 1
$variables->get('term'); // dog
```

*Important*: variable extraction is not supported for list (ie `{foo*}` expression).
