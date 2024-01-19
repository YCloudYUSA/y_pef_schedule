<template>
  <!-- Main application template -->
  <FullCalendar/>
</template>

<script>
// Importing the FullCalendar component from the components directory
import FullCalendar from './components/FullCalendar.vue'

// Exporting the default Vue component for the main app
export default {
  name: 'App',
  components: {
    // Registering the FullCalendar component for use in the template
    FullCalendar
  }
}
</script>

<style>
/* Application-wide styles */
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
