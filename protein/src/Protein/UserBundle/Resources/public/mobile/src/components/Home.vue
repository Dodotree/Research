<template>
  <Provider :mapDispatchToProps="mapDispatchToProps" :mapStateToProps="mapStateToProps" :store="store">
    <template slot-scope="{actions}">

      <div id="layout">
        <div id="global-header">
          <h1>Bitcoin Robo!</h1>
          <div class="pull-right">
            <router-link :to="{name: 'SettingsForm', params: { something: 'getIt' } }">Settings</router-link>
            <a href="#" @click.prevent="actions.logoutUser()" :actions="actions">Logout</a>
            <a href="#" @click.prevent="actions.loadAthletes()" :actions="actions">Reload</a>
          </div>
        </div>
        <div class="container">
          <router-view @stateChangeForRouter="mapStateToRoute"></router-view>
        </div>
      </div>

    </template>
  </Provider>
</template>

<script>
import { bindActionCreators } from 'redux'
import Provider from 'vuejs-redux'
import * as Actions from '../actions'

export default {
  inject: ['store', 'mapStateToRoute'],
  components: { Provider },
  created () {
    console.log('Home created')
  },
  beforeMount () {
    console.log('Home beforeMount', this)
  },
  methods: {
    mapStateToProps (state) {
      console.log('############# Home: StateToProps #############', state)
      this.$emit('stateChangeForRouter', state)
      return {counterValue: state.counter}
    },
    mapDispatchToProps (dispatch) {
      console.log('############# Home: DispatchToProps #############')
      return {actions: bindActionCreators(Actions, dispatch)}
    }
  }
}

</script>

<style lang="scss" scoped>
@import '../variables.scss';

#layout {
    width: 100%;
    margin: 0;

    line-height: 1.4;
    font-family: 'Raleway', Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    color: $vue-blue;
}
.pull-right{
    float: right;
    display: inline-block;
}
#global-header{
    text-align: left;
    h1{
      display: inline-block;
      margin: 1rem;
      font-size: 3rem;
    }
    a, a:hover, a:active{
      color: #fff;
    }
}
.container{
    padding: 2rem;
    background-color: #fff;
}

</style>
