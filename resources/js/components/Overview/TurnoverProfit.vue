<template>
  <div class="flex-1 card p-0 overflow-hidden h-full">
    <template x-if="current_month && !loading">
    <div class=" p-2 pb-1">
      <div class="flex flex-col">
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-bold text-grey">Profit & Turnover</h3>

        </div>
        <date-fieldtype
            class="mb-3"
            name="date"
            :config="{'mode': 'range', 'inline': false}"
            @input="dateUpdated"
            :value="range"></date-fieldtype>
        <p class="text-sm">Based on <span v-text="order_count"></span> orders
        </p>
      </div>
    </div>
    <div class="flex flex-col  mt-2 items-center justify-center p-2 mb-2">
      <div class="text-4xl leading-tight font-light text-green" v-text="profit"></div>
      <p class="text-grey text-sm" v-text="turnover"></p>
    </div>
    </template>
  </div>
</template>

<script>
export default {
  props: ['data'],
  data: function() {

    return {
      range: {
        start: Vue.moment().startOf('month').toDate(),
        end: Vue.moment().toDate(),
      },
      turnover: 0,
      profit: 0,
      loading: true,
      order_count: 0,
    };
  },

  mounted() {

    this.dateUpdated(this.range)

  },

  methods: {
    dateUpdated: function(range){

      this.loading = true;
      this.$axios.post(this.data['fetch_route'], {
        range: range
      }).then(response => {
        this.order_count = response.data.order_count;
        this.turnover = response.data.turnover;
        this.profit = response.data.profit;
        this.loading = false;
      });

    }
  }
};
</script>
