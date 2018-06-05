<template>
  <footer>
    <router-link :to="{name: 'About'}">About</router-link>
    <router-link :to="{name: 'Legal'}">Legal</router-link>
    <router-link :to="{name: 'Copyright'}">&copy; 2018</router-link>
    Created <small>by</small> PaulT - Design <small>by</small> his Cats
  </footer>
</template>

<script>
</script>

<style lang="scss">
    footer{
        position: fixed;
        right: 1rem;
        bottom: 0.1rem;
        text-align: center;
        font-family: 'Wendy One', sans-serif;
        font-size: 1.3rem;
        font-weight: 100;
        color: #6C5E93;
    }
</style>
