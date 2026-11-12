<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Saloon\Http\Faking\MockResponse;
use Statikbe\Surveyhero\Exceptions\UnwantedResponseNotImportedException;
use Statikbe\Surveyhero\Facades\Surveyhero;
use Statikbe\Surveyhero\Http\Controllers\Api\SurveyheroWebhookController;
use Statikbe\Surveyhero\Http\Requests\GetSurveyResponseAnswersRequest;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;
use Statikbe\Surveyhero\Tests\Support\RejectingWebhookController;

const WEBHOOK_SECRET = 'test-webhook-secret-0123456789abcdef';

/**
 * A response as returned by the Surveyhero API for GET surveys/{survey}/responses/{response}.
 */
function apiResponse(array $overrides = []): MockResponse
{
    return MockResponse::make(array_merge([
        'response_id' => 9001,
        'collector_id' => 9876543,
        'started_on' => '2024-06-01T10:00:00+00:00',
        'last_updated_on' => '2024-06-01T11:00:00+00:00',
        'email_address' => 'respondent@example.com',
        'language' => ['code' => 'en'],
        'link_parameters' => null,
        'ip_address' => '192.0.2.1',
        'status' => 'completed',
        'answers' => [],
    ], $overrides));
}

function webhookPayload(array $data = []): array
{
    return [
        'event_type' => 'response.completed',
        'data' => array_merge([
            'survey_id' => 1234567,
            'collector_id' => 9876543,
            'response_id' => 9001,
            'email_address' => 'respondent@example.com',
            'ip_address' => '192.0.2.1',
        ], $data),
    ];
}

function webhookUrl(string $token = WEBHOOK_SECRET): string
{
    return '/process-surveyhero-response-completed/'.$token;
}

beforeEach(function () {
    config()->set('surveyhero.webhook_secret', WEBHOOK_SECRET);
    Surveyhero::webhookRoutes();
    Route::getRoutes()->refreshNameLookups();

    [, $this->mockClient] = $this->makeSurveyheroClient([
        GetSurveyResponseAnswersRequest::class => apiResponse(),
    ]);

    // The test config maps this survey with collectors [9876543].
    $this->survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
});

describe('authentication', function () {
    it('rejects every call when no secret is configured', function (?string $secret) {
        config()->set('surveyhero.webhook_secret', $secret);

        $this->postJson(webhookUrl(), webhookPayload())
            ->assertForbidden()
            ->assertExactJson(['error' => 'Forbidden.']);

        $this->mockClient->assertNothingSent();
        expect(SurveyResponse::count())->toBe(0);
    })->with([null, '']);

    it('rejects a wrong token', function () {
        $this->postJson(webhookUrl('wrong-token'), webhookPayload())
            ->assertForbidden()
            ->assertExactJson(['error' => 'Forbidden.']);

        $this->mockClient->assertNothingSent();
    });

    it('rejects a missing token, also on a hand-registered route', function () {
        $this->postJson('/process-surveyhero-response-completed', webhookPayload())->assertNotFound();

        Route::post('/hand-registered-webhook', [SurveyheroWebhookController::class, 'handleResponseCompletedWebhook']);

        $this->postJson('/hand-registered-webhook', webhookPayload())
            ->assertForbidden()
            ->assertExactJson(['error' => 'Forbidden.']);
        $this->postJson('/hand-registered-webhook?token[]='.WEBHOOK_SECRET, webhookPayload())->assertForbidden();

        $this->mockClient->assertNothingSent();
    });

    it('does not accept the token from the request body', function () {
        Route::post('/hand-registered-webhook', [SurveyheroWebhookController::class, 'handleResponseCompletedWebhook']);

        $this->postJson('/hand-registered-webhook', webhookPayload() + ['token' => WEBHOOK_SECRET])->assertForbidden();
    });

    it('accepts the token as route parameter', function () {
        $this->postJson(webhookUrl(), webhookPayload())
            ->assertOk()
            ->assertExactJson(['message' => 'Response imported.']);
    });

    it('accepts the token as query parameter on a hand-registered route', function () {
        Route::post('/hand-registered-webhook', [SurveyheroWebhookController::class, 'handleResponseCompletedWebhook']);

        $this->postJson('/hand-registered-webhook?token='.WEBHOOK_SECRET, webhookPayload())
            ->assertOk()
            ->assertExactJson(['message' => 'Response imported.']);

        $this->postJson('/hand-registered-webhook?token=wrong', webhookPayload())->assertForbidden();
    });

    it('never logs the token', function () {
        Log::spy();

        $this->postJson(webhookUrl('wrong-'.WEBHOOK_SECRET), webhookPayload());
        $this->postJson(webhookUrl(), webhookPayload());

        foreach (['info', 'warning', 'error', 'debug'] as $level) {
            Log::shouldNotHaveReceived($level, fn (...$args) => str_contains(json_encode($args), WEBHOOK_SECRET));
        }
    });
});

