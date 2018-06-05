<template>
  <router-link :to="{name: 'AthletePage', params: { id: todo.id } }">
    <li class="athlete-preview">
      <img :src="baseURL + todo.image" :alt="todo.name + '\'s profile'" />
      <h2 class="name">{{todo.name}}</h2>
      <span class="medals-count">
        <img :src="baseURL + 'medal.png'" alt="Medal icon" /> {{todo.medals.length}}
      </span>
      <button @click.prevent.stop="$emit('remove', 'athletes', todo.id)"> X </button>
    </li>
  </router-link>
</template>

<script>
export default {
  props: {
    baseURL: {
      type: String,
      required: true
    },
    todo: {
      type: Object,
      required: true
    }
  }
}
</script>

<style lang="scss" scoped>
    .athlete-preview {
      border: 1px solid #ccc;
      box-shadow: 0 2px 5px 0 rgba(0,0,0,0.16),0 2px 10px 0 rgba(0,0,0,0.12);
      display: inline-block;
      margin: 0 1em 1em 0;
      max-width: 200px;
      padding: 0;
      position: relative;
      transition: all .2s ease-in-out;
      width: 100%;

      &:hover {
        transform: scale(1.1);
      }

      img {
        margin: 0;
        width: 100%;
      }

      .name {
        color: #030303;
        display: inline-block;
        font-size: 1.6em;
        overflow: hidden;
        padding: .2em;
        text-align: center;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
        width: 100%;
      }

      .medals-count {
        background: #fff;
        border-radius: 2px;
        display: inline-block;
        font-weight: bold;
        margin: .2em;
        padding: .2em .4em;
        position: absolute;
        right: 0;
        text-align: center;
        top: 0;
        vertical-align: middle;
      }
    }

</style>
