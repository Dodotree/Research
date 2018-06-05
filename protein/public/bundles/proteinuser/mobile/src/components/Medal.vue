<template>
  <li class="medal">
    <span
      :class="'symbol symbol-' + medal.type"
      :title="medalTypes[medal.type]"
    >
      {{medal.type}}
    </span>
    <span class="year">{{medal.year}}</span>
    <span class="city"> {{medal.city}}</span>
    <span class="event"> ({{medal.event}})</span>
    <span class="category"> {{medal.category}}</span>
  </li>
</template>

<script>

export default {
  props: {
    medal: {
      type: Object,
      required: true
    }
  },
  data () {
    return {
      medalTypes: {
        G: 'Gold',
        S: 'Silver',
        B: 'Bronze'
      }
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
