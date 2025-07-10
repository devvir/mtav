import { defineStore } from 'pinia';

export default defineStore('projects', {
  state: () => ({
    projects: [],
    current: null,
  }),

  actions: {
    switch(projectId) {
        this.current = this.projects.find(project => project.id === projectId) || null;
    }
  },

  getters: {
    all: state => state.projects,
    current: state => state.current,
  },
})
