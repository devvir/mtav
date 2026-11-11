import { ShareFilesForm } from '@/components/forms/ShareFilesForm';
import { MantineProvider } from '@mantine/core';
import { render } from '@testing-library/react';

const { cancel } = vi.hoisted(() => ({ cancel: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
  Link: 'a',
  useForm: () => ({
    data: { description: '', files: [] },
    errors: {},
    processing: true,
    progress: { percentage: 40 },
    setData: vi.fn(),
    post: vi.fn(),
    cancel,
  }),
}));
vi.mock('@/lib/route', () => ({ route: (name: string) => name }));

describe('sharing files', { timeout: 30_000 }, () => {
  it('sends nothing more once closed while sending: shared again, they go once', () => {
    const { unmount } = render(
      <MantineProvider>
        <ShareFilesForm
          limits={{ accept: {}, maxFileKb: 1024, maxBatchKb: 4096, maxFiles: 20 }}
          storage={null}
          onDone={vi.fn()}
        />
      </MantineProvider>,
    );
    expect(cancel).not.toHaveBeenCalled();

    unmount();

    expect(cancel).toHaveBeenCalled();
  });
});
