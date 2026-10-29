import { SignInForm } from '@/components/auth/SignInForm';
import { SignUpForm } from '@/components/auth/SignUpForm';
import { useCurrentPage, useFlashToasts } from '@/components/layout/page';
import { DialogHost } from '@/components/overlays/DialogHost';
import { Glyph, Logo } from '@/design';
import { route } from '@/lib/route';
import { _, getLocale } from '@/lib/translations';
import { Head, router } from '@inertiajs/react';
import { Button, Divider, Group, Menu, Modal, SegmentedControl, Text, Title } from '@mantine/core';
import {
  IconBrandGoogleFilled,
  IconCalendarEvent,
  IconCheck,
  IconDice5,
  IconLanguage,
  IconMap,
  IconUsersGroup,
} from '@tabler/icons-react';
import { useState, type ReactNode } from 'react';

type Auth = 'login' | 'register';

interface Props {
  /** Signing in (or up) open over the page, as /login and /register ask */
  dialog: Auth | null;
  /** Google sign-in is set up */
  google: boolean;
  /** The languages to pick from, each named in itself */
  locales: Record<string, string>;
}

/** The photos shown, each credited as its licence asks (all resized from the originals) */
const PHOTOS = {
  mesa1: {
    src: '/images/welcome/mesa-1.webp',
    author: 'MaGaRicard',
    source:
      'https://commons.wikimedia.org/wiki/File:Complejo_cooperativo_de_vivienda_Mesa_1_Uruguay.jpg',
  },
  covife: {
    src: '/images/welcome/covife.webp',
    author: 'Intendencia de Montevideo',
    source:
      'https://commons.wikimedia.org/wiki/File:Entrega_de_bolsones_en_cooperativa_COVIFE_imf3409.jpg',
  },
  building: {
    src: '/images/welcome/building.webp',
    author: 'Verónica Caballero, Intendencia de Montevideo',
    source:
      'https://commons.wikimedia.org/wiki/File:Intendenta_Carolina_Cosse_participa_de_la_entrega_de_24_llaves_en_el_marco_del_programa_de_Vivienda_Sindical_-_20210529dicmouyvc0007.jpg',
  },
  mesa2: {
    src: '/images/welcome/mesa-2.webp',
    author: 'Adrian Manera',
    source: 'https://commons.wikimedia.org/wiki/File:Marcas_de_la_Memoria_-_FUCVAM_Mesa_2.jpg',
  },
  brazos: {
    src: '/images/welcome/brazos-unidos.webp',
    author: 'Artigas Pessio, Intendencia de Montevideo',
    source:
      'https://commons.wikimedia.org/wiki/File:Inauguracion_de_viviendas_Brazos_Unidos_-_afg3572.jpg',
  },
};

/** Where a dialog's address goes, so that a reload keeps it open (and closing, clean) */
const addressFor = (auth: Auth | null) =>
  window.history.replaceState(
    window.history.state,
    '',
    auth === 'login' ? '/login' : auth === 'register' ? '/register' : '/',
  );

/**
 * What the platform is, for those who arrive: what a cooperative does with it, in pictures
 * of cooperatives that built their homes. Signing in is the way on, over this page.
 */
