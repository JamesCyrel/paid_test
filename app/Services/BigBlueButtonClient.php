<?php

namespace App\Services;

use App\Exceptions\BigBlueButtonException;
use Illuminate\Support\Facades\Http;
use Throwable;

class BigBlueButtonClient
{
    protected string $baseUrl;

    protected string $secret;

    protected string $checksumAlgorithm;

    public function __construct(
        ?string $baseUrl = null,
        ?string $secret = null,
        ?string $checksumAlgorithm = null
    ) {
        $rawUrl = $baseUrl ?? (string) config('services.bigbluebutton.base_url', '');
        $this->baseUrl = rtrim($rawUrl, '/').'/';
        $this->secret = $secret ?? (string) config('services.bigbluebutton.secret', '');
        $this->checksumAlgorithm = strtolower($checksumAlgorithm ?? (string) config('services.bigbluebutton.checksum_algorithm', 'sha1'));
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getChecksumAlgorithm(): string
    {
        return $this->checksumAlgorithm;
    }

    /**
     * Calculate BigBlueButton API checksum for a call and parameters.
     * Query parameters are sorted alphabetically and RFC3986 encoded.
     *
     * @param  array<string, mixed>  $params
     */
    public function calculateChecksum(string $call, array $params): string
    {
        ksort($params);
        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $concatenated = $call.$queryString.$this->secret;

        return hash($this->checksumAlgorithm, $concatenated);
    }

    /**
     * Build the signed BigBlueButton API URL for a call and parameters.
     *
     * @param  array<string, mixed>  $params
     */
    public function buildApiUrl(string $call, array $params = []): string
    {
        ksort($params);
        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $checksum = $this->calculateChecksum($call, $params);

        $separator = empty($queryString) ? '' : $queryString.'&';

        return $this->baseUrl.$call.'?'.$separator.'checksum='.$checksum;
    }

    /**
     * Create a new BigBlueButton classroom.
     *
     * @param  string  $meetingId  Unique identifier for the meeting
     * @param  string  $meetingName  Human-readable meeting name
     * @param  array<string, mixed>  $options  Additional BBB create options (pluginManifests, passwords, etc.)
     * @return array<string, mixed>
     *
     * @throws BigBlueButtonException
     */
    public function createMeeting(string $meetingId, string $meetingName, array $options = []): array
    {
        $params = array_merge([
            'meetingID' => $meetingId,
            'name' => $meetingName,
            'record' => 'false',
            'autoStartRecording' => 'false',
            'allowStartStopRecording' => 'false',
        ], $options);

        // Serialize pluginManifests if passed as an array or object
        if (isset($params['pluginManifests']) && ! is_string($params['pluginManifests'])) {
            $params['pluginManifests'] = json_encode($params['pluginManifests'], JSON_UNESCAPED_SLASHES);
        }

        $url = $this->buildApiUrl('create', $params);

        try {
            $response = Http::timeout(15)
                ->accept('text/xml, application/xml')
                ->get($url);
        } catch (Throwable $e) {
            throw BigBlueButtonException::connectionError($e->getMessage(), (int) $e->getCode(), $e);
        }

        if (! $response->successful()) {
            throw BigBlueButtonException::connectionError(
                "HTTP request returned status code {$response->status()}",
                $response->status()
            );
        }

        $body = $response->body();
        $xml = @simplexml_load_string($body);

        if ($xml === false) {
            throw BigBlueButtonException::invalidResponse('Could not parse XML response', $body);
        }

        $returnCode = (string) ($xml->returncode ?? '');

        if ($returnCode !== 'SUCCESS') {
            $messageKey = (string) ($xml->messageKey ?? 'unknownError');
            $message = (string) ($xml->message ?? '');

            throw BigBlueButtonException::failedResponse($messageKey, $message, $body);
        }

        return [
            'returncode' => $returnCode,
            'meetingID' => (string) ($xml->meetingID ?? $meetingId),
            'internalMeetingID' => (string) ($xml->internalMeetingID ?? ''),
            'parentMeetingID' => (string) ($xml->parentMeetingID ?? ''),
            'attendeePW' => (string) ($xml->attendeePW ?? ''),
            'moderatorPW' => (string) ($xml->moderatorPW ?? ''),
            'createTime' => (string) ($xml->createTime ?? ''),
            'createDate' => (string) ($xml->createDate ?? ''),
            'hasUserJoined' => ((string) ($xml->hasUserJoined ?? 'false')) === 'true',
            'duration' => (int) ($xml->duration ?? 0),
            'rawXml' => $body,
        ];
    }

    /**
     * Generate signed URL for joining a BigBlueButton meeting.
     *
     * @param  string  $fullName  User's display name
     * @param  string  $role  MODERATOR or VIEWER
     * @param  string|null  $password  Moderator or attendee password
     * @param  string|null  $createTime  Meeting create timestamp to prevent stale reuse
     * @param  array<string, mixed>  $extraParams  Additional join parameters
     */
    public function getJoinUrl(
        string $meetingId,
        string $fullName,
        string $role = 'MODERATOR',
        ?string $password = null,
        ?string $createTime = null,
        array $extraParams = []
    ): string {
        $params = array_merge([
            'meetingID' => $meetingId,
            'fullName' => $fullName,
            'role' => $role,
            'redirect' => 'true',
        ], $extraParams);

        if ($password !== null && $password !== '') {
            $params['password'] = $password;
        }

        if ($createTime !== null && $createTime !== '') {
            $params['createTime'] = $createTime;
        }

        return $this->buildApiUrl('join', $params);
    }

    /**
     * Fetch meeting information from BigBlueButton.
     *
     * @param  string|null  $password  Moderator password
     * @return array<string, mixed>
     *
     * @throws BigBlueButtonException
     */
    public function getMeetingInfo(string $meetingId, ?string $password = null): array
    {
        $params = ['meetingID' => $meetingId];
        if ($password !== null) {
            $params['password'] = $password;
        }

        $url = $this->buildApiUrl('getMeetingInfo', $params);

        try {
            $response = Http::timeout(15)->get($url);
        } catch (Throwable $e) {
            throw BigBlueButtonException::connectionError($e->getMessage(), (int) $e->getCode(), $e);
        }

        $body = $response->body();
        $xml = @simplexml_load_string($body);

        if ($xml === false) {
            throw BigBlueButtonException::invalidResponse('Could not parse XML response', $body);
        }

        $returnCode = (string) ($xml->returncode ?? '');
        if ($returnCode !== 'SUCCESS') {
            throw BigBlueButtonException::failedResponse(
                (string) ($xml->messageKey ?? 'unknownError'),
                (string) ($xml->message ?? ''),
                $body
            );
        }

        return [
            'returncode' => $returnCode,
            'meetingID' => (string) ($xml->meetingID ?? ''),
            'participantCount' => (int) ($xml->participantCount ?? 0),
            'moderatorCount' => (int) ($xml->moderatorCount ?? 0),
            'running' => ((string) ($xml->running ?? 'false')) === 'true',
            'createTime' => (string) ($xml->createTime ?? ''),
        ];
    }
}
