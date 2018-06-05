<template>
  <input
    class="input"
    :value="value"
    v-on="listeners"
  >
</template>

<script>
export default {
  props: {
    value: {
      type: String,
      default: ''
    }
  },
  computed: {
    listeners () {
      return {
        // Pass all component listeners directly to input
        ...this.$listeners,
        // Override input listener to work with v-model
        input: event => this.$emit('input', event.target.value)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
@import '../variables.scss';

.input {
  font-family: 'Raleway', Helvetica, Arial, sans-serif;
  width: 100%;
  padding: 8px 10px;
  margin: 1rem 0;
  border: none;
  border-bottom: 3px solid #FAAF4A;
  box-shadow: 0 3px  1px #D34261;
  background: transparent;

  font-size: 1.3rem;
  color: #fff;
}
</style>
