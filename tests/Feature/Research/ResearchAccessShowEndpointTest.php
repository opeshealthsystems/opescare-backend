<?php

namespace Tests\Feature\Research;

use App\Models\DataAccessCommitteeReview;
use App\Models\ResearchAccessRequest;
use App\Models\ResearchDataAgreement;
use App\Models\ResearcherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /v1/research/requests/{id}
 *
 * This endpoint threw on every single call. show() eager-loads
 * ['dacReviews', 'dataAgreements'], and ResearchAccessRequest defined neither —
 * only reviewer(). Laravel raises BadMethodCallException before the response is
 * ever built, so the endpoint was a guaranteed 500.
 *
 * Nothing covered it, which is exactly why a 979-test suite stayed green over a
 * dead route. These tests exist so it cannot rot back.
 *
 * Note the near-miss that made this look like a different bug: a separate
 * ResearchRequest model DOES define dacReviews(), and ResearcherProfile defines
 * dataAgreements(). The controller was not pointing at the wrong model — the
 * relations were simply never written on this one.
 */
class ResearchAccessShowEndpointTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = [
        'X-Client-ID'     => 'test_client_id',
        'X-Client-Secret' => 'test_client_secret',
    ];

    private function request(): ResearchAccessRequest
    {
        return ResearchAccessRequest::create([
            'requesting_organization' => 'Centre Pasteur du Cameroun',
            'principal_investigator'  => 'Dr. Amara Diallo',
            'purpose'                 => 'Retrospective diabetes outcomes study, Centre region.',
        ]);
    }

    public function test_show_returns_the_request(): void
    {
        $req = $this->request();

        $this->getJson("/api/v1/research/requests/{$req->id}", $this->headers)
            ->assertOk()
            ->assertJsonPath('data.id', $req->id)
            ->assertJsonPath('data.requesting_organization', 'Centre Pasteur du Cameroun')
            ->assertJsonPath('data.principal_investigator', 'Dr. Amara Diallo');
    }

    public function test_show_embeds_dac_reviews_and_data_agreements(): void
    {
        $req      = $this->request();
        $reviewer = User::factory()->create();

        DataAccessCommitteeReview::create([
            'research_access_request_id' => $req->id,
            'reviewer_id'                => $reviewer->id,
            'decision'                   => 'approved',
            'comments'                   => 'Ethics clearance sighted.',
            'reviewed_at'                => now(),
        ]);

        $profile = ResearcherProfile::create([
            'full_name'   => 'Dr. Amara Diallo',
            'email'       => 'a.diallo@pasteur-yaounde.cm',
            'institution' => 'Centre Pasteur du Cameroun',
            'status'      => 'verified',
        ]);

        ResearchDataAgreement::create([
            'research_access_request_id' => $req->id,
            'researcher_profile_id'      => $profile->id,
            'agreement_text'             => 'Data use agreement, v1.',
            'signed'                     => false,
        ]);

        $res = $this->getJson("/api/v1/research/requests/{$req->id}", $this->headers)->assertOk();

        $res->assertJsonCount(1, 'data.dac_reviews')
            ->assertJsonPath('data.dac_reviews.0.decision', 'approved')
            ->assertJsonPath('data.dac_reviews.0.research_access_request_id', $req->id)
            ->assertJsonCount(1, 'data.data_agreements')
            ->assertJsonPath('data.data_agreements.0.research_access_request_id', $req->id);
    }

    public function test_index_omits_the_relations_rather_than_emitting_null(): void
    {
        // index() does not eager-load, so whenLoaded should drop the keys
        // entirely. Emitting null there would invite an N+1 per row.
        $this->request();

        $res = $this->getJson('/api/v1/research/requests', $this->headers)->assertOk();

        $first = $res->json('data.0') ?? $res->json('data');
        $this->assertIsArray($first);
        $this->assertArrayNotHasKey('dac_reviews', $first);
        $this->assertArrayNotHasKey('data_agreements', $first);
    }

    public function test_unknown_id_is_not_found_rather_than_a_server_error(): void
    {
        $this->getJson('/api/v1/research/requests/' . fake()->uuid(), $this->headers)
            ->assertNotFound();
    }
}