describe('payload validation', function () {
    it('rejects invalid IDs with a 400', function (array $data) {
        $this->postJson(webhookUrl(), webhookPayload($data))
            ->assertBadRequest()
            ->assertExactJson(['error' => 'Surveyhero response data is not valid.']);

        $this->mockClient->assertNothingSent();
    })->with([
        'non-numeric survey_id' => [['survey_id' => 'abc']],
        'numeric string with suffix' => [['response_id' => '9001 OR 1=1']],
        'negative collector_id' => [['collector_id' => -5]],
        'zero response_id' => [['response_id' => 0]],
        'float response_id' => [['response_id' => 9001.5]],
        'array survey_id' => [['survey_id' => [1234567]]],
        'array response_id' => [['response_id' => ['id' => 9001]]],
        'null collector_id' => [['collector_id' => null]],
        'boolean response_id' => [['response_id' => true]],
        'overlong digits' => [['response_id' => str_repeat('9', 30)]],
    ]);

    it('rejects a payload without data or with missing IDs', function (array $payload) {
        $this->postJson(webhookUrl(), $payload)->assertBadRequest();
    })->with([
        'no data' => [['event_type' => 'response.completed']],
        'data is a string' => [['data' => '1234567']],
        'missing response_id' => [['data' => ['survey_id' => 1234567, 'collector_id' => 9876543]]],
    ]);

    it('logs only the IDs of an invalid request, not the payload', function () {
        Log::spy();

        $this->postJson(webhookUrl(), webhookPayload(['response_id' => 'abc']))->assertBadRequest();

        Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context) {
            return $context === ['survey_id' => 1234567, 'collector_id' => 9876543, 'response_id' => 'abc'];
        })->once();
        Log::shouldNotHaveReceived('error', fn (...$args) => str_contains(json_encode($args), 'respondent@example.com'));
    });

    it('accepts numeric strings and passes int IDs to the hooks', function () {
        RejectingWebhookController::$receivedResponseData = [];
        Route::post('/rejecting-webhook/{token}', [RejectingWebhookController::class, 'handleResponseCompletedWebhook']);

        $this->postJson('/rejecting-webhook/'.WEBHOOK_SECRET, webhookPayload([
            'survey_id' => '1234567',
            'collector_id' => '9876543',
            'response_id' => '9001',
        ]))->assertOk();

        expect(RejectingWebhookController::$receivedResponseData)->toHaveCount(1);
        $received = RejectingWebhookController::$receivedResponseData[0];
        expect($received['survey_id'])->toBe(1234567)
            ->and($received['collector_id'])->toBe(9876543)
            ->and($received['response_id'])->toBe(9001)
            ->and($received)->not->toHaveKey('token');
    });
});

