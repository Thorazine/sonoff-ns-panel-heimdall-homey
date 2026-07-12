import { HomeyAPI } from 'homey-api';

const token = '2b7933e8-4916-451b-a4c8-cacad1cadc77:7763e9d0-074e-4f3e-ab95-049544798361:452cff23079e282d3d59cfc5d4d183f0fde30b80';

async function test() {
  try {
    const homeyAPI = await HomeyAPI.createLocalAPI({
      address: 'http://192.168.1.104',
      token: token
    });
    console.log('Connected to Local Homey API');

    try {
      const devices = await homeyAPI.devices.getDevices();
      const heimdallDevices = Object.values(devices).filter(d => d.driverId === 'surveillancemodeswitch' || d.name.toLowerCase().includes('heimdall') || d.name.toLowerCase().includes('surveillance'));
      console.log('Heimdall Devices found:', heimdallDevices.length);
      for (const d of heimdallDevices) {
        console.log(`- ID: ${d.id}, Name: ${d.name} (${d.class})`);
      }
    } catch (e) {
      console.error('Devices fetch failed:', e.message);
    }
  } catch (err) {
    console.error('Failed to init HomeyAPI:', err.message);
  }
}

test();
