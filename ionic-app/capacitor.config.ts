import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'ph.edu.isu.teambangan.lostfound',
  appName: 'Lost and Found',
  webDir: 'www',
  // Plain http is needed while the API runs on your PC in the local network.
  server: { androidScheme: 'http', cleartext: true },
};

export default config;
