import { AppearanceSettings } from '@/components/account/AppearanceSettings';
import { LanguageSettings } from '@/components/account/LanguageSettings';
import { PasswordForm } from '@/components/account/PasswordForm';
import { Form, FormActions } from '@/components/forms/Form';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar } from '@/design';
import { checkbox, field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router, useForm, usePage } from '@inertiajs/react';
import {
  Button,
  Fieldset,
  FileButton,
  Group,
  SimpleGrid,
  Stack,
  Switch,
  Tabs,
  Text,
  TextInput,
  Textarea,
} from '@mantine/core';
import { IconCamera, IconKey, IconPalette, IconUser } from '@tabler/icons-react';
import { useState } from 'react';

const SECTIONS = ['about', 'password', 'preferences'] as const;
type Section = (typeof SECTIONS)[number];

/** The section the address names (?section=password), so that a link or a reload opens it */
const sectionInUrl = (): Section => {
  const named = new URLSearchParams(window.location.search).get('section');
  return SECTIONS.find((section) => section === named) ?? 'about';
};

interface Props {
  profile: {
    firstname: string;
    lastname: string | null;
    email: string;
    new_email: string | null;
    phone: string | null;
    email_public: boolean;
    phone_public: boolean;
    legal_id: string | null;
    about: string | null;
  };
  /** The languages to pick from, each named in itself */
  languages: Record<string, string>;
}

export default function Profile({ profile, languages }: Props) {
  const { auth, errors } = usePage().props;
  const user = auth.user!;
  const form = useForm({
    firstname: profile.firstname,
    lastname: profile.lastname ?? '',
    email: profile.email,
    phone: profile.phone ?? '',
    email_public: profile.email_public,
    phone_public: profile.phone_public,
    legal_id: profile.legal_id ?? '',
    about: profile.about ?? '',
  });

  const [section, setSection] = useState<Section>(sectionInUrl);
  const open = (value: string | null) => {
    const chosen = SECTIONS.find((section) => section === value) ?? 'about';
    setSection(chosen);
    const url = new URL(window.location.href);
    if (chosen === 'about') url.searchParams.delete('section');
    else url.searchParams.set('section', chosen);
    window.history.replaceState(window.history.state, '', url);
  };

  const uploadAvatar = (file: File | null) =>
    file &&
    router.post(
      route('avatar.update'),
      { avatar: file },
      { forceFormData: true, preserveScroll: true },
    );

  return (
    <>
      <PageHeader
        title={_('Profile')}
        description={_(
          'Everything about you: how people see you, how you sign in, your language and how the app looks.',
        )}
      />
      <Tabs value={section} onChange={open} maw={760}>
        <Tabs.List mb="lg">
          <Tabs.Tab value="about" leftSection={<IconUser size={18} />}>
            {_('About you')}
          </Tabs.Tab>
          <Tabs.Tab value="password" leftSection={<IconKey size={18} />}>
            {_('Password')}
          </Tabs.Tab>
          <Tabs.Tab value="preferences" leftSection={<IconPalette size={18} />}>
            {_('Preferences')}
          </Tabs.Tab>
        </Tabs.List>
        <Tabs.Panel value="about" className="ds-surface profile-section">
          <Group mb="lg" gap="md">
            <PersonAvatar
              name={user.name}
              src={user.avatar}
              size={64}
              expandable={_('See the photo of {name}', { name: user.name })}
            />
            <FileButton onChange={uploadAvatar} accept="image/jpeg,image/png,image/webp">
              {(props) => (
                <Button variant="default" leftSection={<IconCamera size={18} />} {...props}>
                  {_('Change picture')}
                </Button>
              )}
            </FileButton>
          </Group>
          {errors.avatar ? (
            <Text c="red" size="sm" role="alert" mb="md">
              {errors.avatar}
            </Text>
          ) : null}
          <Form onSubmit={() => form.patch(route('profile.update'), { preserveScroll: true })}>
            <SimpleGrid cols={{ base: 1, sm: 2 }}>
              <TextInput
                label={_('First name')}
                withAsterisk
                autoComplete="given-name"
                {...field(form, 'firstname')}
              />
              <TextInput
                label={_('Last name')}
                autoComplete="family-name"
                {...field(form, 'lastname')}
              />
            </SimpleGrid>
            <TextInput
              label={_('Email')}
              withAsterisk
              type="email"
              autoComplete="email"
              description={
                profile.new_email
                  ? _('Confirm {email} to change it', { email: profile.new_email })
                  : _('Changes are confirmed by email')
              }
              {...field(form, 'email')}
            />
            <SimpleGrid cols={{ base: 1, sm: 2 }}>
              <TextInput
                label={_('Phone')}
                type="tel"
                autoComplete="tel"
                {...field(form, 'phone')}
              />
              <TextInput
                label={_('ID number')}
                description={_('Only admins see it')}
                {...field(form, 'legal_id')}
              />
            </SimpleGrid>
            {/* Who sees how to reach them: their own choice (admins always see both) */}
            <Fieldset legend={_('Who sees your email and phone')} variant="unstyled">
              <Stack gap="xs">
                <Text className="ds-caption">
                  {_('The admins of your projects always do. Everyone else, if you share them.')}
                </Text>
                <Switch
                  label={_('Show my email to the people in my projects')}
                  {...checkbox(form, 'email_public')}
                />
                <Switch
                  label={_('Show my phone to the people in my projects')}
                  {...checkbox(form, 'phone_public')}
                />
              </Stack>
            </Fieldset>
            <Textarea label={_('About you')} autosize minRows={3} {...field(form, 'about')} />
            <FormActions submit={_('Save')} processing={form.processing} />
          </Form>
        </Tabs.Panel>
        <Tabs.Panel value="password" className="ds-surface profile-section">
          <PasswordForm />
        </Tabs.Panel>
        <Tabs.Panel value="preferences" className="ds-surface profile-section">
          <LanguageSettings languages={languages} />
          <Text fw={650} mt="xl" mb={4}>
            {_('Appearance')}
          </Text>
          <Text className="ds-caption" mb="md">
            {_('Only on this device.')}
          </Text>
          <AppearanceSettings />
        </Tabs.Panel>
      </Tabs>
    </>
  );
}
