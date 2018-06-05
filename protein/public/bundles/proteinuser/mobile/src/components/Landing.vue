<template>
  <div id="landing-page">
    <h1>Bitcoin Robo!</h1>
    <h3>Real time AI trading</h3>
    <router-view @stateChangeForRouter="mapStateToRoute"></router-view>
    <p>
    <router-link :to="{name: 'LoginForm', params: { something: 'getIt' } }">Login</router-link>
    <router-link :to="{name: 'RegisterForm'}">Register</router-link>
    <router-link :to="{name: 'ResetPass'}">Reset</router-link>
    </p>
  </div>
</template>

<script>

export default {
  inject: ['mapStateToRoute'],
  created () {
    console.log('Landing created')
  },
  beforeMount () {
    console.log('Landing beforeMount', this)
  }
}

</script>

<style lang="scss" scoped>
@import '../variables.scss';

#landing-page {
    max-width: 400px;
    margin: 0 auto;
    line-height: 1.4;
    font-family: 'Raleway', Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    color: $vue-blue;
}

</style>
