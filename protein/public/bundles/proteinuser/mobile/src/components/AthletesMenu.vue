<template>
  <nav class="atheletes-menu">
    <router-link
      v-for="athlete in athletes"
      :key="athlete.id"
      :to="{name: 'AthletePage', params: { id: athlete.id, subpageId: subpageId } }">
      {{shortName(athlete.name)}}
    </router-link>
  </nav>

</template>

<script>
export default {
  props: {
    athletes: {
      type: Object,
      required: true
    },
    subpageId: {
      type: Number,
      required: true
    }
  },
  methods: {
    shortName (fullName) {
      const [name, surname] = fullName.split(' ')
      return `${name[0]}. ${surname}`
    }
  }
}
</script>

<style lang="scss" scoped>

nav.atheletes-menu {
  margin: 0 auto;
  max-width: 800px;
  padding: 2em;
  text-align: center;
  width: 100%;

  a {
    font-size: 1.2em;
    margin: 0 1em 1em 0;
    &.router-link-active {
      color: #030303;
      text-decoration: none;
    }
  }
}

</style>
