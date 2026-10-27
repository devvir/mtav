import RankingList from '@/components/domain/RankingList.vue';
import type { Unit } from '@/types/models';
import { mount } from '@vue/test-utils';

const units = (): Unit[] =>
  ['A-101', 'A-102', 'A-103'].map((identifier, id) => ({ id, identifier, can: {} }));

describe("a family's ranking", () => {
  it('moves a unit up, says so, and reports the new order', async () => {
    const wrapper = mount(RankingList, { props: { modelValue: units() } });

    await wrapper.get('[aria-label="Move A-103 up"]').trigger('click');

    const [order] = wrapper.emitted<[Unit[]]>('change')!.at(-1)!;
    expect(order.map((unit) => unit.identifier)).toEqual(['A-101', 'A-103', 'A-102']);
    expect(wrapper.get('[aria-live]').text()).toContain('A-103');
  });

  it('cannot be reordered when locked', () => {
    const wrapper = mount(RankingList, { props: { modelValue: units(), disabled: true } });

    expect(wrapper.find('[aria-label="Move A-103 up"]').exists()).toBe(false);
  });
});
