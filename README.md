# Sonoff NS Panel Pro - Heimdall UI

A sleek, dark-themed, kiosk-optimized web application designed to run on the Sonoff NS Panel Pro (or any browser) to control the **Heimdall** alarm system on a Homey Pro. 

![Heimdall UI](https://github.com/danee-de-kruyff/com.uc.heimdall/raw/master/assets/images/large.png) *(Note: Placeholder image for Heimdall)*

## ✨ Features
- **Kiosk Optimized**: Hardcoded for 480x480 resolution (perfect for NS Panel Pro).
- **Direct Integration**: Talks directly to the Homey Device API locally (no cloud delays).
- **Audio Feedback**: Uses the Web Audio API for native countdown beeps and activation sounds without loading external mp3s.
- **Alarm Push Trigger**: Capable of displaying an urgent, flashing red countdown timer when triggered by Homey.
- **Auto-Sleep**: Automatically returns to a black screensaver after 30 seconds of inactivity.
- **Secure Backend**: Uses a robust PHP proxy backend to securely store API keys and validate PIN codes server-side, protecting your Homey environment from client-side manipulation.

---

## 🛠️ Homey Pro Configuration (Required)

Before starting the application, you must configure Homey and Heimdall to allow external API communication.

### 1. Add the Surveillance Device
This UI does not talk to the Heimdall app directly. Instead, it interacts with the **Surveillance Mode Switch** virtual device.
1. Open the Homey app.
2. Go to **Devices** -> **+** -> **Heimdall**.
3. Add the **Surveillance Mode Switch** device.

### 2. Generate an API Key
You need a Local API key with proper scopes to allow the panel to read and write to the Surveillance device.
1. Go to the [Homey Developer Tools](https://tools.developer.homey.app/tools/api-keys).
2. Create a new API Key. **Important:** Ensure the key has full `homey` access or at least `homey.device` and `homey.app` scopes.
3. Copy the Bearer Token.

### 3. Setting up the Alarm Trigger Push (Fully Kiosk Browser)
If you want the NS Panel to automatically pop up with a flashing red countdown when the alarm is triggered, you must create a Flow in Homey.
1. Ensure your NS Panel is running **Fully Kiosk Browser** and the REST API is enabled in its settings.
2. **Create a Flow in Homey:**
   - **WHEN**: Heimdall status changes to `Alarm` (or Pre-alarm delay starts).
   - **THEN**: Make a Webhook/HTTP GET request to Fully Kiosk Browser to load the specific URL:
     `http://<IP-OF-NSPANEL>:2365/?cmd=loadURL&url=http://<IP-OF-SERVER>:5173/entry?alarm=true&password=<FULLY_KIOSK_PASSWORD>`

---

## 💻 Requirements

To host the Heimdall UI securely, your web server must support:
- **PHP 8.0** or higher
- The PHP server must allow `file_get_contents()` for external networking (cURL is not required).

---

## 💻 Installation & Setup

1. Clone or download this repository.
2. Install dependencies:
   ```bash
   npm install
   ```
3. Configure your Environment Variables:
   Open the `.env` file (create one based on `.env.example`) and add your Homey IP, API Key, and PIN codes:
   ```env
   HOMEY_BASE_URL=http://<YOUR_HOMEY_IP>
   HOMEY_API_KEY=<YOUR_HOMEY_API_KEY>
   ALLOWED_PINS=1234,0000
   
   # Optional: Default seconds for the normal arming countdown
   VITE_COUNTDOWN_SECONDS=10
   ```
4. Build for deployment:
   ```bash
   npm run build:deploy
   ```
   This command creates a `deploy/` directory containing your `.env` file and a `public/` directory with the frontend code and `api.php`. Copy the contents of `deploy/public/` directly to your PHP web server's document root.

## 📱 Running on the NS Panel
1. Open **Fully Kiosk Browser** on your Sonoff NS Panel Pro.
2. Set the Start URL to your web server's address (e.g. `http://<IP-OF-YOUR-SERVER>:8010/`).
3. Enjoy your modern, secure Heimdall dashboard!

## 🧪 Testing
The application includes unit tests for the core logic and routing.
```bash
npm run test
```
