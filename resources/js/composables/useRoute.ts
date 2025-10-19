const page = usePage();

export const currentRoute = computed(() => page.props.state.route);
