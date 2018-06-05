import axios from 'axios'

import merge from 'lodash/merge'
import { normalize } from 'normalizr'
import fetch from 'cross-fetch'
import { Schemas } from '../schemas'

export { apiLogin, apiRegister, apiReset }

const API_ROOT = ''
/* Action key */
export const CALL_API = 'Call API'

function apiLogin (params) {
  return login(params)
    .then(response => {
      if (200 === response.status && 'undefined' !== typeof response.data.successes) {
        return {'status': 'ok', 'user': response.data.successes}
      } else if (401 === response.status) {
        return {'status': 'err', 'text': response.data.error}
      } else {
        return {'status': 'err', 'text': 'unknown error status:' + response.status}
      }
    })
}

function login (params) {
  let url = 'api_login'
  console.log('before promice params', params)
  return axios.post(url, params)
    .then(
      response => response,
      error => error.response // { console.log(error.response.data.error, error.response.status) }
    )
    .catch(e => {
      error => error.response
    })
}

function apiRegister (params) {
  let url = 'register'
  return axios.post(
    url,
    toFormData(params, 'user'),
    {headers: { 'Content-Type': ['application/x-www-form-urlencoded'] }}
  )
    .then(
      response => response,
      error => error.response // { console.log(error.response.data.error, error.response.status) }
    )
    .catch(e => {
      error => error.response
    })
}

function apiReset () {
  let url = 'reset'
  return axios.post(url).then(response => response.data)
}

export function requireAuth (to, from, next) {
  if (!isLoggedIn()) {
    next({
      path: '/',
      query: { redirect: to.fullPath }
    })
  } else {
    next()
  }
}

export function isLoggedIn () {
  //  const idToken = getIdToken();
  //  return !!idToken && !isTokenExpired(idToken);
}


/// ///////////////////////////////////////////////
/// ////////// api to state helper
/// ///////////////////////////////////////////////
let apiKeyToSchema = {
  'loggedUser': {'schema': Schemas.LOGGED_USER, 'selected': true},
  'user': {'schema': Schemas.USER, 'selected': false},
  'users': {'schema': Schemas.USER_ARRAY, 'selected': true},
  'athlete': {'schema': Schemas.ATHLETE, 'selected': false},
  'athletes': {'schema': Schemas.ATHLETE_ARRAY, 'selected': true}
}

export const normalizeResponse = (response) => {
  let nEntities = {}
  let nPagination = {}
  let nSelection = {}
  let resp_pagination = ('undefined' !== typeof response.pagination) ? response.pagination : {}

  for (var key in apiKeyToSchema) {
    if ('undefined' !== typeof response[key]) {
      var normData = normalize(response[key], apiKeyToSchema[key].schema)
      nEntities = merge({}, nEntities, normData.entities)
      if (apiKeyToSchema[key].selected && 'object' === typeof normData.result) {
        if('undefined' !== typeof resp_pagination[key]){
          nPagination[key] = resp_pagination[key]
          nSelection[key] = { ids: resp_pagination[key].ids.slice() }
        }else{
          nPagination[key] = { ids:  normData.result }
          nSelection[key] = { ids: normData.result.slice() }
        }
        console.log('%%%%%%%%%%%%%%%%%%', nSelection[key].ids)
        console.log('++++++++++++++++++', normData.result)
      } else if (apiKeyToSchema[key].selected) {
        nPagination[key] = normData.result
        nSelection[key] = normData.result
      }
    }
  }
  return { 'entities': nEntities, 'pagination': nPagination, 'selections': nSelection }
}

/// ///////////////////////////////////////////////
/// ////////// Redux Thunk middleware
/// ///////////////////////////////////////////////
const getNextPageUrl = response => {
  //const pagination = response.headers.get('pagination')
  //console.log('API func getNextPageUrl', pagination)
  const url = ''
  if (!pagination) { return null }
  if (!url) { return null }
  return url
}

