<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
  >
    <template v-if="name === 'grid'">
      <path d="M4 4.75H10V10.75H4V4.75Z" :stroke-width="strokeWidth" stroke="currentColor" />
      <path d="M14 4.75H20V10.75H14V4.75Z" :stroke-width="strokeWidth" stroke="currentColor" />
      <path d="M4 14.75H10V20.75H4V14.75Z" :stroke-width="strokeWidth" stroke="currentColor" />
      <path d="M14 14.75H20V20.75H14V14.75Z" :stroke-width="strokeWidth" stroke="currentColor" />
    </template>

    <template v-else-if="name === 'users'">
      <path d="M16 19C16 16.7909 14.2091 15 12 15H8C5.79086 15 4 16.7909 4 19" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M10 11C11.933 11 13.5 9.433 13.5 7.5C13.5 5.567 11.933 4 10 4C8.067 4 6.5 5.567 6.5 7.5C6.5 9.433 8.067 11 10 11Z" :stroke-width="strokeWidth" stroke="currentColor" />
      <path d="M17 11C18.3807 11 19.5 9.88071 19.5 8.5C19.5 7.11929 18.3807 6 17 6" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M20 18C20 16.4766 18.9877 15.1897 17.6 14.775" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'building'">
      <path d="M4.75 20.25H19.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M6.75 20.25V5.75L12 3.75L17.25 5.75V20.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M9 8.5H9.01" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M9 11.75H9.01" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M15 8.5H15.01" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M15 11.75H15.01" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M11 20.25V15.5H13V20.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'briefcase'">
      <path d="M9 7V5.75C9 4.7835 9.7835 4 10.75 4H13.25C14.2165 4 15 4.7835 15 5.75V7" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M4.75 9H19.25V17.25C19.25 18.7688 18.0188 20 16.5 20H7.5C5.98122 20 4.75 18.7688 4.75 17.25V9Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M4.75 11.5C6.9 12.9 9.28333 13.6 11.9 13.6C14.5167 13.6 16.9667 12.9 19.25 11.5" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'layers'">
      <path d="M12 4L20 8.5L12 13L4 8.5L12 4Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M6.25 11.75L12 15L17.75 11.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M6.25 15.25L12 18.5L17.75 15.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'history'">
      <path d="M4.75 12C4.75 7.99594 7.99594 4.75 12 4.75C16.0041 4.75 19.25 7.99594 19.25 12C19.25 16.0041 16.0041 19.25 12 19.25C9.58579 19.25 7.44736 18.0701 6.12753 16.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M4.75 7.25V12H9.5" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M12 8.5V12L14.5 13.5" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'file'">
      <path d="M8 4.75H13.75L18.25 9.25V19.25H8C6.48122 19.25 5.25 18.0188 5.25 16.5V7.5C5.25 5.98122 6.48122 4.75 8 4.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M13.5 4.75V9.5H18.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'folder'">
      <path d="M4.75 8.25C4.75 7.00736 5.75736 6 7 6H9.75L11.25 7.75H17C18.2426 7.75 19.25 8.75736 19.25 10V16.75C19.25 17.9926 18.2426 19 17 19H7C5.75736 19 4.75 17.9926 4.75 16.75V8.25Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'tag'">
      <path d="M13.75 4.75H18.25V9.25L11 16.5L6.5 12L13.75 4.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M15.75 7.25H15.76" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M4.75 13.75L6.5 12" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'wallet'">
      <path d="M4.75 8.5C4.75 6.98122 5.98122 5.75 7.5 5.75H17.5C18.4665 5.75 19.25 6.5335 19.25 7.5V16.5C19.25 17.4665 18.4665 18.25 17.5 18.25H7.5C5.98122 18.25 4.75 17.0188 4.75 15.5V8.5Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M4.75 8.25H16.5" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M15.75 13H19.25V10.75H15.75C15.1287 10.75 14.625 11.2537 14.625 11.875C14.625 12.4963 15.1287 13 15.75 13Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'calendar'">
      <path d="M7.25 4.75V7" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M16.75 4.75V7" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M5.25 8H18.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M6.75 5.75H17.25C18.3546 5.75 19.25 6.64543 19.25 7.75V17.25C19.25 18.3546 18.3546 19.25 17.25 19.25H6.75C5.64543 19.25 4.75 18.3546 4.75 17.25V7.75C4.75 6.64543 5.64543 5.75 6.75 5.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'bell'">
      <path d="M7.5 16.25H16.5L15.75 14.25V10C15.75 7.92893 14.0711 6.25 12 6.25C9.92893 6.25 8.25 7.92893 8.25 10V14.25L7.5 16.25Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M10.25 18C10.6297 18.7603 11.2516 19.25 12 19.25C12.7484 19.25 13.3703 18.7603 13.75 18" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'settings'">
      <path d="M12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5Z" :stroke-width="strokeWidth" stroke="currentColor" />
      <path d="M12 4.75V6.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M12 17.75V19.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M19.25 12H17.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M6.25 12H4.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M17.1265 6.87354L16.0658 7.9342" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M7.93413 16.0659L6.87347 17.1265" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M17.1265 17.1265L16.0658 16.0658" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M7.93413 7.9342L6.87347 6.87354" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'sparkles'">
      <path d="M12 4.75L13.3462 8.65377L17.25 10L13.3462 11.3462L12 15.25L10.6538 11.3462L6.75 10L10.6538 8.65377L12 4.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M18.25 4.75L18.6769 6.0731L20 6.5L18.6769 6.9269L18.25 8.25L17.8231 6.9269L16.5 6.5L17.8231 6.0731L18.25 4.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M5.75 15.75L6.1769 17.0731L7.5 17.5L6.1769 17.9269L5.75 19.25L5.3231 17.9269L4 17.5L5.3231 17.0731L5.75 15.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'clock'">
      <path d="M12 19.25C16.0041 19.25 19.25 16.0041 19.25 12C19.25 7.99594 16.0041 4.75 12 4.75C7.99594 4.75 4.75 7.99594 4.75 12C4.75 16.0041 7.99594 19.25 12 19.25Z" :stroke-width="strokeWidth" stroke="currentColor" />
      <path d="M12 8V12L14.75 13.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'clipboard'">
      <path d="M9 5.75H15" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M9.75 4H14.25C14.9404 4 15.5 4.55964 15.5 5.25V6.25H8.5V5.25C8.5 4.55964 9.05964 4 9.75 4Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M8 5.75H16C17.2426 5.75 18.25 6.75736 18.25 8V17.75C18.25 18.9926 17.2426 20 16 20H8C6.75736 20 5.75 18.9926 5.75 17.75V8C5.75 6.75736 6.75736 5.75 8 5.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M9.25 11H14.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M9.25 14.5H14.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'receipt'">
      <path d="M7.5 4.75H16.5V19.25L14.625 18L12.75 19.25L10.875 18L9 19.25L7.125 18L5.25 19.25V7C5.25 5.75736 6.25736 4.75 7.5 4.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M8.75 9H14.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M8.75 12H14.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M8.75 15H12.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'sun'">
      <path d="M12 8.75C10.2051 8.75 8.75 10.2051 8.75 12C8.75 13.7949 10.2051 15.25 12 15.25C13.7949 15.25 15.25 13.7949 15.25 12C15.25 10.2051 13.7949 8.75 12 8.75Z" :stroke-width="strokeWidth" stroke="currentColor" />
      <path d="M12 4.75V6.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M12 17.75V19.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M19.25 12H17.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M6.25 12H4.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M17.1265 6.87354L16.0658 7.9342" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M7.93413 16.0659L6.87347 17.1265" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M17.1265 17.1265L16.0658 16.0658" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M7.93413 7.9342L6.87347 6.87354" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'moon'">
      <path d="M14.7044 4.75C12.8295 5.36873 11.5 7.13582 11.5 9.21382C11.5 11.8078 13.6025 13.9103 16.1965 13.9103C17.5758 13.9103 18.8162 13.3162 19.6761 12.3691C19.1341 16.3292 15.7408 19.25 11.6314 19.25C7.14512 19.25 3.5 15.6049 3.5 11.1186C3.5 7.0092 6.42079 3.61588 10.3809 3.07385C9.43381 3.93379 8.83972 5.17424 8.83972 6.55346C8.83972 9.14745 10.9422 11.2499 13.5362 11.2499C15.6142 11.2499 17.3813 9.92044 18 8.04555" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'logout'">
      <path d="M10 5.5H8C6.48122 5.5 5.25 6.73122 5.25 8.25V15.75C5.25 17.2688 6.48122 18.5 8 18.5H10" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M13.5 8.5L18 12L13.5 15.5" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M18 12H9.5" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'refresh'">
      <path d="M18.5 10.25C17.9235 7.08237 15.1556 4.75 11.75 4.75C8.82526 4.75 6.37307 6.4694 5.22058 8.95" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M5.25 5.75V9.5H9" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M5.5 13.75C6.07645 16.9176 8.84445 19.25 12.25 19.25C15.1747 19.25 17.6269 17.5306 18.7794 15.05" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M18.75 18.25V14.5H15" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'save'">
      <path d="M6.75 4.75H16.25L18.75 7.25V17.25C18.75 18.3546 17.8546 19.25 16.75 19.25H7.25C6.14543 19.25 5.25 18.3546 5.25 17.25V6.25C5.25 5.42157 5.92157 4.75 6.75 4.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M8.5 4.75V9.25H15.25V4.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M9 15.25H15" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'download'">
      <path d="M12 4.75V14" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M8.75 10.75L12 14L15.25 10.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M5.75 18.25H18.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'trash'">
      <path d="M5.75 7H18.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M9.25 4.75H14.75" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M8 7L8.75 18.25H15.25L16 7" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M10 10.25V15" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M14 10.25V15" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'plus'">
      <path d="M12 5.5V18.5" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
      <path d="M5.5 12H18.5" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'shield'">
      <path d="M12 4.75L18.25 7V11.75C18.25 15.599 15.7026 18.9813 12 20C8.29741 18.9813 5.75 15.599 5.75 11.75V7L12 4.75Z" :stroke-width="strokeWidth" stroke="currentColor" stroke-linejoin="round" />
      <path d="M9.5 12L11.25 13.75L14.75 10.25" :stroke-width="strokeWidth" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <template v-else>
      <circle cx="12" cy="12" r="7.25" :stroke-width="strokeWidth" stroke="currentColor" />
    </template>
  </svg>
</template>

<script setup>
defineProps({
  name: {
    type: String,
    required: true,
  },
  size: {
    type: Number,
    default: 18,
  },
  strokeWidth: {
    type: Number,
    default: 1.8,
  },
})
</script>

<style scoped>
svg {
  display: block;
  flex: none;
}
</style>
