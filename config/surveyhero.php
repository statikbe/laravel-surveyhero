<?php

// config for Statikbe/Surveyhero
return [
    //The Surveyhero API URL:
    'api_url' => env('SURVEYHERO_API_URL', 'https://api.surveyhero.com/v1/'),

    //The Surveyhero API user name and password:
    'api_username' => env('SURVEYHERO_API_USERNAME'),
    'api_password' => env('SURVEYHERO_API_PASSWORD'),

    //Map here the link_parameters response from Surveyhero to the database columns of the SurveyResponse model.
    //You need to extend the SurveyResponse model with a migration to include the necessary fields.
    //e.g. 'organisation' => 'uuid',
    'surveyhero_link_parameters_mapping' => [

    ],

    'question_mapping' => [
        [
            [
                'question_id' => 5410053,
                'type' => 'choice_table',
                'subquestion_mapping' => [
                    [
                        'question_id' => 13509163,
                        'field' => 'role_conflict_1',
                    ],
                    [
                        'question_id' => 13509164,
                        'field' => 'role_conflict_2',
                    ],
                    [
                        'question_id' => 13509165,
                        'field' => 'role_conflict_3',
                    ],
                ],
                'answer_mapping' => [
                    13509163 => 1,
                    13509164 => 2,
                    13509165 => 3,
                ],
                'mapped_data_type' => 'int',
            ],

            [
                'question_id' => 5410054,
                'type' => 'choices',
                'field' => 'team_conflict',
                'answer_mapping' => [
                    13509166 => 1,
                    13509167 => 2,
                    13509168 => 3,
                ],
                'mapped_data_type' => 'int',
            ],

            [
                'question_id' => 5410055,
                'type' => 'text',
                'field' => 'job_description',
            ],

            [
                'question_id' => 5410056,
                'type' => 'number',
                'field' => 'age',
                'mapped_data_type' => 'int',
            ],
        ],
    ],
];
