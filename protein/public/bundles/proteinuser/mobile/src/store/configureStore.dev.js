import { createStore, applyMiddleware, compose } from 'redux'
import thunk from 'redux-thunk'
import { createLogger } from 'redux-logger'
import api from '../middleware/api'
import rootReducer from '../reducers'

const configureStore = preloadedState => {

console.log('preloadedState', preloadedState);

  const store = createStore(
    rootReducer,
    preloadedState,
    applyMiddleware(thunk, api, createLogger()),
  )

  return store
}

export default configureStore
