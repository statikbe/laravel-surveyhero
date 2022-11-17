# Changelog

All notable changes to `laravel-surveyhero` will be documented in this file.

## v1.4.2 - 2022-11-17

Fixed collector retrieval in webhook controller

## 1.4.1 - 2022-11-16

added public function deleteSurveyResponse($surveyId, $responseId) to SurveyResponseImportService::class to delete survey responses

## v1.4.0 - 2022-10-27

Add data export functionality to spreadsheet

## v1.3.7 - 2022-10-26

Fix typo in webhook command
Add webhook handler controller for response completed webhook

## V1.3.6 - 2022-10-19

Added command to generate webhooks for your surveys

surveyhero:add-webooks
{--survey=all : The Surveyhero survey ID}
{--eventType= : Webhooks event type (https://developer.surveyhero.com/api/#webhooks-event-types)}
{--url= : The URL the webhook should call}

## v1.3.5 - 2022-10-19

- Add collectors to mapping command
- Fix mapping command formatting when mapping multiple surveys

## 1.3.4 - 2022-09-30

Support for answer_mapping to subquestions of choice_table type.

## v1.3.3 - 2022-09-26

- add convertedValue() convenience function that returns the converted value without specifying the type.

## v1.3.2 - 2022-09-21

- Fix bug with calculation of last imported timestamp of survey, which cause not all responses to be imported

## v1.3.1 - 2022-09-21

Fix import refresh bugs & response updates

## v1.3.0 - 2022-09-21

- support for collector IDs
- fix & improve command output of response import cmd
- add response import info DTO to cleanup error output of response import cmd
- casting survey_last_imported var to Carbon

## v1.2.0 - 2022-09-15

- customisable data models & tables
- survey question mapper command output improvements
- survey question mapper command bug fixes
- survey import command bug fixes
- data model nullable on survey_language
- choice table question mapper bug fixes
- removal of old question responses in case the response is incomplete and new data exists.
- bug fixes in Q & A import

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
