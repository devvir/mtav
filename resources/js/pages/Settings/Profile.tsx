import { AppearanceSettings } from '@/components/account/AppearanceSettings';
import { PasswordForm } from '@/components/account/PasswordForm';
import { Form, FormActions } from '@/components/forms/Form';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar } from '@/design';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router, useForm, usePage } from '@inertiajs/react';
import {
  Button,
  FileButton,
  Group,
  SimpleGrid,
  Tabs,
  Text,
  TextInput,
  Textarea,
} from '@mantine/core';
import { IconCamera, IconKey, IconPalette, IconUser } from '@tabler/icons-react';
import { useState } from 'react';

const SECTIONS = ['about', 'password', 'appearance'] as const;
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
    legal_id: string | null;
    about: string | null;
  };
}

export default function Profile({ profile }: Props) {
  const { auth, errors } = usePage().props;
  const user = auth.user!;
  const form = useForm({
    firstname: profile.firstname,
    lastname: profile.lastname ?? '',
    email: profile.email,
    phone: profile.phone ?? '',
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
          'Everything about you: how people see you, how you sign in, how the app looks.',
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
          <Tabs.Tab value="appearance" leftSection={<IconPalette size={18} />}>
            {_('Appearance')}
          </Tabs.Tab>
        </Tabs.List>
        <Tabs.Panel value="about" className="ds-surface profile-section">
          <Group mb="lg" gap="md">
            <PersonAvatar name={user.name} src={user.avatar} size={64} />
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
            <Textarea label={_('About you')} autosize minRows={3} {...field(form, 'about')} />
            <FormActions submit={_('Save')} processing={form.processing} />
          </Form>
        </Tabs.Panel>
        <Tabs.Panel value="password" className="ds-surface profile-section">
          <PasswordForm />
        </Tabs.Panel>
        <Tabs.Panel value="appearance" className="ds-surface profile-section">
          <Text c="dimmed" size="sm" mb="lg">
            {_('Only on this device.')}
          </Text>
          <AppearanceSettings />
        </Tabs.Panel>
      </Tabs>
    </>
  );
}
