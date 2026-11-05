import { Appearance } from '@/design';
import '@/design/styles.css';
import '@fontsource-variable/bricolage-grotesque';
import '@fontsource-variable/figtree';
import '@mantine/core/styles.css';
import { createRoot } from 'react-dom/client';
import { App } from './App';
import './warden.css';

createRoot(document.getElementById('warden')!).render(
  <Appearance>
    <App />
  </Appearance>,
);
