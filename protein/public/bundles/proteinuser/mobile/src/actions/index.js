import { CALL_API } from '../middleware/api'
import { Schemas } from '../schemas'

export const RESET_ERROR_MESSAGE = 'RESET_ERROR_MESSAGE'

export const LOGIN_REQUEST = 'LOGIN_REQUEST'
export const LOGIN_SUCCESS = 'LOGIN_SUCCESS'
export const LOGIN_FAILURE = 'LOGIN_FAILURE'

export const LOGOUT_REQUEST = 'LOGOUT_REQUEST'
export const LOGOUT_SUCCESS = 'LOGOUT_SUCCESS'
export const LOGOUT_FAILURE = 'LOGOUT_FAILURE'

export const ATHLETE_REQUEST = 'ATHLETE_REQUEST'
export const ATHLETE_SUCCESS = 'ATHLETE_SUCCESS'
export const ATHLETE_FAILURE = 'ATHLETE_FAILURE'

// Resets the currently visible error message.
export const resetErrorMessage = () => ({
  key: '',
  type: RESET_ERROR_MESSAGE
})

// Login ////////////////////////////////////////////////
const fetchLogin = login => ({
  key: 'user',
  login: login,
  [CALL_API]: {
    types: [ LOGIN_REQUEST, LOGIN_SUCCESS, LOGIN_FAILURE ],
    endpoint: '/api_login',
    data: login,
    schema: Schemas.LOGGED_USER
  }
})

export const loginUser = (login) => (dispatch, getState) => {
  console.log('fetching login geez', login)
  return dispatch(fetchLogin(login.login))
}

const fetchLogout = () => ({
  key: 'user',
  [CALL_API]: {
    types: [ LOGOUT_REQUEST, LOGOUT_SUCCESS, LOGOUT_FAILURE ],
    endpoint: '/logout',
    data: {},
    schema: Schemas.LOGGED_USER
  }
})

export const logoutUser = () => (dispatch, getState) => {
  console.log('requesting logout')
  return dispatch(fetchLogout())
}

// Athletes ////////////////////////////////////////////////
// Fetches athletes page from API.
// Relies on the custom API middleware defined in ../middleware/api.js.
const fetchAthletes = () => ({
  key: 'athletes',
  [CALL_API]: {
    types: [ ATHLETE_REQUEST, ATHLETE_SUCCESS, ATHLETE_FAILURE ],
    endpoint: '/api',
    data: '',
    schema: Schemas.ATHLETE_ARRAY
  }
})

// Fetches users from API unless they don't need an update.
// Relies on Redux Thunk middleware.
// via bindActionCreator
export const loadAthletes = () => (dispatch, getState) => {
  // const user = getState().entities.users[login]
  // if (user && requiredFields.every(key => user.hasOwnProperty(key))) {
  //  return null
  // }
  console.log('fetching athletes')
  return dispatch(fetchAthletes())
}

/// //////////////////////////// displayed ids action creators ///////////////////
export function removeSelected (key, id) {
  return { 'type': 'REMOVE_SELECTED', 'key': key, 'data': id }
}

export function addSelected (key, id) {
  return { 'type': 'ADD_SELECTED', 'key': key, 'data': id }
}

export function setSelected (key, ids) {
  return { 'type': 'SET_SELECTED', 'key': key, 'data': ids }
}

/// //////////////////////////// counter action creators ///////////////////
export function increment () {
  return { 'type': 'INCREMENT' }
}

export function decrement () {
  return { 'type': 'DECREMENT' }
}

export function reset () {
  return { 'type': 'RESET' }
}
