<template>
  <div class="flex-1 card p-0 overflow-hidden h-full">
    <div class="flex justify-between items-center p-2 pb-1">
      <div class="flex flex-col">
        <h2>
          <span>{{ __("Profit & Turnover") }}</span>
        </h2>
        <p class="text-sm">Based on <span v-text="data.order_count"></span> orders in <span v-text="data.current_month">.</span></p>
      </div>
    </div>
    <div class="flex flex-col  mt-2 items-center justify-center p-2 mb-2">
      <div class="text-4xl leading-tight font-light text-green" v-text="data.profit"></div>
      <p class="text-grey text-sm" v-text="data.turnover"></p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    data: Array,
  },
};
</script>
