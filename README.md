# Import Surveyhero responses into the Laravel database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/statikbe/laravel-surveyhero.svg?style=flat-square)](https://packagist.org/packages/statikbe/laravel-surveyhero)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/statikbe/laravel-surveyhero/run-tests?label=tests)](https://github.com/statikbe/laravel-surveyhero/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/statikbe/laravel-surveyhero/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/statikbe/laravel-surveyhero/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/statikbe/laravel-surveyhero.svg?style=flat-square)](https://packagist.org/packages/statikbe/laravel-surveyhero)

This package allows you to import [Surveyhero](https://www.surveyhero.com) survey responses. 

## Installation

You can install the package via composer:

```bash
composer require statikbe/laravel-surveyhero
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="surveyhero-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="surveyhero-config"
```

This is the contents of the published config file:

```php
return [
];
```

https://api.surveyhero.com/v1/surveys/53635/elements

## Usage

```php
$surveyhero = new Statikbe\Surveyhero();
echo $surveyhero->echoPhrase('Hello, Statikbe!');
```

## Data model

``` mermaid
erDiagram
SURVEY {
    num id
    num surveyhero_id
    string name
    
}
SURVEY_RESPONSE {
    num id
    num surveyhero_id
    datetime survey_start_date
    datetime survey_last_updated
    string survey_language
    bool survey_completed
    num survey_id FK
}
SURVEY_QUESTION_RESPONSE {
    num surveyhero_question_id
    num surveyhero_answer_id
    string surveyhero_answer_lbl
    string field
    string converted_string_value
    num converted_int_value
    num survey_response_id FK
}
SURVEY ||--o{ SURVEY_RESPONSE : contains
SURVEY_RESPONSE ||--o{ SURVEY_QUESTION_RESPONSE : has
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

You can post an issue and provide a pull request. Thanks!

## Credits

- [Sten Govaerts](https://github.com/statikbe)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
