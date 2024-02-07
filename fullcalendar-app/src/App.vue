<template>
  <!-- Main application template -->
  <FullCalendar/>
</template>

<script>

import FullCalendar from './components/FullCalendar.vue'

export default {
  name: 'App',
  components: {
    FullCalendar
  }
}
</script>

<style>
#fullcalendar-app {
  position: relative;
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
  padding: 0 7vw;
}
/* FullCalendar specific styles to adjust the height of the calendar grid */
.fc .fc-scrollgrid-section .fc-timegrid-cols table {
  height: 100%;
}
</style>
