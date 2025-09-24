import { DOMWrapper, mount } from "@vue/test-utils";
import IndexSearch from "@/components/pagination/IndexSearch.vue";

let wrapper: ReturnType<typeof mount>;
let input: DOMWrapper<HTMLInputElement>;

describe('DOM example tests', () => {
    document.body.innerHTML = '<div id="app"></div>';
    beforeEach(() => {
        wrapper = mount(IndexSearch, { props: { q: 'test' }, attachTo: '#app' });
        input = wrapper.find('input');
    });
    // afterEach(() => { ... });
    // beforeAll(() => { ... });
    // afterAll(() => { ... });

    test('Search Component exists', () => {
        expect(IndexSearch).toBeDefined();
    });

    test('Search input has placeholder "Search...", autofocus, and is of type "search"', () => {
        expect(input.attributes('type')).toBe('search');
        expect(input.attributes('placeholder')).toBe('Search...');
        expect(input.attributes('autofocus')).toBeDefined();
    });

    test('Search input starts with the value from the q prop', () => {
        expect(input.element.value).toBe('test');
    });

    test('Search Component does not contain text outside its input', () => {
        expect(wrapper.text()).toBe('');
    });

    test('Example of DOM manipulation (on the input element)', async () => {
        await input.setValue('lorem');
        await input.trigger('keydown.enter');
        expect(wrapper.html()).toContain('autofocus');
        // ...
    });

    test('Spy/Mock example', () => {
        // Spy on console.warn, and mock its implementation to do nothing
        const spy = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
        expect(console.warn).not.toHaveBeenCalled();

        // Alternatively, you can mock it like this, and it effectively spies on the empty mock
        // However, this will permanently replace the implementation until explicitely restored
        console.log = vi.fn();
        expect(console.log).not.toHaveBeenCalled();

        // Clear mock state (implementation remains). Not needed if config contains clearMocks: true
        spy.mockClear();

        // This will only empty .mock state, it will not affect mock implementations.
        // This is useful if you need to clean up mocks between different assertions within a test.
        vi.clearAllMocks();

        // This will empty .mock state, reset "once" implementations, and reset each mock's base implementation to its original.
        //This is useful when you want to reset all mocks to their original states.
        vi.resetAllMocks();
    });

    test('Does it restore console.log and console.warn?', () => {
        console.log('test log');    // This is still mocked to vi.fn()!
        console.warn('test warn');  // This mock was restored to the original console.warn
    });

    describe('Nested describe block and usage of .each()', () => {
        beforeEach(() => {
            // This only runs before each test in THIS describe block
        });

        test.each([ // You can also do describe.each(...)
            { params: ['First Param'], description: 'Test description 1' },
            { params: ['Second Param'], description: 'Test description 2' },
        ])('Test: $description', async ({ params }) => {
            expect(params[0]).toMatch(/^(First|Second) Param$/);
        });
    });

    test('Vue specific methods', async () => {
        expect(wrapper.exists()).toBe(true);
        expect(wrapper.isVisible()).toBe(true);
        expect(wrapper.classes()).toContain('flex');
        expect(wrapper.attributes('class')).toBeDefined();
        expect(wrapper.props('q')).toBe('test');

        await wrapper.setProps({ q: 'changed' });
        expect(wrapper.props('q')).toBe('changed');
        expect(input.element.value).toBe('test');
        console.warn(document.body.innerHTML);

        // This counts 0. Maybe it doesn't count the root component?
        // expect(wrapper.findAllComponents(IndexSearch)).toHaveLength(1);
    });
});