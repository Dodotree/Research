import * as ActionTypes from '../actions'
import merge from 'lodash/merge'
import assign from 'lodash/assign'
import paginate from './paginate'
import authenticate from './loggedUser'
import { combineReducers } from 'redux'

const counter = (state = 0, action) => {
  switch (action.type) {
    case 'INCREMENT':
      return state + 1
    case 'DECREMENT':
      return state - 1
    case 'RESET':
      return 0
    default:
      return state
  }
}

// Updates an entity cache in response to any action with response.entities.
const entities = (state = { athletes: {}, users: {} }, action) => {
  if (action.response && action.response.entities) {
    return merge({}, state, action.response.entities)
  }
  return state
}

// Updates state "selected" in response that resets selection.
const selections = (state = { athletes: {}, users: {} }, action) => {
  if (action.response && action.response.selections) {
    return assign({}, state, action.response.selections)
  }
  switch (action.type) {
    case 'SET_SELECTED':
      return assign({}, state, {[action.key]: {ids: action.ids}})
    case 'ADD_SELECTED':
      return merge({}, state, {[action.key]: {ids: [action.id]}})
    case 'REMOVE_SELECTED':
      let newState = merge({}, state)
      newState[action.key].ids = state[action.key].ids.filter((item, index) => item !== action.data)
      return newState
    default:
      return state
  }
}

// Updates error message to notify about the failed fetches.
const errorMessage = (state = null, action) => {
  const { type, error } = action

  if (type === ActionTypes.RESET_ERROR_MESSAGE) {
    return null
  } else if (error) {
    return error
  }

  return state
}

// Updates the login data for different actions.
const loggedUser = authenticate({
  mapActionToKey: (action) => 'loggedUser',
  types: [
    ActionTypes.LOGIN_REQUEST,
    ActionTypes.LOGIN_SUCCESS,
    ActionTypes.LOGIN_FAILURE,
    ActionTypes.LOGOUT_REQUEST,
    ActionTypes.LOGOUT_SUCCESS,
    ActionTypes.LOGOUT_FAILURE
  ]
})

// Updates the pagination data for different actions.
const pagination = paginate({
  mapActionToKey: (action) => 'athletes', // action.login
  types: [
    ActionTypes.ATHLETE_REQUEST,
    ActionTypes.ATHLETE_SUCCESS,
    ActionTypes.ATHLETE_FAILURE
  ]
})

// All together now
const rootReducer = combineReducers({
  counter,
  entities,
  selections,
  loggedUser, // entities before logged in and pagination
  pagination,
  errorMessage
})

export default rootReducer
