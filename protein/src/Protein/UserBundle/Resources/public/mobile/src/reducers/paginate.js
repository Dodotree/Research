import union from 'lodash/union'
import assign from 'lodash/assign'

// Creates a reducer managing pagination, given the action types to handle,
// and a function telling how to extract the key from an action.
const paginate = ({ types, mapActionToKey }) => {
  console.log('Pagination enter point')

  if (!Array.isArray(types) || types.length !== 3) {
    throw new Error('Expected types to be an array of three elements.')
  }
  if (!types.every(t => typeof t === 'string')) {
    throw new Error('Expected types to be strings.')
  }
  if (typeof mapActionToKey !== 'function') {
    throw new Error('Expected mapActionToKey to be a function.')
  }

  const [ requestType, successType, failureType ] = types

  console.log('define pagination reducer function(defaults and switch by action.type)')
  console.log('takes care of isFetching, nextPageUrl and pageCount, ids[]')
  const updatePagination = (state = {
    isFetching: false,
    ids: [],
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
  }, action) => {
    console.log('Pagination update')

    switch (action.type) {
      case requestType:
        return {
          ...state,
          isFetching: true
        }
      case successType:
        console.log('Pagination update SUCCESS', state, action.response.pagination)
        if (action.response && action.response.pagination) {
          return assign({}, state, action.response.pagination)
        }

        return {
          ...state,
          isFetching: false,
          ids: union(state.ids, action.response.result)
        }
      case failureType:
        return {
          ...state,
          isFetching: false
        }
      default:
        return state
    }
  }

  console.log('Return pagination reducer function')

  return (state = {}, action) => {
    // Update pagination by key
    console.log('Pagination: action from prev. middleware', state, action, mapActionToKey(action), mapActionToKey)

    switch (action.type) {
      case requestType:
      case successType:
      case failureType:
        const key = mapActionToKey(action)
        if (typeof key !== 'string') {
          throw new Error('Expected key to be a string.')
        }
        return updatePagination(state, action)
      default:
        return state
    }
  }
}

export default paginate
