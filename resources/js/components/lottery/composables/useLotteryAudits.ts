// Copilot - Pending review
import { ComputedRef } from 'vue';

export interface LotteryAuditsComposable {
  hasAudits: ComputedRef<boolean>;
  hasFailure: ComputedRef<boolean>;
  failureAudit: ComputedRef<LotteryAudit | undefined>;
  totalUnitTypes: ComputedRef<number>;
  groupsCount: ComputedRef<number>;
  processedGroups: ComputedRef<number>;
  progress: ComputedRef<number>;
  isExecuting: ComputedRef<boolean>;
  initAuditTimestamp: ComputedRef<number | null>;
}

export const useLotteryAudits = (
  lottery: ComputedRef<Lottery> | Ref<Lottery>,
): LotteryAuditsComposable => {
  const lotteryValue = isRef(lottery) ? lottery : computed(() => lottery.value);

  // Check if there are any audits
  const hasAudits = computed(() => {
    return !!lotteryValue.value?.audits?.length;
  });

  // Check if there's a failure audit
  const hasFailure = computed(() => {
    return !!lotteryValue.value?.audits?.some((audit: LotteryAudit) => audit.type === 'failure');
  });

  // Get the failure audit (most recent one if multiple)
  const failureAudit = computed(() => {
    return lotteryValue.value?.audits?.find((audit: LotteryAudit) => audit.type === 'failure');
  });

  // Get init audit for timestamp
  const initAudit = computed(() => {
    return lotteryValue.value?.audits?.find((audit: LotteryAudit) => audit.type === 'init');
  });

  // Get timestamp from init audit (in milliseconds)
  const initAuditTimestamp = computed(() => {
    if (!initAudit.value?.created_at) return null;
    return new Date(initAudit.value.created_at).getTime();
  });

  // Total unit types from manifest in init audit
  const totalUnitTypes = computed(() => {
    if (!initAudit.value?.audit?.manifest?.data) return 0;
    return Object.keys(initAudit.value.audit.manifest.data).length;
  });

  // Check if there will be an orphans/second-chance group
  const hasOrphansGroup = computed(() => {
    if (!initAudit.value?.audit?.manifest?.data) return false;

    const manifestData = initAudit.value.audit.manifest.data;

    // Check each unit type group for imbalance (more families than units or vice versa)
    for (const unitTypeData of Object.values(manifestData)) {
      const familiesCount = Object.keys((unitTypeData as any).families || {}).length;
      const unitsCount = ((unitTypeData as any).units || []).length;

      if (familiesCount !== unitsCount) {
        return true; // There will be orphans that need redistribution
      }
    }

    return false;
  });

  // Total groups to process (unit types + orphans group if needed)
  const groupsCount = computed(() => {
    return totalUnitTypes.value + (hasOrphansGroup.value ? 1 : 0);
  });

  // Count processed groups from group_execution audits
  const processedGroups = computed(() => {
    if (!lotteryValue.value?.audits) return 0;

    const groupExecutions = lotteryValue.value.audits.filter(
      (audit: LotteryAudit) => audit.type === 'group_execution',
    );

    return groupExecutions.length;
  });

  // Progress percentage (0-100) based on groups
  const progress = computed(() => {
    if (groupsCount.value === 0) return 0;
    return Math.round((processedGroups.value / groupsCount.value) * 100);
  });

  // Check if execution is in progress (not failed, not completed)
  const isExecuting = computed(() => {
    return lotteryValue.value?.is_executing && !hasFailure.value;
  });

  return {
    hasAudits,
    hasFailure,
    failureAudit,
    totalUnitTypes,
    groupsCount,
    processedGroups,
    progress,
    isExecuting,
    initAuditTimestamp,
  };
}
