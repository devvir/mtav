import { decimal, megabytes } from '@/lib/app';
import { setLocale } from '@/lib/translations';

describe('numbers, as the app’s language writes them', () => {
  afterEach(() => setLocale('en'));

  it('write sizes in megabytes, and in gigabytes from one on', () => {
    expect(megabytes(500)).toBe('500 MB');
    expect(megabytes(1024)).toBe('1 GB');
    expect(megabytes(1536)).toBe('1.5 GB');
  });

  it('write decimals with the language’s own mark', () => {
    setLocale('es_UY');
    expect(megabytes(1536)).toBe('1,5 GB');
    expect(decimal(2.333, 2)).toBe('2,33');

    setLocale('fr');
    expect(megabytes(1536)).toBe('1,5 GB');

    setLocale('pt_BR');
    expect(decimal(2.5, 2)).toBe('2,5');
  });

  it('never round a whole number to decimals', () => {
    expect(decimal(3, 2)).toBe('3');
  });
});
