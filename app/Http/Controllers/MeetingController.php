<?php

namespace App\Http\Controllers;

use App\Exceptions\BigBlueButtonException;
use App\Services\BigBlueButtonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class MeetingController extends Controller
{
    /**
     * Programmatically create a new unique BigBlueButton classroom
     * and redirect the user directly as moderator/tutor.
     */
    public function join(Request $request, BigBlueButtonClient $bbbClient): Response
    {
        // Generate a new unique meeting ID on every request
        $uuid = Str::uuid()->toString();
        $meetingId = 'trial-'.$uuid;
        $meetingName = 'Classroom '.substr($uuid, 0, 8);
        $tutorName = $request->input('fullName', 'James (Tutor)');

        // Prepare optional plugin manifest configuration
        $options = [];
        $manifestUrl = config('services.bigbluebutton.plugin_manifest_url');

        // Fallback: If not explicitly configured in .env, check if local manifest exists and generate URL
        if (empty($manifestUrl) && file_exists(public_path('plugins/resources/manifest.json'))) {
            $manifestUrl = url('plugins/resources/manifest.json');
        }

        if (! empty($manifestUrl)) {
            $options['pluginManifests'] = [
                ['url' => $manifestUrl],
            ];
        }

        try {
            // 1. Create the meeting on BigBlueButton
            $meeting = $bbbClient->createMeeting($meetingId, $meetingName, $options);

            // 2. Generate signed moderator join URL
            $joinUrl = $bbbClient->getJoinUrl(
                meetingId: $meeting['meetingID'],
                fullName: $tutorName,
                role: 'MODERATOR',
                password: $meeting['moderatorPW'] ?: null,
                createTime: $meeting['createTime'] ?: null
            );

            // 3. For Inertia requests, Inertia::location sends an external redirect header
            return Inertia::location($joinUrl);
        } catch (BigBlueButtonException $e) {
            Log::error('BigBlueButton API meeting creation failed', [
                'meetingId' => $meetingId,
                'error' => $e->getMessage(),
                'returnCode' => $e->getReturnCode(),
                'messageKey' => $e->getMessageKey(),
            ]);

            return back()->with([
                'error' => 'Failed to create BigBlueButton classroom: '.$e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Unexpected error while creating BigBlueButton classroom', [
                'meetingId' => $meetingId,
                'error' => $e->getMessage(),
            ]);

            return back()->with([
                'error' => 'An unexpected error occurred while connecting to BigBlueButton: '.$e->getMessage(),
            ]);
        }
    }
}
