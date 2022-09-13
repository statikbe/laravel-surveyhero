<?php

// config for Statikbe/Surveyhero
return [
    /**
     * The Surveyhero API URL:
     */
    'api_url' => env('SURVEYHERO_API_URL', 'https://api.surveyhero.com/v1/'),

    /**
     * The Surveyhero API user name and password:
     */
    'api_username' => env('SURVEYHERO_API_USERNAME'),
    'api_password' => env('SURVEYHERO_API_PASSWORD'),

    'models' => [

        /*
         * TODO
         * When using the "HasPermissions" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Spatie\Permission\Contracts\Permission` contract.
         */

        'survey' => Statikbe\Surveyhero\Models\Survey::class,

        /*
         * TODO
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Spatie\Permission\Contracts\Role` contract.
         */

        'survey_question' => Statikbe\Surveyhero\Models\SurveyQuestion::class,
        'survey_answer' => Statikbe\Surveyhero\Models\SurveyAnswer::class,
        'survey_response' => Statikbe\Surveyhero\Models\SurveyResponse::class,
        'survey_question_response' => Statikbe\Surveyhero\Models\SurveyQuestionResponse::class,
    ],

    'table_names' => [
        /* TODO
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */
        'surveys' => 'surveys',
        'survey_questions' => 'survey_questions',
        'survey_answers' => 'survey_answers',
        'survey_responses' => 'survey_responses',
        'survey_question_responses' => 'survey_question_responses',
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
            'questions' => [
                'question_id' => 1000001,
                'type' => 'choice_table',
                'subquestion_mapping' => [
                    [
                        'question_id' => 13509163,
                        'field' => 'question_1',
                    ],
                    [
                        'question_id' => 13509164,
                        'field' => 'question_2',
                    ],
                    [
                        'question_id' => 13509165,
                        'field' => 'question_3',
                    ],
                ],
                'answer_mapping' => [
                    13509163 => 1,
                    13509164 => 2,
                    13509165 => 3,
                ],
                'mapped_data_type' => 'int', //can also be string if the values are strings in answer_mapping
            ],

            [
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

            [
                'question_id' => 1000005,
                'type' => 'text',
                'field' => 'question_5',
            ],

            [
                'question_id' => 1000006,
                'type' => 'number',
                'field' => 'age',
                'mapped_data_type' => 'int',
            ],
        ],
    ],
];
