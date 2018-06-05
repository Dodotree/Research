<template>
  <span class="flag">
    <img class="icon" :title="name" :src="baseURL + icon" :alt="name + '\'s flag'" />
    <span v-if="showName" className="name">{{name}}</span>
  </span>
</template>

<script>
export default {
  props: {
    baseURL: {
      type: String,
      required: true
    },
    name: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      required: true
    },
    showName: {
      type: Boolean,
      required: true
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