export default function Welcome({ dialog, google, locales }: Props) {
  const { props } = useCurrentPage();
  useFlashToasts();
  const [open, setOpen] = useState<Auth | null>(dialog);
  const show = (next: Auth | null) => {
    setOpen(next);
    addressFor(next);
  };

  const captions: Record<keyof typeof PHOTOS, string> = {
    mesa1: _('Mesa 1, mutual-aid housing cooperatives in Montevideo'),
    covife: _('COVIFE, a housing cooperative in Montevideo'),
    building: _('Homes going up, Montevideo'),
    mesa2: _('The common green of Mesa 2'),
    brazos: _('Brazos Unidos, homes built in Montevideo'),
  };

  const features: { icon: typeof IconMap; title: string; text: string }[] = [
    {
      icon: IconUsersGroup,
      title: _('Families and their people'),
      text: _(
        'Each family joins by email, with everyone in it. Each person decides what others see of them.',
      ),
    },
    {
      icon: IconMap,
      title: _('Units and the plan'),
      text: _('The units by type, on a plan of the site you draw yourselves, floor by floor.'),
    },
    {
      icon: IconDice5,
      title: _('A fair lottery'),
      text: _(
        'Each family ranks the units of its type. The lottery gives every family a unit as high in its ranking as possible.',
      ),
    },
    {
      icon: IconCalendarEvent,
      title: _('Life together'),
      text: _(
        'Assemblies and work days on one calendar; photos and documents shared with the whole cooperative.',
      ),
    },
  ];

  const steps: [string, string][] = [
    [_('Start the project'), _('Its admins describe it: unit types, units, the plan.')],
    [_('Invite the families'), _('Each family joins by email, with everyone in it.')],
    [_('Each family ranks its units'), _('Most wanted first, together, until the lottery.')],
    [
      _('The lottery gives out the units'),
      _('As high in each ranking as possible; only ties are drawn at random.'),
    ],
  ];

  const ctas = (
    <Group gap="sm">
      <Button size="lg" onClick={() => show('login')}>
        {_('Sign in')}
      </Button>
      <Button size="lg" variant="default" onClick={() => show('register')}>
        {_('Create an account')}
      </Button>
    </Group>
  );

  return (
    <div className="welcome">
      <Head title={_('Housing cooperatives, together')} />

      <header className="welcome-bar">
        <Logo name={props.name} institution={props.institution} size={38} />
        <Group gap="xs" wrap="nowrap">
          <LanguagePicker locales={locales} />
          <Button variant="light" onClick={() => show('login')}>
            {_('Sign in')}
          </Button>
        </Group>
      </header>

      <main>
        <section className="welcome-hero">
          <div className="welcome-hero-words">
            <Title order={1} className="welcome-title">
              {_('From the first assembly to the keys')}
            </Title>
            <Text className="welcome-lead">
              {_(
                'The whole cooperative in one place: its families and their people, the units and the plan of the site, assemblies and work days, and a lottery that gives every family the best home it can.',
              )}
            </Text>
            {ctas}
            <Text className="ds-caption" mt="md">
              {_('Invited by your cooperative? Open the link in your email.')}
            </Text>
          </div>
          <Photo photo="mesa1" caption={captions.mesa1} className="welcome-hero-photo" eager />
        </section>

        <section className="welcome-section">
          <Title order={2} className="welcome-heading">
            {_('Everything the cooperative shares')}
          </Title>
          <div className="welcome-features">
            {features.map((feature) => (
              <article key={feature.title} className="welcome-feature ds-surface">
                <Glyph icon={feature.icon} size={40} />
                <Title order={3} fz="lg" mt="md">
                  {feature.title}
                </Title>
                <Text c="dimmed" mt={6}>
                  {feature.text}
                </Text>
              </article>
            ))}
          </div>
        </section>

        <section className="welcome-photos" aria-label={_('Housing cooperatives in Uruguay')}>
          {(['covife', 'building', 'mesa2'] as const).map((photo) => (
            <Photo key={photo} photo={photo} caption={captions[photo]} />
          ))}
        </section>

        <section className="welcome-section">
          <Title order={2} className="welcome-heading">
            {_('How it goes')}
          </Title>
          <ol className="welcome-steps">
            {steps.map(([title, text], index) => (
              <li key={title}>
                <span className="welcome-step-number" aria-hidden>
                  {index + 1}
                </span>
                <div>
                  <Text fw={700}>{title}</Text>
                  <Text c="dimmed" size="sm" mt={2}>
                    {text}
                  </Text>
                </div>
              </li>
            ))}
          </ol>
        </section>

        <section className="welcome-closing">
          <Photo photo="brazos" caption={captions.brazos} className="welcome-closing-photo" />
          <div>
            <Title order={2} className="welcome-heading">
              {_('Ready when your cooperative is')}
            </Title>
            {ctas}
          </div>
        </section>
      </main>

      <footer className="welcome-footer">
        {props.institution ? <Text fw={650}>{props.institution}</Text> : null}
        <Text className="ds-caption">
          {_('Photos')}:{' '}
          {Object.entries(PHOTOS).map(([key, photo], index) => (
            <Credit key={key} photo={photo} last={index === Object.keys(PHOTOS).length - 1} />
          ))}
        </Text>
      </footer>

      <Modal
        opened={Boolean(open)}
        onClose={() => show(null)}
        title={open === 'register' ? _('Create an account') : _('Sign in')}
        size={open === 'register' ? 'lg' : 'md'}
      >
        <SegmentedControl
          fullWidth
          mb="lg"
          value={open ?? 'login'}
          onChange={(value) => show(value as Auth)}
          data={[
            { value: 'login', label: _('Sign in') },
            { value: 'register', label: _('Create an account') },
          ]}
        />
        {google ? (
          <>
            <Button
              component="a"
              href={route('google.redirect')}
              variant="default"
              fullWidth
              size="md"
              leftSection={<IconBrandGoogleFilled size={18} />}
            >
              {_('Continue with Google')}
            </Button>
            <Divider label={_('or')} labelPosition="center" my="md" />
          </>
        ) : null}
        {open === 'register' ? <SignUpForm /> : <SignInForm />}
      </Modal>
      <DialogHost />
    </div>
  );
}

// Its own frame: the page is the whole window
Welcome.layout = (page: ReactNode) => page;

function Photo({
  photo,
  caption,
  className,
  eager,
}: {
  photo: keyof typeof PHOTOS;
  caption: string;
  className?: string;
  eager?: boolean;
}) {
  return (
    <figure className={className ? `welcome-photo ${className}` : 'welcome-photo'}>
      <img src={PHOTOS[photo].src} alt={caption} loading={eager ? 'eager' : 'lazy'} />
      <figcaption>{caption}</figcaption>
    </figure>
  );
}

function Credit({ photo, last }: { photo: (typeof PHOTOS)[keyof typeof PHOTOS]; last: boolean }) {
  return (
    <>
      <a href={photo.source} target="_blank" rel="noreferrer">
        {photo.author}
      </a>{' '}
      (
      <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noreferrer">
        CC BY-SA 4.0
      </a>
      ){last ? '.' : '; '}
    </>
  );
}

/** The language of the page, each named in itself; the account they sign in to keeps it */
function LanguagePicker({ locales }: { locales: Record<string, string> }) {
  const current = getLocale();
  const pick = (locale: string) =>
    locale !== current &&
    router.put(
      route('language'),
      { locale },
      // The words of the new language come with a fresh start of the app
      { onSuccess: () => window.location.reload() },
    );

  return (
    <Menu position="bottom-end" withinPortal>
      <Menu.Target>
        <Button variant="subtle" color="gray" leftSection={<IconLanguage size={18} />}>
          {locales[current] ?? current}
        </Button>
      </Menu.Target>
      <Menu.Dropdown aria-label={_('Language')}>
        {Object.entries(locales).map(([locale, name]) => (
          <Menu.Item
            key={locale}
            lang={locale.replace('_', '-')}
            onClick={() => pick(locale)}
            // Room kept for the mark, so names stay in line
            rightSection={
              <IconCheck
                size={16}
                style={{ visibility: locale === current ? 'visible' : 'hidden' }}
              />
            }
          >
            {name}
          </Menu.Item>
        ))}
      </Menu.Dropdown>
    </Menu>
  );
}