describe('responses that are not imported', function () {
    it('gives the same reply for an unknown survey, a disallowed collector and an unwanted response', function () {
        Route::post('/rejecting-webhook/{token}', [RejectingWebhookController::class, 'handleResponseCompletedWebhook']);

        $unknownSurvey = $this->postJson(webhookUrl(), webhookPayload(['survey_id' => 7777777]));
        $disallowedCollector = $this->postJson(webhookUrl(), webhookPayload(['collector_id' => 1111111]));
        $unwanted = $this->postJson('/rejecting-webhook/'.WEBHOOK_SECRET, webhookPayload());

        foreach ([$unknownSurvey, $disallowedCollector, $unwanted] as $response) {
            $response->assertOk()->assertExactJson(['message' => 'Response not imported.']);
        }
        expect($unknownSurvey->getContent())
            ->toBe($disallowedCollector->getContent())
            ->toBe($unwanted->getContent());

        $this->mockClient->assertNothingSent();
        expect(SurveyResponse::count())->toBe(0);
    });

    it('does not import a response whose fetched collector is not allowed, even if the payload claims an allowed one', function () {
        [, $mockClient] = $this->makeSurveyheroClient([
            GetSurveyResponseAnswersRequest::class => apiResponse(['collector_id' => 1111111]),
        ]);

        $this->postJson(webhookUrl(), webhookPayload(['collector_id' => 9876543]))
            ->assertOk()
            ->assertExactJson(['message' => 'Response not imported.']);

        $mockClient->assertSentCount(1);
        expect(SurveyResponse::count())->toBe(0);
    });

    it('does not import a response that the API reports for another survey', function () {
        $this->makeSurveyheroClient([
            GetSurveyResponseAnswersRequest::class => apiResponse(['survey_id' => 7654321]),
        ]);

        $this->postJson(webhookUrl(), webhookPayload())
            ->assertOk()
            ->assertExactJson(['message' => 'Response not imported.']);

        expect(SurveyResponse::count())->toBe(0);
    });

    it('treats an unwanted response thrown by the import service as not imported', function () {
        $this->mock(SurveyResponseImportService::class)
            ->shouldReceive('importSurveyResponse')
            ->andThrow(UnwantedResponseNotImportedException::create(['response_id' => 9001], 'nope'));

        $this->postJson(webhookUrl(), webhookPayload())
            ->assertOk()
            ->assertExactJson(['message' => 'Response not imported.']);
    });
});

describe('importing', function () {
    it('imports the response with a valid token', function () {
        $this->postJson(webhookUrl(), webhookPayload())
            ->assertOk()
            ->assertExactJson(['message' => 'Response imported.']);

        $response = SurveyResponse::sole();
        expect($response->surveyhero_id)->toBe(9001)
            ->and($response->survey_id)->toBe($this->survey->id);
    });

    it('stores the response ID returned by the API, not the one in the payload', function () {
        $this->makeSurveyheroClient([
            GetSurveyResponseAnswersRequest::class => apiResponse(['response_id' => 9005]),
        ]);

        $this->postJson(webhookUrl(), webhookPayload(['response_id' => 9001]))->assertOk();

        expect(SurveyResponse::sole()->surveyhero_id)->toBe(9005);
    });

    it('creates one row when the same response is delivered twice', function () {
        $this->makeSurveyheroClient([
            GetSurveyResponseAnswersRequest::class => apiResponse(['status' => 'incomplete']),
        ]);

        $this->postJson(webhookUrl(), webhookPayload())->assertOk();
        $this->postJson(webhookUrl(), webhookPayload())->assertOk();

        expect(SurveyResponse::where('surveyhero_id', 9001)->count())->toBe(1);
    });

    it('updates the row when a concurrent request inserted the same response first', function () {
        // Simulate a parallel webhook call that inserts the row after both of our lookups (the import service's and
        // the one inside updateOrCreate()), so our own insert hits the unique index on surveyhero_id.
        $lookups = 0;
        DB::listen(function (QueryExecuted $query) use (&$lookups) {
            if (! str_starts_with($query->sql, 'select * from "survey_responses"') || ! str_contains($query->sql, '"surveyhero_id" = ?')) {
                return;
            }
            if (++$lookups === 2) {
                DB::table('survey_responses')->insert([
                    'surveyhero_id' => 9001,
                    'survey_id' => $this->survey->id,
                    'survey_completed' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->postJson(webhookUrl(), webhookPayload())
            ->assertOk()
            ->assertExactJson(['message' => 'Response imported.']);

        // The import service's lookup, updateOrCreate()'s lookup, and createOrFirst()'s re-fetch after the violation.
        expect($lookups)->toBe(3);
        $response = SurveyResponse::sole();
        expect($response->surveyhero_id)->toBe(9001)
            ->and($response->survey_language)->toBe('en')
            ->and($response->survey_completed)->toBeTruthy();
    });

    it('returns a generic error without the exception message when the import fails', function () {
        $this->mock(SurveyResponseImportService::class)
            ->shouldReceive('importSurveyResponse')
            ->andThrow(new RuntimeException('SQLSTATE[23000]: insert into survey_responses (resume_link) values (https://secret-resume-link)'));

        $response = $this->postJson(webhookUrl(), webhookPayload())
            ->assertStatus(500)
            ->assertExactJson(['error' => 'Import failed.']);

        expect($response->getContent())->not->toContain('SQLSTATE')
            ->not->toContain('secret-resume-link');
    });
});
