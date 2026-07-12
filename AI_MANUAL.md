# AI Programming Manual & Context

If you are an AI programming assistant (like Copilot, Cursor, or Gemini) reading this file to help maintain or extend this codebase, **read this first**. This document contains critical context on how this application interacts with Homey and Heimdall, and why it is built this way.

## Where the Data Came From
During the initial development of this application, standard documentation for Heimdall suggested hitting REST endpoints like `/api/app/com.uc.heimdall/state/surveillance`. However, through live testing and web searches, we discovered that:

1. **Heimdall's Restricted App API**: Heimdall does *not* expose a public REST API directly for end-users to change its state. Hitting the App API directly results in a `403 Missing Scopes` or `Invalid Path` error.
   - *Source*: Live `curl` testing and Homey Community Forum best practices.
2. **The Correct Approach (Homey Device API)**: The standard and supported way to interact with Heimdall via an external interface is by manipulating the virtual **Surveillance Mode Switch** device that Heimdall creates in Homey. 
   - *Source*: We used the `node-homey-api` module to query `homeyAPI.devices.getDevices()` and discovered the device `Surveillance (sensor)` with `driverId: 'surveillancemodeswitch'`.
   - *Documentation*: [Node Homey API Reference](https://athombv.github.io/node-homey-api/)

## Critical Implementation Details

### 1. API Communication
Instead of using the heavy `homey-api` library in the browser, this app uses `axios` to make direct HTTP requests to the local Homey Pro's API. 

The application dynamically searches for the Heimdall device ID using:
```http
GET http://<HOMEY_IP>/api/manager/devices/device
```
It filters the response for a device where `driverId === 'surveillancemodeswitch'`.

To change the alarm state, it updates the `homealarm_state` capability of that specific device:
```http
PUT http://<HOMEY_IP>/api/manager/devices/device/<DEVICE_ID>/capability/homealarm_state
```
Payload: `{ "value": "armed" | "partially_armed" | "disarmed" }`

### 2. Kiosk Environment (Browser Constraints)
This app is designed to run in **Fully Kiosk Browser** on a Sonoff NS Panel Pro (480x480 resolution).
- **Push Events**: Browsers cannot natively listen to inbound HTTP requests. To handle "Alarm Triggered" events pushed from Homey, Heimdall uses a Homey Flow to send a REST command to the Fully Kiosk Browser API, instructing it to load: `http://<PANEL_IP>:5173/entry?alarm=true`.
- **Audio Constraints**: Browsers require user interaction before playing audio. Because this is a touch panel, the user must tap the screen to initiate a countdown, which satisfies the browser's audio context requirements. We use the native `AudioContext` (Web Audio API) to generate beeps programmatically to avoid loading external MP3 files.

### 3. Authentication
The API key must be passed as a Bearer token in the `Authorization` header. Note that typical Webhook keys do not work; the API key must have full `homey` or `homey.device` + `homey.app` scopes generated from the [Homey Developer portal](https://tools.developer.homey.app/tools/api-keys).
