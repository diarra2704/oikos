import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.oikos.sygauges',
  appName: 'Oikos',
  webDir: 'www',
  server: {
    // Charge directement le site en production (même BD, même backend)
    url: 'https://oikos.sygauges.com',
    cleartext: false,
  },
  android: {
    allowMixedContent: false,
  },
};

export default config;
