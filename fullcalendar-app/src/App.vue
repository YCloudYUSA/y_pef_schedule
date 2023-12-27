<template>
<!--  <img alt="Vue logo" src="./assets/logo.png">-->
<!--  <HelloWorld msg="Welcome to Your Vue.js App"/>-->
  <FullCalendar/>
</template>

<script>
// import HelloWorld from './components/HelloWorld.vue'
import FullCalendar from './components/FullCalendar.vue'

export default {
  name: 'App',
  components: {
    // HelloWorld,
    FullCalendar
  }
}
</script>

<style>
#fullcalendar-app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
  padding: 0 7vw;
}
</style>
