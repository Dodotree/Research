<template>
    <button class="btn" type="submit">{{btnText}}</button>
</template>

<script>
export default {
  props: {
    btnText: {
      type: String,
      required: true
    }
  }
}
</script>

<style lang="scss">
    button.btn{
        width: 100%;
        padding: 0.1rem 0.5rem;
        margin: 1.5rem 0 3rem 0;
        background-color: #fff;
        color: #000;
        font-family: 'Raleway', Helvetica, Arial, sans-serif;
        font-size: 1.7rem;
        font-weight: 700;
        border: 3px solid black;
        border-radius: 3rem;
        box-shadow: 2px 5px  1px #000;
    }
    button.btn:active,
    button.btn:hover{
        background-color: #F27839;
    }
</style>
