# Sonoff NS Panel Pro - Heimdall UI

A sleek, dark-themed, kiosk-optimized web application designed to run on the Sonoff NS Panel Pro (or any browser) to control the **Heimdall** alarm system on a Homey Pro. 

![Heimdall UI](https://github.com/danee-de-kruyff/com.uc.heimdall/raw/master/assets/images/large.png) *(Note: Placeholder image for Heimdall)*

## ✨ Features
- **Kiosk Optimized**: Hardcoded for 480x480 resolution (perfect for NS Panel Pro).
- **Direct Integration**: Talks directly to the Homey Device API locally (no cloud delays).
- **Audio Feedback**: Uses the Web Audio API for native countdown beeps and activation sounds without loading external mp3s.
- **Alarm Push Trigger**: Capable of displaying an urgent, flashing red countdown timer when triggered by Homey.
- **Auto-Sleep**: Automatically returns to a black screensaver after 30 seconds of inactivity.

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

## 💻 Installation & Setup

1. Clone or download this repository.
2. Install dependencies:
   ```bash
   npm install
   ```
3. Configure your Environment Variables:
   Open the `.env` file (create one if it doesn't exist) and add your Homey IP and the API Key you generated:
   ```env
   VITE_HOMEY_BASE_URL=http://<YOUR_HOMEY_IP>
   VITE_HOMEY_API_KEY=<YOUR_HOMEY_API_KEY>
   
   # Optional: Default seconds for the normal arming countdown
   VITE_COUNTDOWN_SECONDS=10
   ```
4. Start the development server (make sure to use `--host` so the NS Panel can access it):
   ```bash
   npm run dev -- --host
   ```

## 📱 Running on the NS Panel
1. Open **Fully Kiosk Browser** on your Sonoff NS Panel Pro.
2. Set the Start URL to: `http://<IP-OF-YOUR-SERVER>:5173`
3. Enjoy your new modern Heimdall dashboard!

## 🧪 Testing
The application includes unit tests for the core logic and routing.
```bash
npm run test
```