// Actual ajax call that returnes promise and resolves it
// then  normalizes the result JSON according to schema.
// This makes every API response have the same shape, regardless of how nested it was.
const callApi = (endpoint, schema, data) => {
  const fullUrl = (endpoint.indexOf(API_ROOT) === -1) ? API_ROOT + endpoint : endpoint
  //  returns middleware function callApi
  console.log('API Return AJAX call function')

  return axios.post(fullUrl, data)
    .then(
      response => {
        console.log('response', response) // response.data.successes
        if (response.statusText !== 'OK') { return Promise.reject(response) }
        console.log('status OK') // response.data.successes
        if (!response.data) { return response }
        console.log('response data exists', response.data) // response.data.successes
        if (response.data.successes) {
          const normalizedData = normalizeResponse(response.data.successes)
          // next page url for pagination if any
          const nextPageUrl = ''// getNextPageUrl(response)
          console.log('successes', nextPageUrl, normalizedData) // response.data.successes
          let ret = Object.assign({}, normalizedData, { nextPageUrl })
          console.log('return >>> ', ret)
          return ret
        }
        if (response.data.errors) {
          return Promise.reject(response.data.errors)
        }
        if (response.data.warnings) {
        }
        return response
      },
      error => {
        if (error.response.status === 401) { // login failure
          return Promise.reject(error.response.data.error)
        }
        return Promise.reject(error.response)
      }
    )
    .catch(e => {
      error => error.response
    })
  /* return fetch(fullUrl, {
      method: 'POST', // or 'PUT'
      body: JSON.stringify(data),
      headers: new Headers({
        'Content-Type': 'application/json'
      })
    })
    .then(response => {
      return response.json().then(json => {
        console.log('API AJAX promise and json actually resolved')
        if (!response.ok) { return Promise.reject(json) }
        // next page url for pagination if any
        const nextPageUrl = getNextPageUrl(response)
        // creating new (partial)state object with normalized result + pagination
        // it's returned to the reducer, reducer will change state to a new one
        // by merging this new partial one from response with current state
        let ret = Object.assign({}, normalize(json, schema), { nextPageUrl })
        return ret
      })
    }) */
}

// A Redux middleware that interprets actions with CALL_API info specified.
// Performs the call and promises when such actions are dispatched.
export default store => next => action => {
  console.log('API enter point')
  const callAPI = action[CALL_API]

  // Skips API middleware if action doesn't have valid RSAA object
  if (typeof callAPI === 'undefined') { return next(action) }

  // action[CALL_API] should have "types", "endpint" and "schema"
  // check/resolve all of them
  let { endpoint } = callAPI
  // resolve if it's a function to find string url
  if (typeof endpoint === 'function') {
    endpoint = endpoint(store.getState())
  }
  if (typeof endpoint !== 'string') { throw new Error('Specify a string endpoint URL.') }

  const { schema, types, data } = callAPI
  if (!schema) { throw new Error('Specify one of the exported Schemas.') }
  if (!Array.isArray(types) || types.length !== 3) { throw new Error('Expected an array of three action types.') }
  if (!types.every(type => typeof type === 'string')) { throw new Error('Expected action types to be strings.') }

  const [ requestType, successType, failureType ] = types

  // create actionModifier function for removing our used params and feeding the rest to the next middleware (reducer)
  console.log('API create actionModifier to form action for next() middleware')
  const actionWith = data => {
    console.log('API next(actionWith()) called')
    const finalAction = Object.assign({}, action, data)
    // remove our Symbol [CALL_API] as we don't need API call anymore
    delete finalAction[CALL_API] // the rest is action for the next middleware reducer
    return finalAction
  }
  // resolve syncronous next() first to take care of isFetching etc.
  console.log('API sync next() call')
  next(actionWith({ type: requestType }))

  // return fetching function, as original caller function will wait for it to finish before execution
  console.log('API Return async api call function: (promise) with .then(Success next(), Fail next()) attached')
  return callApi(endpoint, schema, data)
    .then(
      response => next(actionWith({
        response,
        type: successType
      })),
      error => next(actionWith({
        type: failureType,
        error: error.message || 'Something bad happened'
      }))
    )
}

/// ///////////////////////////////////////////////
/// ////////// end of Redux Thunk middleware
/// ///////////////////////////////////////////////

/// ///////// vue forms provide object but not a serialized params string /////////
/// ///////// json string will not get into $_GET nor $_POST recognizaple by server
/// ///////// toFormData works around that problem
function toFormData (tree, namespace) {
  let flats = []
  toFormDataIterator(tree, namespace, flats)
  return flats.join('&')
}

function toFormDataIterator (tree, namespace, flats) {
  for (let property in tree) {
    let val = tree[property]
    val = (val instanceof Date) ? val.toISOString() : val
    let formKey = (namespace) ? namespace + '[' + property + ']' : property

    if (typeof val === 'object' && !(val instanceof File)) {
      toFormDataIterator(val, formKey, flats)
    } else {
      formKey = cleanKey(formKey)
      flats.push(formKey + '=' + val)
    }
  }
}

function cleanKey (str) {
  var replace = {
    '!': '%21',
    "'": '%27',
    '(': '%28',
    ')': '%29',
    '~': '%7E',
    '%20': '+',
    '%00': '\x00'
  }
  return encodeURIComponent(str).replace(/[!'\(\)~]|%20|%00/g, function (match) {
    return replace[match]
  })
}
/// ////////// end of toFormData /////////////////////
