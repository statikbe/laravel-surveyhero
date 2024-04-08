<?php

// config for Statikbe/Surveyhero
return [
    /**
     * The Surveyhero API URL:
     */
    'api_url' => env('SURVEYHERO_API_URL', 'https://api.surveyhero.com/v1/'),

    /**
     * The Surveyhero API username and password:
     */
    'api_username' => env('SURVEYHERO_API_USERNAME'),
    'api_password' => env('SURVEYHERO_API_PASSWORD'),

    /**
     * To allow you to override the default models that this package provides, in case you want to add other fields,
     * relationships or functions to the survey related models, you can define your own Eloquent Models here for each
     * data model.
     */
    'models' => [
        'survey' => Statikbe\Surveyhero\Models\Survey::class,
        'survey_question' => Statikbe\Surveyhero\Models\SurveyQuestion::class,
        'survey_answer' => Statikbe\Surveyhero\Models\SurveyAnswer::class,
        'survey_response' => Statikbe\Surveyhero\Models\SurveyResponse::class,
        'survey_question_response' => Statikbe\Surveyhero\Models\SurveyQuestionResponse::class,
    ],

    /**
     * If you overwrite your data models, you might also want to change the database table names. You can do this by
     * defining the table name and the foreign key used to create relationships in other tables.
     */
    'table_names' => [
        'surveys' => [
            'name' => 'surveys',
            'foreign_key' => 'survey_id',
        ],
        'survey_questions' => [
            'name' => 'survey_questions',
            'foreign_key' => 'survey_question_id',
        ],
        'survey_answers' => [
            'name' => 'survey_answers',
            'foreign_key' => 'survey_answer_id',
        ],
        'survey_responses' => [
            'name' => 'survey_responses',
            'foreign_key' => 'survey_response_id',
        ],
        'survey_question_responses' => [
            'name' => 'survey_question_responses',
            'foreign_key' => 'survey_question_response_id',
        ],
    ],

    /**
     * Here you can map the link_parameters response from Surveyhero to the database columns of the SurveyResponse model.
     * You need to extend the SurveyResponse model with a migration to include the necessary fields.
     *
     * The key is the name of the Surveyhero link parameter
     * 'name' represents the column in the survey_response table to which we save the field
     *
     * Following parameters are optional in case you want to evaluate the link_parameters value on the database
     * 'entity' represents the model you're querying on
     * 'value' represents the field you're comparing on your model
     * 'field' represents the field from your model to store in de database
     */
    'surveyhero_link_parameters_mapping' => [
    /**
     * example 1:
     *
     * 'username' => [
     *     'name' => 'user_name'                         (the target column name on the survey_response table)
     * ],
     */

    /**
     * example 2:
     * Behind the scenes this will save the result of
     * \App\Models\User::where('uuid', $linkParameterValue)->first()->id to user_id on the survey_response table
     *
     * 'user_uuid' => [
     *     'name' => 'user_id',                         (the target column name on the survey_response table)
     *     'entity' => \App\Models\User::class,         (the model to query)
     *     'value' => 'uuid',                           (the column on the entity to query)
     *     'field' => 'id',                             (the column on the entity to select)
     * ],
     */
    ],

    /**
     * Map here the Surveyhero questions. Check the documentation for the formatting of each question type.
     * Below are 4 example question types
     */
    'question_mapping' => [
        [
            'survey_id' => 1234567,
            'collectors' => [9876543], //optionally, see https://developer.surveyhero.com/api/#collector-api
            'use_resume_link' => false,
            'questions' => [
                1000001 => [
                    'question_id' => 1000001,
                    'type' => 'choice_table',
                    'subquestion_mapping' => [
                        13509163 => [
                            'question_id' => 13509163,
                            'field' => 'question_1',
                        ],
                        13509164 => [
                            'question_id' => 13509164,
                            'field' => 'question_2',
                        ],
                        13509165 => [
                            'question_id' => 13509165,
                            'field' => 'question_3',
                            'answer_mapping' => [
                                13509163 => 3,
                                13509164 => 2,
                                13509165 => 1,
                            ],
                        ],
                    ],
                    'answer_mapping' => [
                        13509163 => 1,
                        13509164 => 2,
                        13509165 => 3,
                    ],
                    'mapped_data_type' => 'int', //can also be string if the values are strings in answer_mapping
                ],
                1000002 => [
                    'question_id' => 1000002,
                    'type' => 'choices',
                    'field' => 'question_4',
                    'answer_mapping' => [
                        13509166 => 1,
                        13509167 => 2,
                        13509168 => 3,
                    ],
                    'mapped_data_type' => 'int', //can also be string if the values are strings in answer_mapping
                ],

                1000005 => [
                    'question_id' => 1000005,
                    'type' => 'text',
                    'field' => 'question_5',
                ],

                1000006 => [
                    'question_id' => 1000006,
                    'type' => 'number',
                    'field' => 'age',
                    'mapped_data_type' => 'int',
                ],
            ],
        ],
    ],
];
