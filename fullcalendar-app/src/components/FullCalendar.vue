<script>

import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'

import EventService from '../service/EventService';

export default {
  components: {
    FullCalendar
  },
  data() {
    return {
      selectedCategories: [],
      categories: [
        "Academic Enrichment - Youth",
        "Swim Lessons - Youth",
        "Birthday Parties",
        "Swim Lessons - Preschool"
      ],
      calendarOptions: {
        plugins: [
          dayGridPlugin,
          interactionPlugin,
          listPlugin,
        ],
        headerToolbar: {
          left: 'prev, next, today',
          center: 'title',
          // right: 'dayGridMonth, dayGridWeek, listDay'
          right: 'dayGridWeek'
        },
        views: {
          // dayGridMonth: { buttonText: 'month' },
          dayGridWeek: { buttonText: 'week' },
          // listDay: { buttonText: 'list day' },
        },

        initialView: 'dayGridWeek',
        // initialEvents: INITIAL_EVENTS,
        editable: true,
        selectable: true,
        weekends: true,
        datesSet: this.handleWeekChange,
        dateClick: (arg) => {
          console.log(arg.dateStr)
          console.log(this.selectedCategories);
        },
        events: null,
      }
    }
  },
  eventService: null,
  created() {
    this.eventService = new EventService();
  },
  mounted() {
    this.loadEvents();
  },
  methods: {
    async handleWeekChange(payload) {
      const start = payload.startStr;
      const end = payload.endStr;

      await this.loadEvents(start, end)
    },
    handleCategoryChange() {
      const calendarApi = this.$refs.fullCalendar.getApi();

      const start = calendarApi.view.activeStart.toISOString();
      const end = calendarApi.view.activeEnd.toISOString();

      console.log('startdd', start)
      console.log('enddd', end)

      this.loadEvents(start, end);
    },
    async loadEvents(start, end) {
      this.eventService.getEvents(start, end, this.selectedCategories).then(data => this.calendarOptions.events = data);
    }
  }
}
</script>

<template>
  <div>
    <div class="fullcalendar--header">
      <div class="pdf">
        <button @click="downloadPDF">Download PDF</button>
      </div>

      <div class="checkbox-container">
        <div v-for="category in categories" :key="category" class="checkbox-item">
          <input type="checkbox" :value="category" v-model="selectedCategories" @change="handleCategoryChange" />
          <label>{{ category }}</label>
        </div>
      </div>
    </div>

    <FullCalendar ref="fullCalendar" :options="calendarOptions" />
  </div>
</template>

<style scoped>
.fullcalendar--header {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  padding: 30px 0;
}

.checkbox-container {
  display: flex;
  flex-wrap: wrap;
}

.checkbox-item {
  margin-right: 15px;
}

.checkbox-item input[type="checkbox"]:checked {
  color: green;
}
</style>
