<?php

namespace Tests\Unit;

use App\Exceptions\BigBlueButtonException;
use App\Services\BigBlueButtonClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BigBlueButtonClientTest extends TestCase
{
    protected BigBlueButtonClient $client;

    protected string $testBaseUrl = 'https://bbb.example.com/bigbluebutton/api/';

    protected string $testSecret = 'test_secret_salt_12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new BigBlueButtonClient($this->testBaseUrl, $this->testSecret, 'sha1');
    }

    public function test_calculate_checksum_matches_sha1_specification(): void
    {
        $call = 'create';
        $params = [
            'meetingID' => 'room-42',
            'name' => 'Physics Class',
        ];

        // BBB specification: sha1(call + sorted_rfc3986_query + secret)
        $expectedQuery = 'meetingID=room-42&name=Physics%20Class';
        $expectedChecksum = sha1($call.$expectedQuery.$this->testSecret);

        $actualChecksum = $this->client->calculateChecksum($call, $params);

        $this->assertEquals($expectedChecksum, $actualChecksum);
    }

    public function test_calculate_checksum_sorts_parameters_alphabetically(): void
    {
        $call = 'join';
        $params = [
            'role' => 'MODERATOR',
            'fullName' => 'Jane Doe',
            'meetingID' => 'alpha-1',
        ];

        // Should sort to: fullName=...&meetingID=...&role=...
        $expectedQuery = 'fullName=Jane%20Doe&meetingID=alpha-1&role=MODERATOR';
        $expectedChecksum = sha1($call.$expectedQuery.$this->testSecret);

        $actualChecksum = $this->client->calculateChecksum($call, $params);

        $this->assertEquals($expectedChecksum, $actualChecksum);
    }

    public function test_build_api_url_constructs_valid_url_with_checksum(): void
    {
        $url = $this->client->buildApiUrl('create', ['meetingID' => 'test-room']);

        $this->assertStringStartsWith($this->testBaseUrl.'create?', $url);
        $this->assertStringContainsString('meetingID=test-room', $url);
        $this->assertStringContainsString('checksum=', $url);
        // Shared secret must never be directly exposed in the URL
        $this->assertStringNotContainsString($this->testSecret, $url);
    }

    public function test_create_meeting_parses_successful_xml_response(): void
    {
        $mockXml = <<<'XML'
<?xml version="1.0"?>
<response>
  <returncode>SUCCESS</returncode>
  <meetingID>trial-room-101</meetingID>
  <internalMeetingID>internal-xyz-987</internalMeetingID>
  <parentMeetingID>bbb-none</parentMeetingID>
  <attendeePW>ap123</attendeePW>
  <moderatorPW>mp456</moderatorPW>
  <createTime>1788424784000</createTime>
  <createDate>Thu Sep 03 2026</createDate>
  <hasUserJoined>false</hasUserJoined>
  <duration>0</duration>
</response>
XML;

        Http::fake([
            $this->testBaseUrl.'create*' => Http::response($mockXml, 200, ['Content-Type' => 'text/xml']),
        ]);

        $result = $this->client->createMeeting('trial-room-101', 'Test Meeting');

        $this->assertEquals('SUCCESS', $result['returncode']);
        $this->assertEquals('trial-room-101', $result['meetingID']);
        $this->assertEquals('internal-xyz-987', $result['internalMeetingID']);
        $this->assertEquals('mp456', $result['moderatorPW']);
        $this->assertEquals('ap123', $result['attendeePW']);
        $this->assertEquals('1788424784000', $result['createTime']);
    }

    public function test_create_meeting_serializes_plugin_manifests(): void
    {
        Http::fake([
            $this->testBaseUrl.'create*' => function ($request) {
                $this->assertStringContainsString('pluginManifests=', $request->url());
                $this->assertStringContainsString(urlencode('https://example.com/manifest.json'), $request->url());

                return Http::response('<response><returncode>SUCCESS</returncode><meetingID>m1</meetingID></response>', 200);
            },
        ]);

        $this->client->createMeeting('m1', 'Plugin Meeting', [
            'pluginManifests' => [
                ['url' => 'https://example.com/manifest.json'],
            ],
        ]);
    }

    public function test_create_meeting_throws_exception_on_api_failure(): void
    {
        $failedXml = <<<'XML'
<?xml version="1.0"?>
<response>
  <returncode>FAILED</returncode>
  <messageKey>idNotUnique</messageKey>
  <message>A meeting already exists with that meeting ID</message>
</response>
XML;

        Http::fake([
            $this->testBaseUrl.'create*' => Http::response($failedXml, 200),
        ]);

        $this->expectException(BigBlueButtonException::class);
        $this->expectExceptionMessage('BigBlueButton API error (idNotUnique): A meeting already exists with that meeting ID');

        $this->client->createMeeting('duplicate-room', 'Duplicate');
    }

    public function test_create_meeting_throws_exception_on_http_error(): void
    {
        Http::fake([
            $this->testBaseUrl.'create*' => Http::response('Server Error', 500),
        ]);

        $this->expectException(BigBlueButtonException::class);

        $this->client->createMeeting('err-room', 'Error');
    }

    public function test_get_join_url_generates_signed_moderator_url(): void
    {
        $joinUrl = $this->client->getJoinUrl(
            meetingId: 'room-99',
            fullName: 'James (Tutor)',
            role: 'MODERATOR',
            password: 'mod_secret_pw',
            createTime: '1788424784000'
        );

        $this->assertStringStartsWith($this->testBaseUrl.'join?', $joinUrl);
        $this->assertStringContainsString('meetingID=room-99', $joinUrl);
        $this->assertStringContainsString('fullName='.rawurlencode('James (Tutor)'), $joinUrl);
        $this->assertStringContainsString('role=MODERATOR', $joinUrl);
        $this->assertStringContainsString('password=mod_secret_pw', $joinUrl);
        $this->assertStringContainsString('createTime=1788424784000', $joinUrl);
        $this->assertStringContainsString('redirect=true', $joinUrl);
        $this->assertStringContainsString('checksum=', $joinUrl);
        $this->assertStringNotContainsString($this->testSecret, $joinUrl);
    }
}
