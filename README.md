# UrlTemplate

[![Build Status](https://github.com/innmind/urltemplate/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/urltemplate/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/urltemplate/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/urltemplate)
[![Type Coverage](https://shepherd.dev/github/innmind/urltemplate/coverage.svg)](https://shepherd.dev/github/innmind/urltemplate)

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
    Map::of(['term', 'dog']),
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
