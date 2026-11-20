# AI Programming Manual & Context

If you are an AI programming assistant (like Copilot, Cursor, or Gemini) reading this file to help maintain or extend this codebase, **read this first**. This document contains critical context on how this application interacts with Homey and Heimdall, and why it is built this way.

## Architecture Stack
- **Backend**: Laravel 13 (PHP 8.3+), MySQL (`pdo_mysql`), Cache (`array` locally, `redis` via `phpredis` on server), Eloquent (`PinCode`, `Setting`, `ActivityLog`, `User`).
- **Kiosk Frontend (`480x480`)**: TALL stack — Livewire v4 full-page components (`app/Livewire/Kiosk/*`), Alpine.js (client-side 1s clocks, auto-sleep timer, countdown timers, Web Audio API beeps), and Tailwind CSS v4 via Vite (`resources/css/app.css`, `resources/js/app.js`).
- **Admin Panel (`/admin`)**: Filament v5 (`app/Filament/*`) for managing bcrypt-hashed PIN codes, encrypted Homey credentials, Homey Flow bindings, sensor inspection, and activity logs.
- **Test Suite**: Pest v4 (`tests/Unit/*` and `tests/Feature/*`).

## Where the Data Came From
During the initial development of this application, standard documentation for Heimdall suggested hitting REST endpoints like `/api/app/com.uc.heimdall/state/surveillance`. However, through live testing and web searches, we discovered that:

1. **Heimdall's Restricted App API**: Heimdall does *not* expose a public REST API directly for end-users to change its state. Hitting the App API directly results in a `403 Missing Scopes` or `Invalid Path` error.
   - *Source*: Live `curl` testing and Homey Community Forum best practices.
2. **The Correct Approach (Homey Device API)**: The standard and supported way to interact with Heimdall via an external interface is by manipulating the virtual **Surveillance Mode Switch** device that Heimdall creates in Homey.
   - *Source*: We used the `node-homey-api` module to query `homeyAPI.devices.getDevices()` and discovered the device `Surveillance (sensor)` with `driverId: 'surveillancemodeswitch'`.
   - *Documentation*: [Node Homey API Reference](https://athombv.github.io/node-homey-api/)

## Critical Implementation Details

### 1. Server-Side API Communication (`app/Services/HomeyService.php`)
All Homey communication happens server-side in `HomeyService` using Laravel's `Http` client (the Homey Bearer token is stored encrypted via Laravel `Crypt` in the `settings` table and never exposed to the browser).

The service dynamically searches for the Heimdall device ID using:
```http
GET http://<HOMEY_IP>/api/manager/devices/device
```
It filters the response for a device where `driverId === 'surveillancemodeswitch'` (or `name` contains `surveillance`).

To change the alarm state, it updates the `homealarm_state` capability of that specific device:
```http
PUT http://<HOMEY_IP>/api/manager/devices/device/<DEVICE_ID>/capability/homealarm_state
```
Payload: `{ "value": "armed" | "partially_armed" | "disarmed" }`

### 2. Kiosk Environment (Browser Constraints)
This app is designed to run in **Fully Kiosk Browser** on a Sonoff NS Panel Pro (`480x480` resolution).
- **Push Events**: Browsers cannot natively listen to inbound HTTP requests. To handle "Alarm Triggered" events pushed from Homey, Heimdall uses a Homey Flow to send a REST command to the Fully Kiosk Browser API, instructing it to load: `http://<SERVER_HOST>/entry?alarm=true`. `EntryScreen` reads `#[Url(as: 'alarm')]` and enters alarm countdown mode.
- **Audio Constraints**: Browsers require user interaction before playing audio. Because this is a touch panel, the user taps the screen to initiate a countdown, which satisfies the browser's audio context requirements. `resources/js/app.js` uses the native `AudioContext` (Web Audio API) to generate beeps programmatically without external MP3 files.
- **Modals & Touch Targets**: Open-sensor warning dialogs use native HTML `<dialog closedby="any">` (`showModal()`) and interactive controls maintain generous touch targets (`>= 44x44` CSS px) for wall-mounted touch ergonomics.

### 3. Authentication & Security
- **Homey API Key**: Passed as a Bearer token in the `Authorization` header by `HomeyService`. Typical Webhook keys do not work; the API key must have full `homey` or `homey.device` + `homey.app` scopes generated from the [Homey Developer portal](https://tools.developer.homey.app/tools/api-keys).
- **PIN Codes & Rate Limiting**: Kiosk PIN codes are stored as bcrypt hashes (`PinCode` model) and verified server-side in Livewire actions, protected against brute-force attempts per IP via `PinLockoutService` (`RateLimiter`).
