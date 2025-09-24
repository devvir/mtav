const page = usePage();

export const currentProject = computed<Project | null>(() => page.props.state.project);
export const projectIsSelected = computed(() => !!currentProject.value);

export const setCurrentProject = (project: Project): void => {
  router.flushAll();
  router.post(route('setCurrentProject', project.id));
};

export const resetCurrentProject = (): void => {
  router.flushAll();
  router.delete(route('resetCurrentProject'));
};
