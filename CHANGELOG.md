# Changelog

All notable changes to `laravel-surveyhero` will be documented in this file.

## v1.1.3 - 2022-09-06

Renaming survey->survey_last_updated to survey_last_imported

## v1.1.2 - 2022-09-05

Fix PHPstan errors

## v1.1.1 - 2022-09-05

- Avoid re-importing unupdated responses

## v1.1.0 - 2022-09-05

- Improved data model to normalise the field column on questions
- Refactor Q&A import
- Added support for more question types in Q&A import, question mapper and response creator
- Update docs

## v1.0.1 - 2022-08-17

Added survey_questions and survey_answers tables and mapping commands

## v1.0.0 - 2022-08-04

First version with data model and Surveyhero import
