import Vue from 'vue'
import router from './router'
import App from './App'

import configureStore from './store/configureStore'
import { normalizeResponse } from './middleware/api'

let initialState = JSON.parse(window.__PRELOADED_STATE__)
// Allow the passed state to be garbage-collected
delete window.__PRELOADED_STATE__

let nInitialState = normalizeResponse(initialState)
let loggedIn = ('undefined' !== typeof nInitialState.selections.loggedUser)

let preloadedState = {
  'entities': nInitialState.entities,
  'selections': nInitialState.selections,
  'loggedUser': {
    'id': (loggedIn) ? nInitialState.selections.loggedUser : undefined,
    'loggedIn': loggedIn,
    'isFetching': false
  },
  'pagination': {
    'athletes': {
      isFetching: false,
      ids: ('undefined' !== typeof nInitialState.pagination.athletes) ? nInitialState.pagination.athletes.ids : [],
      current: 1,
      first: 1,
      last: 1,
      startPage: 1,
      endPage: 1,
      next: 1,
      previous: 1,
      pagesInRange: [1],
      numItemsPerPage: 1,
      pageParameterName: 'Page'
    }
  },
  'counterValue': 0
}

let store = configureStore(preloadedState)

let mapStateToRoute = (state) => {
  let storeState = store.getState()
  console.log(':::::::::::::', storeState.entities, storeState.selections)
  console.log('**************** routing state **************', router.history.current, state.loggedUser, state.loggedUser.loggedIn)
  if (router.history.current.meta.requireLogout && state.loggedUser.loggedIn) {
    router.push({name: 'private', subpageId: 1})
  } else if (router.history.current.meta.requireLogin && !state.loggedUser.loggedIn) {
    router.push({name: 'LoginForm'})
  }
}

Vue.config.productionTip = false

router.beforeEach((to, from, next) => {
  console.log('!!!!!!!!!!!!!!!!! before route !!!', to, from)
  next()
})

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  template: '<App/>',
  provide: { store: configureStore(preloadedState), mapStateToRoute: mapStateToRoute },
  components: { App }
})
