<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreateAndJoinMeetingTest extends TestCase
{
    protected string $bbbBaseUrl = 'https://test-install.blindsidenetworks.com/bigbluebutton/api/';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.bigbluebutton.base_url' => $this->bbbBaseUrl,
            'services.bigbluebutton.secret' => '8cd8ef52e8e101574e400365b55e11a6',
            'services.bigbluebutton.checksum_algorithm' => 'sha1',
        ]);
    }

    public function test_join_meeting_creates_unique_meeting_and_redirects(): void
    {
        $createdMeetingIds = [];

        Http::fake([
            $this->bbbBaseUrl.'create*' => function ($request) use (&$createdMeetingIds) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);
                $createdMeetingIds[] = $params['meetingID'];

                $xml = <<<XML
<response>
  <returncode>SUCCESS</returncode>
  <meetingID>{$params['meetingID']}</meetingID>
  <internalMeetingID>internal-{$params['meetingID']}</internalMeetingID>
  <moderatorPW>mod_pw_xyz</moderatorPW>
  <attendeePW>att_pw_abc</attendeePW>
  <createTime>1788424784000</createTime>
</response>
XML;

                return Http::response($xml, 200);
            },
        ]);

        $response = $this->post('/meetings/join', [
            'fullName' => 'James (Tutor)',
        ], [
            'X-Inertia' => 'true',
        ]);

        // Inertia external redirect status (409 Conflict with X-Inertia-Location header)
        $response->assertStatus(409);
        $redirectUrl = $response->headers->get('X-Inertia-Location');
        $this->assertNotEmpty($redirectUrl);

        // Verify meeting ID format: trial-{uuid}
        $this->assertCount(1, $createdMeetingIds);
        $meetingId = $createdMeetingIds[0];
        $this->assertStringStartsWith('trial-', $meetingId);
        $this->assertEquals(42, strlen($meetingId)); // 'trial-' (6) + 36 char UUID = 42

        // Verify join URL points to BBB join endpoint as MODERATOR
        $this->assertStringStartsWith($this->bbbBaseUrl.'join?', $redirectUrl);
        $this->assertStringContainsString('role=MODERATOR', $redirectUrl);
        $this->assertStringContainsString('meetingID='.$meetingId, $redirectUrl);
        $this->assertStringContainsString('password=mod_pw_xyz', $redirectUrl);
        $this->assertStringContainsString('checksum=', $redirectUrl);
    }

    public function test_subsequent_requests_generate_different_unique_meeting_ids(): void
    {
        $createdMeetingIds = [];

        Http::fake([
            $this->bbbBaseUrl.'create*' => function ($request) use (&$createdMeetingIds) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);
                $createdMeetingIds[] = $params['meetingID'];

                $xml = <<<XML
<response>
  <returncode>SUCCESS</returncode>
  <meetingID>{$params['meetingID']}</meetingID>
  <moderatorPW>mpw</moderatorPW>
  <attendeePW>apw</attendeePW>
  <createTime>1788424784000</createTime>
</response>
XML;

                return Http::response($xml, 200);
            },
        ]);

        $response1 = $this->post('/meetings/join', [], ['X-Inertia' => 'true']);
        $response2 = $this->post('/meetings/join', [], ['X-Inertia' => 'true']);

        $response1->assertStatus(409);
        $response2->assertStatus(409);

        $this->assertCount(2, $createdMeetingIds);
        $this->assertNotEquals($createdMeetingIds[0], $createdMeetingIds[1]);
        $this->assertStringStartsWith('trial-', $createdMeetingIds[0]);
        $this->assertStringStartsWith('trial-', $createdMeetingIds[1]);
    }

    public function test_join_meeting_handles_api_failure_gracefully(): void
    {
        Http::fake([
            $this->bbbBaseUrl.'create*' => Http::response(
                '<response><returncode>FAILED</returncode><messageKey>internalError</messageKey><message>Server unavailable</message></response>',
                200
            ),
        ]);

        $response = $this->post('/meetings/join');

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $errorMessage = session('error');
        $this->assertStringContainsString('BigBlueButton API error', $errorMessage);

        // Verify shared secret is never leaked
        $this->assertStringNotContainsString('8cd8ef52e8e101574e400365b55e11a6', $errorMessage);
    }
}
