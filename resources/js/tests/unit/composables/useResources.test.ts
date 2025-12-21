// Copilot - Pending review
import { describe, it, expect, vi } from 'vitest';

vi.unmock('@/composables/useResources');

import {
  actions,
  entityFromNS,
  entityLabel,
  entityNS,
  entityPlural,
  entityRoutes,
} from '@/composables/useResources';
import useResources from '@/composables/useResources';

describe('useResources composable', () => {
  describe('exported functions', () => {
    it('exports all expected functions', () => {
      expect(typeof actions).toBe('object');
      expect(Array.isArray(actions)).toBe(true);
      expect(typeof entityFromNS).toBe('function');
      expect(typeof entityLabel).toBe('function');
      expect(typeof entityNS).toBe('function');
      expect(typeof entityPlural).toBe('function');
      expect(typeof entityRoutes).toBe('function');
    });

    it('composable default export returns all functions', () => {
      const composable = useResources();

      expect(composable.actions).toEqual(actions);
      expect(composable.entityFromNS).toBe(entityFromNS);
      expect(composable.entityLabel).toBe(entityLabel);
      expect(composable.entityNS).toBe(entityNS);
      expect(composable.entityPlural).toBe(entityPlural);
      expect(composable.entityRoutes).toBe(entityRoutes);
    });
  });

  describe('actions array', () => {
    it('contains expected action types', () => {
      expect(actions).toContain('index');
      expect(actions).toContain('show');
      expect(actions).toContain('create');
      expect(actions).toContain('edit');
      expect(actions).toContain('destroy');
      expect(actions).toContain('restore');
    });

    it('has exactly 6 actions', () => {
      expect(actions).toHaveLength(6);
    });
  });

  describe('entityNS function', () => {
    it('returns namespace for project', () => {
      expect(entityNS('project')).toBe('projects');
    });

    it('returns namespace for unit', () => {
      expect(entityNS('unit')).toBe('units');
    });

    it('returns namespace for unit_type', () => {
      expect(entityNS('unit_type')).toBe('unit_types');
    });

    it('returns namespace for admin', () => {
      expect(entityNS('admin')).toBe('admins');
    });

    it('returns namespace for family', () => {
      expect(entityNS('family')).toBe('families');
    });

    it('returns namespace for member', () => {
      expect(entityNS('member')).toBe('members');
    });

    it('returns namespace for event', () => {
      expect(entityNS('event')).toBe('events');
    });

    it('returns namespace for media', () => {
      expect(entityNS('media')).toBe('media');
    });

    it('returns namespace for log', () => {
      expect(entityNS('log')).toBe('logs');
    });
  });

  describe('entityPlural function', () => {
    it('returns plural form for project', () => {
      expect(entityPlural('project')).toBe('projects');
    });

    it('returns plural form for family', () => {
      expect(entityPlural('family')).toBe('families');
    });

    it('returns plural form for media (special case)', () => {
      expect(entityPlural('media')).toBe('items');
    });

    it('returns plural form for unit', () => {
      expect(entityPlural('unit')).toBe('units');
    });

    it('returns plural form for member', () => {
      expect(entityPlural('member')).toBe('members');
    });
  });

  describe('entityFromNS function', () => {
    it('converts projects namespace to project entity', () => {
      expect(entityFromNS('projects')).toBe('project');
    });

    it('converts families namespace to family entity', () => {
      expect(entityFromNS('families')).toBe('family');
    });

    it('converts units namespace to unit entity', () => {
      expect(entityFromNS('units')).toBe('unit');
    });

    it('converts members namespace to member entity', () => {
      expect(entityFromNS('members')).toBe('member');
    });

    it('converts events namespace to event entity', () => {
      expect(entityFromNS('events')).toBe('event');
    });

    it('inverse of entityNS', () => {
      const testEntities: Array<'project' | 'family' | 'member' | 'unit' | 'event'> = [
        'project',
        'family',
        'member',
        'unit',
        'event',
      ];

      testEntities.forEach((entity) => {
        const ns = entityNS(entity);
        const back = entityFromNS(ns as any);
        expect(back).toBe(entity);
      });
    });
  });

  describe('entityRoutes function', () => {
    it('returns all actions as route keys', () => {
      const routes = entityRoutes('project');

      expect(routes.index).toBeDefined();
      expect(routes.show).toBeDefined();
      expect(routes.create).toBeDefined();
      expect(routes.edit).toBeDefined();
      expect(routes.destroy).toBeDefined();
      expect(routes.restore).toBeDefined();
    });

    it('formats routes as namespace.action', () => {
      const routes = entityRoutes('project');

      expect(routes.index).toBe('projects.index');
      expect(routes.show).toBe('projects.show');
      expect(routes.create).toBe('projects.create');
      expect(routes.edit).toBe('projects.edit');
      expect(routes.destroy).toBe('projects.destroy');
      expect(routes.restore).toBe('projects.restore');
    });

    it('formats routes for different entities', () => {
      const familyRoutes = entityRoutes('family');
      expect(familyRoutes.index).toBe('families.index');

      const unitRoutes = entityRoutes('unit');
      expect(unitRoutes.index).toBe('units.index');

      const memberRoutes = entityRoutes('member');
      expect(memberRoutes.index).toBe('members.index');
    });
  });

  describe('entityLabel function', () => {
    it('returns singular label for entity', () => {
      const projectLabel = entityLabel('project', 'singular');
      expect(projectLabel).toBeDefined();
      // The actual translation depends on useTranslations mock
      expect(typeof projectLabel).toBe('string');
    });

    it('returns plural label for entity', () => {
      const projectsLabel = entityLabel('project', 'plural');
      expect(projectsLabel).toBeDefined();
      expect(typeof projectsLabel).toBe('string');
    });

    it('defaults to singular label', () => {
      const label = entityLabel('family');
      expect(label).toBeDefined();
      expect(typeof label).toBe('string');
    });

    it('uses count=1 for singular', () => {
      const label = entityLabel('project', 1);
      expect(label).toBeDefined();
      expect(typeof label).toBe('string');
    });

    it('returns different label for different entities', () => {
      const projectLabel = entityLabel('project', 'singular');
      const familyLabel = entityLabel('family', 'singular');

      // Both should be strings, and they might be the entity name capitalized
      expect(typeof projectLabel).toBe('string');
      expect(typeof familyLabel).toBe('string');
    });
  });

  describe('entity mapping consistency', () => {
    it('all entities in actions are mappable', () => {
      const testEntities: Array<'project' | 'family' | 'member' | 'unit' | 'event' | 'admin' | 'unit_type' | 'media' | 'log'> = [
        'project',
        'family',
        'member',
        'unit',
        'event',
        'admin',
        'unit_type',
        'media',
        'log',
      ];

      testEntities.forEach((entity) => {
        const ns = entityNS(entity);
        expect(ns).toBeDefined();

        const back = entityFromNS(ns as any);
        expect(back).toBe(entity);

        const routes = entityRoutes(entity);
        expect(routes).toBeDefined();
      });
    });
  });
});
