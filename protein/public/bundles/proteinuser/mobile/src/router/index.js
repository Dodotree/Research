import Vue from 'vue'
import Router from 'vue-router'
import Landing from '@/components/Landing'
import Home from '@/components/Home'
import LoginForm from '@/components/LoginForm'
import RegisterForm from '@/components/RegisterForm'
import PrivatePage from '@/components/PrivatePage'
import AthletePage from '@/components/AthletePage'

Vue.use(Router)

function routeProps (route) {
  const now = new Date()
  return {
    name: now.getFullYear() + '!'
  }
}

export default new Router({
  mode: 'history',
  base: __dirname,
  inject: ['store'],
  routes: [
    {
      path: '/landing',
      name: 'Landing',
      component: Landing,
      props: routeProps,
      children: [
        {
          path: 'login',
          name: 'LoginForm',
          component: LoginForm,
          meta: { requireLogin: false, requireLogout: true },
          props: routeProps
        },
        {
          path: 'register',
          name: 'RegisterForm',
          component: RegisterForm,
          meta: { requireLogin: false, requireLogout: true }
        },
        {
          path: 'reset',
          name: 'ResetPass',
          meta: { requireLogin: false, requireLogout: false }
        },
        {
          path: 'about',
          name: 'About',
          meta: { requireLogin: false, requireLogout: false }
        },
        {
          path: 'legal',
          name: 'Legal',
          meta: { requireLogin: false, requireLogout: false }
        },
        {
          path: 'copyright',
          name: 'Copyright',
          meta: { requireLogin: false, requireLogout: false }
        }
      ]
    },
    {
      path: '/home',
      name: 'home',
      component: Home,
      props: routeProps,
      children: [
        {
          path: ':subpageId',
          name: 'private',
          component: PrivatePage,
          meta: { requireLogin: true, requireLogout: false },
          props: {name:'Routing param', subpageId: this.subpageId}
        },
        {
          path: ':subpageId/athlete/:id',
          name: 'AthletePage',
          component: AthletePage,
          meta: { requireLogin: true, requireLogout: false },
          props: true
        },
        {
          path: 'settings',
          name: 'SettingsForm',
          meta: { requireLogin: true, requireLogout: false }
        }
      ]
    }
  ]
})
