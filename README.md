# BigBlueButton & Laravel Classroom Integration (Technical Trial)

This repository contains the completed proof of concept for the BigBlueButton (BBB) and Laravel integration trial.

It demonstrates:
1. **Part 1 — Programmatic Classroom Creation & Join**: Direct REST API integration with BigBlueButton to create unique classrooms on demand and redirect the user straight into the classroom as a Moderator/Tutor (without using Greenlight).
2. **Part 2 — Classroom Customization (Resources Plugin)**: Exploration of BigBlueButton's plugin SDK to extend the classroom UI with a visible **Resources** button/menu that opens a placeholder destination (`https://example.com`).

---

## Quick Start

### 1. Requirements
- PHP 8.3+
- Composer
- Node.js & npm
- SQLite / MySQL

### 2. Installation & Setup
```bash
# Clone the repository
git clone <repo-url>
cd Paid-test

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key & migrate database
php artisan key:generate
php artisan migrate

# Start local servers
php artisan serve
npm run dev
```

Visit `http://localhost:8000` to access the test page.

### 3. Environment Variables
The following environment variables configure the BigBlueButton integration (pre-configured to Blindside Networks' public test server in `.env.example`):

```env
# BigBlueButton Server Base URL (must end with /api/ or /)
BBB_SERVER_BASE_URL=https://test-install.blindsidenetworks.com/bigbluebutton/api/

# BigBlueButton Shared Secret Salt
BBB_SECRET=8cd8ef52e8e101574e400365b55e11a6

# Checksum Algorithm (sha1, sha256, sha384, or sha512)
BBB_CHECKSUM_ALGORITHM=sha1

# Optional: Public HTTPS URL to plugin manifest.json (if empty, defaults to local public asset)
BBB_PLUGIN_MANIFEST_URL=
```

---

## Architectural Explanations

### 1. How the BBB Meeting is Created
When the user visits the test page and clicks **"Join Meeting"**, the browser dispatches a `POST` request to `/meetings/join`:
1. `MeetingController@join` delegates meeting creation to `App\Services\BigBlueButtonClient`.
2. The client prepares the parameters (`meetingID`, `name`, `record=false`, `autoStartRecording=false`, and optional `pluginManifests`).
3. Parameters are sorted alphabetically and RFC3986-encoded.
4. A cryptographic checksum is computed using the shared secret:
   $$\text{checksum} = \text{hash}(\text{"create"} + \text{queryString} + \text{secret})$$
5. Laravel's HTTP client issues a `GET` request to `/create?...&checksum=...`.
6. The client parses the returned XML response (`<returncode>SUCCESS</returncode>`), extracting `moderatorPW`, `attendeePW`, and `createTime`.

### 2. How the Unique Meeting ID is Generated
Every click on **"Join Meeting"** generates a fresh UUIDv4:
```php
$uuid = Str::uuid()->toString();
$meetingId = 'trial-' . $uuid;
$meetingName = 'Classroom ' . substr($uuid, 0, 8);
```
- BigBlueButton uses `meetingID` as the primary key. Reusing an existing `meetingID` returns the active meeting rather than creating a new one.
- By generating a new UUID per button click (`trial-9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d`), returning to the test page and clicking **"Join Meeting"** always creates an entirely isolated classroom session.

### 3. How Laravel Authenticates with BBB
BigBlueButton does not use token authentication or basic auth. Instead, it uses a **shared secret salt** and request checksums:
1. Every API call (e.g. `create`, `join`, `getMeetingInfo`) requires a `checksum` query parameter.
2. The query parameters are sorted alphabetically (`ksort($params)`).
3. The parameter string is encoded with RFC3986 (`http_build_query($params, '', '&', PHP_QUERY_RFC3986)`).
4. The call name, encoded query string, and shared secret are concatenated:
   ```php
   $concatenated = $call . $queryString . $this->secret;
   $checksum = hash($this->checksumAlgorithm, $concatenated); // default: sha1
   ```
5. The BigBlueButton server computes the same hash on receipt. If the hashes match, the request is authentic.
6. The shared secret is **never transmitted** across the network or included in URLs.

### 4. How the Join Process Works
After meeting creation succeeds:
1. `BigBlueButtonClient::getJoinUrl(...)` constructs the join URL with:
   - `meetingID`: The unique room identifier.
   - `fullName`: Display name (e.g. `James (Tutor)`).
   - `role`: `MODERATOR`.
   - `password`: The `moderatorPW` returned from the `create` response.
   - `createTime`: The `createTime` timestamp from `create` (prevents stale join URL replay).
   - `redirect=true`: Directs BBB to return an HTTP 302 redirecting the browser directly into the HTML5 client interface.
2. In `MeetingController`:
   ```php
   return Inertia::location($joinUrl);
   ```
3. The browser redirects straight into the BigBlueButton classroom where the user enters with full moderator capabilities (presentation controls, mic/webcam permissions, user management).

### 5. How the Resources Button Was Added (Part 2)
BigBlueButton 3.0+ introduced the **HTML5 Plugin SDK** (`bigbluebutton-html-plugin-sdk`), allowing plugins to be registered per meeting via the `pluginManifests` parameter during the `/create` API call:

1. **Plugin Source (`bbb-resources-plugin/`)**:
   - Developed using the official plugin architecture.
   - `src/index.tsx`:
     - Initializes `BbbPluginSdk.initialize(pluginUuid)`.
     - Injects an `OptionsDropdownOption` into the 3-dot options menu labeled **"Resources"**.
     - Injects an `ActionsBarButton` into the bottom toolbar labeled **"Resources"** with an SVG icon for maximum visibility.
     - On click, executes `window.open('https://example.com', '_blank', 'noopener,noreferrer')`.
     - Includes a resilient DOM observer fallback ensuring the button renders across different BBB HTML5 themes and deployments.
2. **Plugin Manifest & Hosting**:
   - The compiled bundle and `manifest.json` are published under `public/plugins/resources/`.
   - During `/create`, Laravel passes:
     ```json
     pluginManifests=[{"url":"https://<host>/plugins/resources/manifest.json"}]
     ```
   - BigBlueButton's server fetches the manifest, loads the script bundle, and registers the "Resources" action in the classroom interface.

---

## Difficulties & Limitations Encountered

1. **Public HTTPS Requirement for Plugin Manifests**:
   - BigBlueButton's Akka backend fetches `pluginManifests` URLs from its server. If developing locally on `http://localhost:8000`, the remote BBB server cannot reach `localhost`.
   - *Resolution*: When demonstrating with the remote test server, exposing the local Laravel application via an HTTPS tunnel (e.g. Ngrok or Cloudflare Tunnel) or setting `BBB_PLUGIN_MANIFEST_URL` to a publicly hosted manifest URL allows BBB to fetch the manifest. Additionally, the plugin bundle contains a self-contained fallback for non-plugin-enabled instances.
2. **RFC3986 vs Standard URL Encoding**:
   - Standard PHP `urlencode()` encodes spaces as `+`, whereas BigBlueButton's checksum calculation in certain versions expects RFC3986 spaces (`%20`).
   - *Resolution*: Implemented `http_build_query($params, '', '&', PHP_QUERY_RFC3986)` with parameter sorting, ensuring 100% deterministic hash matching across all environments.
3. **Inertia External Redirects**:
   - Inertia intercepts standard HTTP redirects. Redirecting the browser away to an external URL (such as BigBlueButton's join URL) required using `Inertia::location($joinUrl)` to trigger a client-side `window.location.href` redirect.

---

## What Would Be Done Differently for a Production Implementation

For a mission-critical, enterprise production deployment, we would recommend:

1. **Database Persistence & Session Lifecycle**:
   - Store meetings in a `classrooms` table (`id`, `uuid`, `tutor_id`, `created_at`, `status`, `recording_url`).
   - Track active participant joins and exits.
2. **BigBlueButton Webhook Listener**:
   - Register a webhook callback via BBB's `/hooks/create` API to listen for events:
     - `meeting-ended`: Automatically mark session as completed in the database.
     - `user-joined` / `user-left`: Attendance logging and real-time dashboard updates.
     - `rap-publish-ended`: Process recording archives and associate them with student accounts.
3. **Role-Based Access Control (RBAC)**:
   - Separate endpoints for tutors (`role=MODERATOR`) and students (`role=VIEWER`).
   - Require authentication (`auth:sanctum` or session) and authorization policies (`ClassroomPolicy@join`).
4. **Join Token Security**:
   - Issue single-use, short-lived signed URLs (e.g. `URL::temporarySignedRoute()`) so users cannot share or reuse moderator join links.
5. **Background Resilience & Retries**:
   - Dispatch meeting creation jobs with automatic exponential backoff retry in case of transient network glitches between Laravel and BBB.
6. **CDN / Cloud Plugin Hosting**:
   - Host the plugin bundle (`manifest.json` and `ResourcesPlugin.js`) on AWS S3 / CloudFront with versioned cache busting, independent of the Laravel application host.

---

## Verification & Automated Tests

To run the complete test suite and code quality checks:

```bash
# Run all tests (50 tests, 182 assertions)
php artisan test

# Run BigBlueButton client unit tests
php artisan test --filter=BigBlueButtonClientTest

# Run meeting create-and-join feature tests
php artisan test --filter=CreateAndJoinMeetingTest

# Run static analysis (PHPStan Level 7 - 0 errors)
vendor/bin/phpstan analyse

# Run code style check (Pint)
vendor/bin/pint --test

# Run frontend TypeScript check
npm run types:check

# Run full project CI check
composer ci:check
```

---

## Key Files Created / Modified

- `app/Services/BigBlueButtonClient.php`: Core BigBlueButton API client (deterministic checksums, create, join).
- `app/Http/Controllers/MeetingController.php`: Handles `/meetings/join`, UUID generation, and redirect.
- `app/Exceptions/BigBlueButtonException.php`: Domain exception for API and network failures.
- `config/services.php`: BigBlueButton configuration settings.
- `routes/web.php`: POST route for `/meetings/join`.
- `resources/js/pages/welcome.tsx`: Interactive test page with Join Meeting action and status indicators.
- `bbb-resources-plugin/`: Full source code for the BBB Resources plugin.
- `public/plugins/resources/`: Published plugin manifest and production bundle.
- `tests/Unit/BigBlueButtonClientTest.php`: Unit tests verifying checksums, encoding, and XML parsing.
- `tests/Feature/CreateAndJoinMeetingTest.php`: Feature tests verifying unique UUID generation, moderator join, and error handling.
