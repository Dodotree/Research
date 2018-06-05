const authenticate = ({ types, mapActionToKey }) => {
  console.log('Logged user enter point')

  if (!Array.isArray(types) || types.length !== 6) {
    throw new Error('Expected types to be an array of three elements.')
  }
  if (!types.every(t => typeof t === 'string')) {
    throw new Error('Expected types to be strings.')
  }
  if (typeof mapActionToKey !== 'function') {
    throw new Error('Expected mapActionToKey to be a function.')
  }

  const [ requestType, successType, failureType, requestType2, successType2, failureType2 ] = types

  const updateLoggedUser = (state = {
    isFetching: false,
    loggedIn: false,
    id: null
  }, action) => {
    console.log('Authenticate update ', state)

    switch (action.type) {
      case 'LOGIN_REQUEST':
      case 'LOGOUT_REQUEST':
        return {
          ...state,
          isFetching: true
        }
      case 'LOGIN_SUCCESS':
      case 'LOGOUT_FAILURE':
        console.log('Authentication success, logout failure', action.response.result, {...state, id: action.response.result})
        return {
          ...state,
          isFetching: false,
          loggedIn: true,
          id: action.response.result
        }
      case 'LOGIN_FAILURE':
      case 'LOGOUT_SUCCESS':
        console.log('Authentication failure, logout success')
        return {
          ...state,
          isFetching: false,
          loggedIn: false,
          id: null
        }
      default:
        console.log('Authentication default')
        return state
    }
  }

  return (state = {
    isFetching: false, // initiated with those values
    loggedIn: false,
    id: null
  }, action) => {
    // Update pagination by key
    console.log('Authentication: action from prev. middleware', state, action, mapActionToKey(action), mapActionToKey)

    switch (action.type) {
      case requestType:
      case successType:
      case failureType:
      case requestType2:
      case successType2:
      case failureType2:
        const key = mapActionToKey(action)
        if (typeof key !== 'string') {
          throw new Error('Expected key to be a string.')
        }
        console.log('Authentication key: ' + key, state[key])
        return updateLoggedUser(state[key], action)
      default:
        return state
    }
  }
}

export default authenticate
