<script setup lang="ts">
import SvgBackgroundsItem from './SvgBackgroundsItem.vue';
</script>

<template>
  <div class="flex flex-wrap justify-between gap-4 bg-black p-6">
    <!-- Circuit Pattern -->
    <SvgBackgroundsItem>
      <svg class="size-42">
        <defs>
          <pattern id="circuit" patternUnits="userSpaceOnUse" width="60" height="60">
            <rect width="60" height="60" fill="#0a0a0a" />
            <line x1="0" y1="15" x2="60" y2="15" stroke="#2a1a28" stroke-width="1" />
            <line x1="0" y1="45" x2="60" y2="45" stroke="#2a1a28" stroke-width="1" />
            <line x1="15" y1="0" x2="15" y2="60" stroke="#2a1a28" stroke-width="1" />
            <line x1="45" y1="0" x2="45" y2="60" stroke="#2a1a28" stroke-width="1" />
            <circle cx="15" cy="15" r="2" fill="#1a0a2a" />
            <circle cx="45" cy="15" r="2" fill="#1a0a2a" />
            <circle cx="15" cy="45" r="2" fill="#1a0a2a" />
            <circle cx="45" cy="45" r="2" fill="#1a0a2a" />
            <rect x="13" y="20" width="4" height="10" fill="#242423" />
            <rect x="43" y="30" width="4" height="10" fill="#242423" />
            <rect x="20" y="13" width="10" height="4" fill="#242423" />
            <rect x="30" y="43" width="10" height="4" fill="#242423" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#circuit)" />
      </svg>
    </SvgBackgroundsItem>

    <!-- Hexagon Grid -->
    <SvgBackgroundsItem>
      <svg class="size-42">
        <defs>
          <pattern id="hexagon" patternUnits="userSpaceOnUse" width="40" height="40">
            <rect width="40" height="40" fill="#0d1117" />
            <g stroke="#161b22" stroke-width="0.5" fill="none">
              <polygon points="20,5 30,12.5 30,27.5 20,35 10,27.5 10,12.5" />
              <polygon points="0,25 10,32.5 10,47.5 0,55 -10,47.5 -10,32.5" />
              <polygon points="40,25 50,32.5 50,47.5 40,55 30,47.5 30,32.5" />
              <polygon points="20,-15 30,-7.5 30,7.5 20,15 10,7.5 10,-7.5" />
            </g>
            <circle cx="20" cy="20" r="1" fill="#21262d" opacity="0.6" />
            <circle cx="10" cy="12.5" r="0.5" fill="#30363d" opacity="0.4" />
            <circle cx="30" cy="27.5" r="0.5" fill="#30363d" opacity="0.4" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#hexagon)" />
      </svg>
    </SvgBackgroundsItem>

    <!-- Carbon Fiber -->
    <SvgBackgroundsItem>
      <svg class="size-42">
        <defs>
          <pattern id="carbon" patternUnits="userSpaceOnUse" width="100" height="100">
            <rect width="100" height="100" fill="#0f0f0f" />
            <g opacity="0.3">
              <rect x="0" y="0" width="25" height="50" fill="#1a1a1a" />
              <rect x="50" y="0" width="25" height="50" fill="#1a1a1a" />
              <rect x="25" y="50" width="25" height="50" fill="#1a1a1a" />
              <rect x="75" y="50" width="25" height="50" fill="#1a1a1a" />
              <rect x="25" y="0" width="50" height="25" fill="#151515" />
              <rect x="25" y="50" width="50" height="25" fill="#151515" />
              <rect x="0" y="25" width="50" height="25" fill="#151515" />
              <rect x="50" y="75" width="50" height="25" fill="#151515" />
            </g>
            <g opacity="0.1">
              <line x1="12.5" y1="0" x2="12.5" y2="50" stroke="#333" stroke-width="0.5" />
              <line x1="62.5" y1="0" x2="62.5" y2="50" stroke="#333" stroke-width="0.5" />
              <line x1="37.5" y1="50" x2="37.5" y2="100" stroke="#333" stroke-width="0.5" />
              <line x1="87.5" y1="50" x2="87.5" y2="100" stroke="#333" stroke-width="0.5" />
              <line x1="25" y1="12.5" x2="75" y2="12.5" stroke="#333" stroke-width="0.5" />
              <line x1="25" y1="62.5" x2="75" y2="62.5" stroke="#333" stroke-width="0.5" />
              <line x1="0" y1="37.5" x2="50" y2="37.5" stroke="#333" stroke-width="0.5" />
              <line x1="50" y1="87.5" x2="100" y2="87.5" stroke="#333" stroke-width="0.5" />
            </g>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#carbon)" />
      </svg>
    </SvgBackgroundsItem>

    <!-- Matrix Grid -->
    <SvgBackgroundsItem>
      <svg class="size-42">
        <defs>
          <pattern id="matrix" patternUnits="userSpaceOnUse" width="80" height="80">
            <rect width="80" height="80" fill="#000" />
            <g stroke="#003300" stroke-width="0.5" fill="none" opacity="0.6">
              <line x1="0" y1="20" x2="80" y2="20" />
              <line x1="0" y1="40" x2="80" y2="40" />
              <line x1="0" y1="60" x2="80" y2="60" />
              <line x1="20" y1="0" x2="20" y2="80" />
              <line x1="40" y1="0" x2="40" y2="80" />
              <line x1="60" y1="0" x2="60" y2="80" />
            </g>
            <g fill="#004400" opacity="0.8">
              <rect x="10" y="10" width="2" height="2" />
              <rect x="30" y="25" width="2" height="2" />
              <rect x="50" y="15" width="2" height="2" />
              <rect x="70" y="35" width="2" height="2" />
              <rect x="15" y="45" width="2" height="2" />
              <rect x="35" y="55" width="2" height="2" />
              <rect x="55" y="65" width="2" height="2" />
              <rect x="25" y="70" width="2" height="2" />
            </g>
            <g fill="#006600" opacity="0.4">
              <rect x="40" y="30" width="1" height="1" />
              <rect x="60" y="50" width="1" height="1" />
              <rect x="20" y="60" width="1" height="1" />
              <rect x="10" y="40" width="1" height="1" />
            </g>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#matrix)" />
      </svg>
    </SvgBackgroundsItem>

    <!-- Metal Mesh -->
    <SvgBackgroundsItem>
      <svg class="size-42">
        <defs>
          <pattern id="mesh" patternUnits="userSpaceOnUse" width="50" height="50">
            <rect width="50" height="50" fill="#111" />
            <g stroke="#222" stroke-width="1" fill="none">
              <line x1="0" y1="12.5" x2="50" y2="12.5" />
              <line x1="0" y1="37.5" x2="50" y2="37.5" />
              <line x1="12.5" y1="0" x2="12.5" y2="50" />
              <line x1="37.5" y1="0" x2="37.5" y2="50" />
            </g>
            <g fill="#0a0a0a">
              <rect x="15" y="15" width="8" height="8" rx="1" />
              <rect x="27" y="27" width="8" height="8" rx="1" />
              <rect x="2" y="27" width="8" height="8" rx="1" />
              <rect x="40" y="15" width="8" height="8" rx="1" />
              <rect x="15" y="40" width="8" height="8" rx="1" />
              <rect x="40" y="2" width="8" height="8" rx="1" />
              <rect x="2" y="2" width="8" height="8" rx="1" />
              <rect x="27" y="2" width="8" height="8" rx="1" />
              <rect x="2" y="15" width="8" height="8" rx="1" />
            </g>
            <g stroke="#333" stroke-width="0.5" fill="none" opacity="0.6">
              <rect x="15" y="15" width="8" height="8" rx="1" />
              <rect x="27" y="27" width="8" height="8" rx="1" />
              <rect x="2" y="27" width="8" height="8" rx="1" />
              <rect x="40" y="15" width="8" height="8" rx="1" />
            </g>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#mesh)" />
      </svg>
    </SvgBackgroundsItem>

    <!-- Digital Noise -->
    <SvgBackgroundsItem>
      <svg class="size-42">
        <defs>
          <pattern id="noise" patternUnits="userSpaceOnUse" width="32" height="32">
            <rect width="32" height="32" fill="#0c0c0c" />
            <g fill="#151515" opacity="0.8">
              <rect x="2" y="3" width="1" height="1" />
              <rect x="7" y="1" width="1" height="1" />
              <rect x="12" y="5" width="1" height="1" />
              <rect x="18" y="2" width="1" height="1" />
              <rect x="23" y="7" width="1" height="1" />
              <rect x="29" y="4" width="1" height="1" />
              <rect x="4" y="9" width="1" height="1" />
              <rect x="11" y="11" width="1" height="1" />
              <rect x="16" y="8" width="1" height="1" />
              <rect x="21" y="12" width="1" height="1" />
              <rect x="27" y="10" width="1" height="1" />
              <rect x="31" y="14" width="1" height="1" />
              <rect x="1" y="16" width="1" height="1" />
              <rect x="6" y="18" width="1" height="1" />
              <rect x="13" y="15" width="1" height="1" />
              <rect x="19" y="19" width="1" height="1" />
              <rect x="24" y="17" width="1" height="1" />
              <rect x="30" y="21" width="1" height="1" />
              <rect x="3" y="23" width="1" height="1" />
              <rect x="9" y="25" width="1" height="1" />
              <rect x="14" y="22" width="1" height="1" />
              <rect x="20" y="26" width="1" height="1" />
              <rect x="26" y="24" width="1" height="1" />
              <rect x="5" y="29" width="1" height="1" />
              <rect x="10" y="31" width="1" height="1" />
              <rect x="17" y="28" width="1" height="1" />
              <rect x="22" y="30" width="1" height="1" />
              <rect x="28" y="27" width="1" height="1" />
            </g>
            <g fill="#1a1a1a" opacity="0.5">
              <rect x="5" y="6" width="1" height="1" />
              <rect x="15" y="3" width="1" height="1" />
              <rect x="25" y="9" width="1" height="1" />
              <rect x="8" y="13" width="1" height="1" />
              <rect x="18" y="20" width="1" height="1" />
              <rect x="28" y="16" width="1" height="1" />
              <rect x="2" y="25" width="1" height="1" />
              <rect x="12" y="28" width="1" height="1" />
            </g>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#noise)" />
      </svg>
    </SvgBackgroundsItem>
  </div>
</template>
