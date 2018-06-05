<template>
  <Provider :mapDispatchToProps="mapDispatchToProps" :mapStateToProps="mapStateToProps" :store="store">
    <template slot-scope="{actions}"> <!-- We our state via slot-scope. Passing down the props to the component is no more hidden -->
      <div>

        <form @submit.prevent="actions.loginUser({login})" id="login-form" method="post" :actions="actions">
          <BaseInputText
            v-model="login.username"
            type="text"
            placeholder="Username"
          />
          <BaseInputText
            v-model="login.password"
            type="password"
            placeholder="Password"
          />
          <SubmitButton btnText="Login"/>
        </form>

      </div>
    </template>
  </Provider>
</template>

<script>

import { bindActionCreators } from 'redux'
import Provider from 'vuejs-redux'
import * as Actions from '../actions'

import BaseInputText from './BaseInputText.vue'
import SubmitButton from './SubmitButton.vue'

export default {
  inject: ['store'],
  props: ['name'],
  components: {
    Provider,
    BaseInputText,
    SubmitButton
  },
  data () {
    return {
      login: { 'username': '', 'password': '' }
    }
  },
  methods: {
    mapStateToProps (state) {
      console.log('############# StateToProps #############', state)
      this.$emit('stateChangeForRouter', state)
      return {}
    },
    mapDispatchToProps (dispatch) {
      console.log('############# DispatchToProps #############')
      return {actions: bindActionCreators(Actions, dispatch)}
    }
  }
}
</script>
