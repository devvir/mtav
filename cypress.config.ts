// Copilot - pending review

import { defineConfig } from 'cypress';
import { addMatchImageSnapshotPlugin } from '@simonsmith/cypress-image-snapshot/plugin';

export default defineConfig({
  e2e: {
    baseUrl: process.env.CYPRESS_baseUrl || 'http://localhost:8000',
    specPattern: 'tests/e2e/Views/**/*.cy.ts',
    supportFile: 'tests/e2e/support/e2e.ts',
    videosFolder: 'tests/e2e/videos',
    screenshotsFolder: 'tests/e2e/screenshots',
    downloadsFolder: 'tests/e2e/downloads',
    fixturesFolder: 'tests/e2e/fixtures',
    viewportWidth: 1280,
    viewportHeight: 720,
    video: false,
    screenshotOnRunFailure: true,
    // Optimized timeouts for local Docker environment with tmpfs DB
    defaultCommandTimeout: 5000,
    pageLoadTimeout: 5000,
    requestTimeout: 5000,
    // Disable animations for consistent snapshots
    experimentalMemoryManagement: true,
    numTestsKeptInMemory: 0, // Free memory after each test
    setupNodeEvents(on, config) {
      // Enable prefers-reduced-motion to disable all CSS transitions/animations
      on('before:browser:launch', (browser, launchOptions) => {
        if (browser.family === 'chromium') {
          launchOptions.args.push(
            '--force-prefers-reduced-motion',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--disable-software-rasterizer',
            '--no-sandbox',
          );
        }
        if (browser.family === 'firefox') {
          launchOptions.preferences['ui.prefersReducedMotion'] = 1;
        }
        return launchOptions;
      });

      // cypress-image-snapshot plugin
      addMatchImageSnapshotPlugin(on, config);

      return config;
    },
  },
});
