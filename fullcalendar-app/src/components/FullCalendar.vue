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

    <FullCalendar ref="fullCalendar" :options="calendarOptions">
      <template v-slot:eventContent='arg'>
        <b>{{ arg.timeText }}</b>
        <i>{{ arg.event.title }}</i>
      </template>
    </FullCalendar>

    <EventPopup v-if="activeModal === 'eventPopup'" :initialEvent="selectedEvent" @close="closePopup" @save="addEvent" />
    <EventPopover v-if="activeModal === 'eventPopover'" :event="selectedEvent" :style="popoverStyle" @close="closePopup" @edit="handleEdit" />
<!--    <event-popup v-if="showEditPopup" :initialEvent="selectedEvent" @close="closePopup" @save="selectedEvent.id ? updateEvent : addEvent" />-->

  </div>
</template>

<script>

// import the third-party stylesheets directly from your JS
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-icons/font/bootstrap-icons.css'; // needs additional webpack config!

import FullCalendar from '@fullcalendar/vue3';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import EventPopup from './EventPopup.vue';
import EventPopover from './EventPopover.vue';

import bootstrap5Plugin from '@fullcalendar/bootstrap5';


import EventService from '../service/EventService';

export default {
  name: 'EventsFullCalendar',
  components: {
    FullCalendar,
    EventPopup,
    EventPopover,
  },
  data() {
    return {
      activeModal: null, // 'eventPopup', 'eventEditPopup', 'eventPopover'

      popoverStyle: {},

      selectedEvent: null,
      selectedCategories: [],
      categories: [
        "Academic Enrichment - Youth",
        "Swim Lessons - Youth",
        "Birthday Parties",
        "Swim Lessons - Preschool"
      ],
      calendarOptions: {
        plugins: [interactionPlugin, timeGridPlugin, bootstrap5Plugin],
        themeSystem : "bootstrap5",
        headerToolbar: {
          left: 'prev, next, today',
          center: 'title',
          right: ''
        },
        initialView: 'timeGridWeek',
        editable: true,
        selectable: true,
        selectAllow: function(selectInfo) {
          const start = selectInfo.start;
          const end = selectInfo.end;
          // Перевірка, що кінець вибору є тим же днем (не включає наступний день)
          return start.toISOString().substring(0, 10) === end.toISOString().substring(0, 10);
        },
        // selectMirror: true,
        weekends: true,
        dayMaxEvents: false,
        events: null,
        allDaySlot: false,
        datesSet: this.handleWeekChange,
        select: this.handleSelect,
        eventClick: this.handleEventClick,
        eventDrop: this.handleEventDrop,
        eventResize: this.handleEventResize,
      }
    };
  },
  eventService: null,
  created() {
    this.eventService = new EventService();
  },
  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    const start = urlParams.get('start');
    const end = urlParams.get('end');

    if (start && end) {
      this.loadEvents(start, end);
      this.setInitialDate(start);
    } else {
      // Якщо параметри 'start' та 'end' не знайдені, використовуйте поточний тиждень
      // або будь-яку іншу логіку для визначення початкових дат
      this.loadEvents(); // Завантаження подій для поточного тижня або дефолтних дат
    }
  },
  methods: {
    openPopup(type) {
      this.activeModal = type;
    },
    closePopup() {
      this.activeModal = null;
    },
    handleSelect(selectInfo) {
      console.log('handleSelect called');

      // Логіка для обробки вибору дати
      this.selectedEvent = {
        start: selectInfo.startStr,
        end: selectInfo.endStr
        // Додайте інші необхідні вам поля
      };

      this.openPopup('eventPopup')
    },
    setInitialDate(startDate) {
      // Логіка для встановлення початкової дати календаря
      // Ви можете використовувати API вашого календаря для зміни поточного перегляду
      const calendarApi = this.$refs.fullCalendar.getApi();
      calendarApi.gotoDate(startDate);
    },
    formatDateTimeLocal(date) {
      return date.toISOString().slice(0, 19); // Обрізаємо частину про часовий пояс
    },
    handleEventDrop(dropInfo) {
      let event = {
        id: dropInfo.event.id,
        start: this.formatDateTimeLocal(dropInfo.event.start),
        end: this.formatDateTimeLocal(dropInfo.event.end),
      };
      this.eventService.updateEventOnServer(event)
    },
    handleEventResize(resizeInfo) {
      let event = {
        id: resizeInfo.event.id,
        start: this.formatDateTimeLocal(resizeInfo.event.start),
        end: this.formatDateTimeLocal(resizeInfo.event.end),
      };
      this.eventService.updateEventOnServer(event);
    },
    handleEventClick(clickInfo) {
      this.popoverStyle = {
        top: clickInfo.jsEvent.clientY + 'px',
        left: clickInfo.jsEvent.clientX + 'px'
      };

      this.selectedEvent = {
        id: clickInfo.event.id,
        title: clickInfo.event.title,
        start: clickInfo.event.start,
        end: clickInfo.event.end,
        // Інші поля, якщо потрібно
      };

      this.openPopup('eventPopover')
    },
    handleEdit(event) {
      console.log(event);
      // Логіка для редагування події
      // Може включати відкриття форми для редагування
    },
    addEventToCalendar(newEvent) {
      // Припускаємо, що newEvent містить необхідні поля для FullCalendar
      const calendarApi = this.$refs.fullCalendar.getApi();
      calendarApi.addEvent(newEvent);
    },
    addEvent(newEvent) {
      this.addEventToCalendar(newEvent);
      this.closePopup()
    },
    async handleWeekChange(payload) {
      const start = payload.startStr;
      const end = payload.endStr;

      await this.loadEvents(start, end)

      // Отримання поточного URL з браузера
      const currentUrl = window.location.href;

      // Створення URL-об'єкту для легкого маніпулювання параметрами запиту
      const url = new URL(currentUrl);

      // Встановлення або оновлення параметрів 'start' та 'end'
      url.searchParams.set('start', start);
      url.searchParams.set('end', end);

      // Заміна поточного URL без перезавантаження сторінки
      window.history.replaceState({}, '', url.toString());

      console.log(this.$route);
      // this.$router.replace({ path: this.$route.path, query: { start, end } });
    },
    handleCategoryChange() {
      const calendarApi = this.$refs.fullCalendar.getApi();

      const start = calendarApi.view.activeStart.toISOString();
      const end = calendarApi.view.activeEnd.toISOString();

      this.loadEvents(start, end);
    },
    async loadEvents(start, end) {
      this.eventService.getEvents(start, end, this.selectedCategories).then(data => this.calendarOptions.events = data);
    },
  }
};
</script>

<style>
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
