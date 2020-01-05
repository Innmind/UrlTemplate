# UrlTemplate

| `develop` |
|-----------|
| [![codecov](https://codecov.io/gh/Innmind/UrlTemplate/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/UrlTemplate) |
| [![Build Status](https://github.com/Innmind/UrlTemplate/workflows/CI/badge.svg)](https://github.com/Innmind/UrlTemplate/actions?query=workflow%3ACI) |

[RFC6570](https://tools.ietf.org/html/rfc6570) implementation.

## Installation

```sh
composer require innmind/url-template
```

## Usage

```php
use Innmind\UrlTemplate\Template;
use Innmind\Immutable\Map;
use Innmind\Url\Url;

$url = Template::of('http://example.com/dictionary/{term:1}/{term}')->expand(
    Map::of('string', 'scalar|array')
        ('term', 'dog')
);
$url instanceof Url; // true
$url->toString(); // http://example.com/dictionary/d/dog

$variables = Template::of('http://example.com/dictionary/{term:1}/{term}')->extract(
    Url::of('http://example.com/dictionary/d/dog')
);
$variables; // Map<string, string>
$variables->size(); // 1
$variables->get('term'); // dog
```

*Important*: variable extraction is not supported for list (ie `{foo*}` expression).
