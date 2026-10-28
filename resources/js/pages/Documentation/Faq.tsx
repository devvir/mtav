import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { Link } from '@inertiajs/react';
import { Accordion, Anchor } from '@mantine/core';

interface Props {
  title: string;
  description: string;
  guideText: string;
  questions: { question: string; answer: string }[];
}

export default function Faq({ title, description, guideText, questions }: Props) {
  return (
    <>
      <PageHeader title={title} description={description} />
      <Accordion variant="separated" radius="lg" maw={860} classNames={{ item: 'ds-surface' }}>
        {questions.map((item) => (
          <Accordion.Item key={item.question} value={item.question}>
            <Accordion.Control fw={650}>{item.question}</Accordion.Control>
            <Accordion.Panel c="dimmed">{item.answer}</Accordion.Panel>
          </Accordion.Item>
        ))}
      </Accordion>
      <Anchor
        component={Link}
        href={route('documentation.guide')}
        fw={600}
        mt="lg"
        display="inline-block"
      >
        {guideText}
      </Anchor>
    </>
  );
}
