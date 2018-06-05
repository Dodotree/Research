<template>
  <Provider :mapDispatchToProps="mapDispatchToProps" :mapStateToProps="mapStateToProps" :store="store">
    <template slot-scope="{counterValue, actions, todos, todo_ids, paginationObject}">
    <!-- We our state via slot-scope. Passing down the props to the component is no more hidden -->
      <div>

        <p>{{name}}</p>

        <ul v-if="todo_ids.length" class="athletes-selector">
          <TodoListItem
            v-for="id in todo_ids"
            :key="id"
            :todo="todos[id]"
            baseURL='/images/'
            @remove="actions.removeSelected"
          />
        </ul>

        <p v-else>
          Nothing left in the list. Add a new todo in the input above.
        </p>

        <Pagination v-if="paginationObject.pagesInRange.length > 1"
            :pgO="paginationObject" routeName="private" />

        <Counter :counterValue="counterValue" :actions="actions" :title="title" />

      </div>
    </template>
  </Provider>
</template>

<script>

import { bindActionCreators } from 'redux'
import Provider from 'vuejs-redux'
import * as Actions from '../actions'
import Counter from '../components/Counter.vue'

import TodoListItem from './TodoListItem.vue'
import Pagination from './Pagination.vue'

let nextTodoId = 1

export default {
  inject: ['store'],
  props: ['name'],
  components: {
    Counter,
    Provider,
    Pagination,
    TodoListItem
  },
  data () {
    return {
      title: 'Counter',
      newAthleteName: '',
      todos: {},
      todo_ids: [],
      paginationObject: {}
    }
  },
  created () {
  },
  methods: {
    mapStateToProps (state) {
      console.log('############# Private: StateToProps #############', state.pagination.athletes.pagesInRange)
      this.$emit('stateChangeForRouter', state)
      return {
        counterValue: state.counter,
        todos: state.entities.athletes,
        todo_ids: ('undefined' !== typeof state.selections.athletes) ? state.selections.athletes.ids : [],
        paginationObject: state.pagination.athletes
      }
    },
    mapDispatchToProps (dispatch) {
      console.log('############# Private: DispatchToProps #############')
      return {actions: bindActionCreators(Actions, dispatch)}
    },
    addTodo () {
      const trimmedText = this.newAthleteName.trim()
      if (trimmedText) {
        this.todos.push({
          id: nextTodoId++,
          name: trimmedText
        })
        this.newAthleteName = ''
      }
    }
  }
}
</script>

<style lang="scss" scoped>
    ul {
        list-style: none;
    }
    .athletes-selector {
      clear: both;
      padding: 2em;
      text-align: center;
    }

</style>
