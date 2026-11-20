# Sonoff NS Panel Pro — Heimdall UI (Laravel 13 + Filament v5)

Een donkere, kiosk-geoptimaliseerde webapplicatie (480×480) voor de **Sonoff NS Panel Pro** (of iedere browser) om het **Heimdall** alarmsysteem en bijbehorende flows op een **Homey Pro** te bedienen — volledig gebouwd op **Laravel 13**, **Filament v5**, **Livewire v4**, **Alpine.js** en **Tailwind CSS v4**.

## ✨ Features
- **Kiosk Frontend (TALL Stack)**: Geoptimaliseerd op `480×480` resolutie met 0ms touch-respons (Alpine.js), 30s auto-sleep screensaver, Web Audio API countdown-beeps en ambient weer-, vorst- (*Krabben*), Buienradar- (*Paraplu*) en afvalwijzer-kaarten (*Kliko's*).
- **Filament v5 Admin Paneel (`/admin`)**: Volledig beheer van gehashte gebruikers-PIN's (`bcrypt`), versleutelde Homey API-sleutels (`AES-256-CBC`), Homey Flow-koppelingen, live sensorstatus met batterijpercentages en activiteitenlogboek.
- **Server-Side Beveiliging**: Gehashte PIN-verificatie, sessie-geautoriseerde uitloopvertraging en server-side rate-limiting (`PinLockoutService`) tegen brute-force pogingen.
- **Alarm Push Trigger**: Ondersteunt directe push-activering vanuit een Homey Flow via Fully Kiosk Browser (`/entry?alarm=true`) met urgente rode countdown en knipperende alarmrand.

---

## 💻 Systeemvereisten
- **PHP 8.3** of hoger (inclusief `pdo_mysql`, `redis` en `intl`)
- **MySQL 8.0+** (lokaal en op de server)
- **Redis** (op de server voor caching & rate-limiting; lokaal draait cache via `array`)
- **Composer 2.x**
- **Node.js 22+** & **npm** (voor Vite + Tailwind CSS v4 compilatie)

---

## 🛠️ Installatie & Configuratie

1. **Installeer PHP- en frontend-dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Configureer je `.env` bestand:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Stel je MySQL-connectie in (`DB_DATABASE=sonoff_ns_panel_heimdall_homey`) en gebruik lokaal `CACHE_STORE=array` en op productie `CACHE_STORE=redis`. Alle applicatie-instellingen (Homey URL, versleutelde API-key, Flows, timers en gehashte PIN-codes met optioneel `is_admin`-vinkje) worden **volledig in MySQL** opgeslagen en met je beheerder-PIN ontgrendeld op `/admin`.

3. **Voer de MySQL-migraties en seeder uit:**
   ```bash
   php artisan migrate --seed
   ```

4. **Compileer de frontend-assets:**
   ```bash
   npm run build
   ```

5. **Start de applicatie:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   - **Kiosk Scherm (NS Panel Pro):** `http://<SERVER-IP>:8000/`
   - **Filament Beheerpaneel:** `http://<SERVER-IP>:8000/admin`
   - **Fully Kiosk Alarm Trigger URL:** `http://<SERVER-IP>:8000/entry?alarm=true`

---

## 🧪 Testen & Codekwaliteit

De applicatie beschikt over een volledige **Pest v4** testsuite (Unit, Feature, Livewire Kiosk & Filament v5 Admin tests):

```bash
php artisan test
./vendor/bin/pint --test
```
