import { AthomCloudAPI, HomeyAPI } from 'homey-api';

const token = process.env.TOKEN || '2b7933e8-4916-451b-a4c8-cacad1cadc77:744ce84a-8456-426a-b696-b1ebc2523390:6e8ef9b0e95bca92aa68dee8f9c0b106096675cd';

async function test() {
  try {
    const homeyAPI = await HomeyAPI.createLocalAPI({
      address: 'http://localhost:80', // Replace with actual Homey IP if known, but since we don't know it, we might have to use AthomCloudAPI
      token: token
    });
    console.log('Connected to Local Homey API');
  } catch (e) {
    console.error('Local failed, trying cloud', e.message);
  }
}

test();
