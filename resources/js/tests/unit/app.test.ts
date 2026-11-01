import { decimal, fileSize, megabytes } from '@/lib/app';
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

describe('a size in bytes', () => {
  afterEach(() => setLocale('en'));

  it('is in kilobytes under a megabyte, never nothing', () => {
    expect(fileSize(0)).toBe('1 KB');
    expect(fileSize(820 * 1024)).toBe('820 KB');
    expect(fileSize(1024 * 1024 - 1)).toBe('1024 KB');
  });

  it('is in megabytes from exactly one on, wherever it shows', () => {
    expect(fileSize(1024 * 1024)).toBe('1 MB');
    expect(fileSize(3.44 * 1024 * 1024)).toBe('3.4 MB');
  });

  it('takes the decimal mark of the app’s language', () => {
    setLocale('es_UY');

    expect(fileSize(1.5 * 1024 * 1024)).toBe('1,5 MB');
  });
});
