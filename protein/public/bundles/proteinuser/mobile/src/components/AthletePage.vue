<template>
  <Provider :mapDispatchToProps="mapDispatchToProps" :mapStateToProps="mapStateToProps" :store="store">
    <template slot-scope="{counterValue, actions, athletes, athlete, country, medals}">
      <div>

        <div class="athlete-full">
          <AthletesMenu :athletes="athletes" :subpageId="subpageId"/>
          <div class="athlete">
            <header :style="'backgroundImage: url(' + baseURL + athlete.cover + ')'"/>
            <div class="picture-container">
              <img :src="baseURL + athlete.image" :alt="athlete.name + '\'s profile'"/>
              <h2 class="name">{{athlete.name}}</h2>
            </div>
            <section class="description">
              Olympic medalist from &nbsp;
              <strong>
                <Flag
                  :name="country.name"
                  :icon="country.icon"
                  baseURL='/images/'
                  showName="true"
                />
              </strong>,
              born in {{athlete.birth}}
              (Find out more on <a :href="athlete.link">Wikipedia</a>).
            </section>
            <section class="medals">
              <p>Winner of <strong>{{athlete.medals.length}}</strong> medals:</p>
              <ul>
                <Medal
                  v-for="medal in medals"
                  :key="medal.id"
                  :medal="medal"
                />
              </ul>
            </section>
          </div>
          <div class="navigateBack">
            <router-link :to="{name: 'private'}">« Back to the index</router-link>
          </div>
        </div>

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

import AthletesMenu from '../components/AthletesMenu.vue'
import Medal from '../components/Medal.vue'
import Flag from '../components/Flag.vue'

export default {
  inject: ['store'],
  props: ['subpageId', 'id'],
  components: {
    Counter,
    AthletesMenu,
    Flag,
    Medal,
    Provider
  },
  data () {
    return {
      title: 'Counter',
      baseURL: '/images/',
      athletes: {},
      athlete: {}
    }
  },
  created () {
  },
  methods: {
    mapStateToProps (state) {
      console.log('############# Private: StateToProps #############', state)
      this.$emit('stateChangeForRouter', state)
      let athlete = state.entities.athletes[ this.id ]
      return {
        counterValue: state.counter,
        athletes: state.entities.athletes,
        athlete: athlete,
        country: state.entities.countries[ athlete.country ],
        medals: athlete.medals.map(id => state.entities.medals[id])
      }
    },
    mapDispatchToProps (dispatch) {
      console.log('############# Private: DispatchToProps #############')
      return {actions: bindActionCreators(Actions, dispatch)}
    }
  }
}
</script>

<style lang="scss">

.athlete {
  border: 1px solid #ccc;
  box-shadow: 0 2px 5px 0 rgba(0,0,0,0.16),0 2px 10px 0 rgba(0,0,0,0.12);
  margin: 0 auto;
  max-width: 800px;
  width: 100%;

  header {
    background: #ccc;
    background-position: center center;
    background-repeat: no-repeat;
    background-size: cover;
    width: 100%;
    height: 200px;
  }
  .picture-container {
    margin: -160px 0 0 0;
    padding: 0 1em 0 1em;
    img {
      border-radius: 4px;
      border: 8px solid #fff;
      box-shadow: 0 2px 5px 0 rgba(0,0,0,0.16),0 2px 10px 0 rgba(0,0,0,0.12);
      display: inline;
      margin: 0 0 0 1em;
      width: 200px;
    }
    h2 {
      display: inline;
      font-size: 3em;
      padding: 0 0 0 .5em;
      width: auto;
    }
  }
  .flag .icon {
    display: inline-block;
    padding-bottom: .1em;
    width: auto;
  }
  .description{
    padding: 1em;
  }

  .medals {
    padding: 1em;
    p {
      font-size: 1em;
    }
    li {
      list-style: none;
    }
    .symbol {
      border-radius: 50%;
      display: inline-block;
      font-size: .8em;
      height: 1.6em;
      justify-content: center;
      margin: 5px;
      text-align: center;
      width: 1.6em;
    }
    .symbol.symbol-G {
      color: #daa520;
      background-color: #fff6de;
      border: 2px solid #daa520;
    }
    .symbol.symbol-S {
      color: #383738;
      background-color: #b9b5b5;
      border: 2px solid #383738;
    }
    .symbol.symbol-B {
      color: #6b1919;
      background-color: #ea96a1;
      border: 2px solid #6E1924;
    }
  }
}

</style>
