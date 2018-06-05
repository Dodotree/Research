<template>
  <div id="app">
    <router-view @stateChangeForRouter="mapStateToRoute"></router-view>
    <Footer/>
  </div>
</template>

<script>

import Footer from './components/Footer.vue'

export default {
  inject: ['mapStateToRoute'],
  components: {
    Footer
  },
  created () {
    console.log('!!!!!!!!! created !!!!!!!!!!!!!!', this.mapStateToRoute)
  },
  beforeMount () {
    console.log('!!!!!!!!! beforeMount !!!!!!!!!!!!!!', this)
  }
}

</script>

<style lang="scss">
@import url('https://fonts.googleapis.com/css?family=Wendy+One');
@import url('https://fonts.googleapis.com/css?family=Raleway');

/*! CSS reset from benfrain/app-reset */
*{user-select:none;
-webkit-tap-highlight-color:rgba(255,255,255,0);
-webkit-tap-highlight-color:transparent}
[contenteditable],input[type]{user-select:text}
body,h1,h2,h3,h4,h5,h6,p{margin:0;font-size:1rem;font-weight:400}
a{text-decoration:none;color:inherit}
b{font-weight:400}
em,i{font-style:normal}
a:focus,input:focus{outline:0}
fieldset,input{appearance:none;border:0;padding:0;margin:0;min-width:0;font-size:1rem;font-family:inherit}
input::-ms-clear{display:none}
input[type=number]{-moz-appearance:textfield}
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button{appearance:none}
svg{display:inline-flex}img{max-width:100%;display:block}

*, *::before, *::after {
    box-sizing: border-box;
}

html{
     background: linear-gradient(#6C5E93 4%, #1D80AA 25%,  #1D80AA 55%, #6C5E93 90%, #B26CA0 99%);
}

html, body{
    padding: 0;
    margin: 0;
    min-height: 100%;
}
h1, h3 {
    margin: 0;
    font-family: 'Wendy One', sans-serif;
    text-align: center;
    color: #FAAF4A;
    text-shadow: 2px 5px  1px #D34261;
    font-size: 5rem;
    line-height: 4rem;
    font-weight: 400;
}
h1{
    margin-top: 2rem;
}
h3{
    margin-bottom: 2.5rem;
    font-size: 1.3rem;
    color: #fff;
    text-shadow: none;
}

</style>
