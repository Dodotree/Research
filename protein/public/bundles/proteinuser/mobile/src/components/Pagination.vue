<template>

  <nav class="pagination-menu">
    <router-link
      :to="{name: routeName, params: { subpageId: pgO.startPage, pageParameterName: pgO.pageParameterName } }">
      First
    </router-link>
    <router-link
      v-if="pgO.first !== pgO.current"
      :to="{name: routeName, params: { subpageId: pgO.previous, pageParameterName: pgO.pageParameterName } }">
      Back
    </router-link>
    <router-link
      v-for="subpageId in pgO.pagesInRange"
      :key="subpageId"
      :to="{name: routeName, params: { subpageId: subpageId, pageParameterName: pgO.pageParameterName } }">
      {{subpageId}}
    </router-link>
    <router-link
      v-if="pgO.last !== pgO.current"
      :to="{name: routeName, params: { subpageId: nextSubpage, pageParameterName: pgO.pageParameterName } }">
      Next
    </router-link>
    <router-link
      :to="{name: routeName, params: { subpageId: pgO.endPage, pageParameterName: pgO.pageParameterName } }">
      Last
    </router-link>
  </nav>

</template>

<script>
export default {
  props: {
    routeName: {
      type: String,
      required: true
    },
    pgO: {
      type: Object,
      required: true
    }
  }
}
</script>

<style lang="scss" scoped>

</style>
