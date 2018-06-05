<template>
  <Provider :mapDispatchToProps="mapDispatchToProps" :mapStateToProps="mapStateToProps" :store="store">
    <template slot-scope="{actions}"> <!-- We our state via slot-scope. Passing down the props to the component is no more hidden -->
      <div>
        <form @submit.prevent="submitRegisterForm" name="user" method="post" id="register-form">
          <BaseInputText
            v-model="user.username"
            type="text"
            placeholder="Username"
          />
          <BaseInputText
            v-model="user.email"
            type="email"
            placeholder="Email"
          />
          <BaseInputText
            v-model="user.plainPassword.first"
            type="password"
            placeholder="Password"
          />
          <BaseInputText
            v-model="user.plainPassword.second"
            type="password"
            placeholder="Repeat Password"
          />
          <SubmitButton btnText="Register"/>
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

import { apiRegister } from '../middleware/api'

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
      user: {
        username: '',
        email: '',
        plainPassword: {
          first: '',
          second: ''
        }
      }
    }
  },
  methods: {
    mapStateToProps (state) {
      this.$emit('stateChangeForRouter', state)
      return {}
    },
    mapDispatchToProps (dispatch) {
      return {actions: bindActionCreators(Actions, dispatch)}
    },
    submitRegisterForm () {
      console.log('submit register')
      apiRegister(this.user)
    }
  }
}
</script>
