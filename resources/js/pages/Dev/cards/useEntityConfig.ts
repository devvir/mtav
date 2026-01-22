import { entityLabel, entityPlural } from '@/composables/useResources';
import {
  Building,
  Calendar,
  FileText,
  Home,
  Image,
  Layers,
  UserCheck,
  Users,
} from 'lucide-vue-next';

// Import all entity cards
import AdminIndexCard from '@/components/entities/admin/IndexCard.vue';
import AdminShowCard from '@/components/entities/admin/ShowCard.vue';
import EventIndexCard from '@/components/entities/event/IndexCard.vue';
import EventShowCard from '@/components/entities/event/ShowCard.vue';
import FamilyIndexCard from '@/components/entities/family/IndexCard.vue';
import FamilyShowCard from '@/components/entities/family/ShowCard.vue';
import LogIndexCard from '@/components/entities/log/IndexCard.vue';
import LogShowCard from '@/components/entities/log/ShowCard.vue';
import MediaIndexCard from '@/components/entities/media/IndexCard.vue';
import MediaShowCard from '@/components/entities/media/ShowCard.vue';
import MemberIndexCard from '@/components/entities/member/IndexCard.vue';
import MemberShowCard from '@/components/entities/member/ShowCard.vue';
import ProjectIndexCard from '@/components/entities/project/IndexCard.vue';
import ProjectShowCard from '@/components/entities/project/ShowCard.vue';
import UnitIndexCard from '@/components/entities/unit/IndexCard.vue';
import UnitShowCard from '@/components/entities/unit/ShowCard.vue';
import UnitTypeIndexCard from '@/components/entities/unit_type/IndexCard.vue';
import UnitTypeShowCard from '@/components/entities/unit_type/ShowCard.vue';

export interface EntityConfig {
  icon: any;
  description: string;
  color: string;
  indexCard: any;
  showCard: any;
}

export interface EntityDisplay {
  entity: AppEntity;
  key: string;
  name: string;
  propName: string;
  singularPropName: string;
  icon: any;
  description: string;
  color: string;
  indexCard: any;
  showCard: any;
}

// Entity configuration - purely declarative
const entityConfig: Partial<Record<AppEntity, EntityConfig>> = {
  project: {
    icon: Building,
    description: 'Housing cooperative projects managed by admins',
    color: 'text-blue-500',
    indexCard: ProjectIndexCard,
    showCard: ProjectShowCard,
  },
  unit: {
    icon: Home,
    description: 'Living units within each project',
    color: 'text-green-500',
    indexCard: UnitIndexCard,
    showCard: UnitShowCard,
  },
  unit_type: {
    icon: Layers,
    description: 'Unit type categories and specifications',
    color: 'text-teal-500',
    indexCard: UnitTypeIndexCard,
    showCard: UnitTypeShowCard,
  },
  admin: {
    icon: UserCheck,
    description: 'Project administrators and managers',
    color: 'text-purple-500',
    indexCard: AdminIndexCard,
    showCard: AdminShowCard,
  },
  member: {
    icon: Users,
    description: 'Family members participating in projects',
    color: 'text-indigo-500',
    indexCard: MemberIndexCard,
    showCard: MemberShowCard,
  },
  family: {
    icon: Home,
    description: 'Family units (atomic participation units)',
    color: 'text-orange-500',
    indexCard: FamilyIndexCard,
    showCard: FamilyShowCard,
  },
  event: {
    icon: Calendar,
    description: 'Project events and activities',
    color: 'text-rose-500',
    indexCard: EventIndexCard,
    showCard: EventShowCard,
  },
  log: {
    icon: FileText,
    description: 'System activity logs and audit trails',
    color: 'text-gray-500',
    indexCard: LogIndexCard,
    showCard: LogShowCard,
  },
  media: {
    icon: Image,
    description: 'Photo galleries and media collections',
    color: 'text-pink-500',
    indexCard: MediaIndexCard,
    showCard: MediaShowCard,
  },
};

/**
 * Convert snake_case to camelCase
 */
const toCamelCase = (str: string): string => {
  return str.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
};

/**
 * Composable for entity configuration and data transformation
 */
export const useEntityConfig = () => {
  /**
   * Get entities that have data, transformed for display
   */
  const getAvailableEntities = (entityData: Record<string, ApiResource[]>): EntityDisplay[] => {
    return Object.keys(entityConfig)
      .filter((entity) => {
        const pluralKey = entityPlural(entity as AppEntity);
        const camelKey = toCamelCase(pluralKey);
        return entityData[camelKey]?.length > 0;
      })
      .map((entity) => createEntityDisplay(entity as AppEntity));
  };

  /**
   * Transform a raw entity type into a display-ready object
   */
  const createEntityDisplay = (entity: AppEntity): EntityDisplay => {
    const config = entityConfig[entity]!;
    const pluralKey = entityPlural(entity);

    return {
      entity,
      key: pluralKey,
      name: entityLabel(entity, 'plural').replace(/_/g, ' '),
      propName: toCamelCase(pluralKey),
      singularPropName: entity,
      ...config,
    };
  };

  return {
    getAvailableEntities,
    createEntityDisplay,
  };
};
