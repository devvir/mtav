const page = usePage();

export const currentProject = computed<Project | null>(() => page.props.project);
export const projectIsSelected = computed(() => !!currentProject.value);
