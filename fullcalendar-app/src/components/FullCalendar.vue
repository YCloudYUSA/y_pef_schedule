<template>
  <div>
    <CalendarHeader
      :categories="categories"
      :selectedCategories="selectedCategories"
      @downloadPDF="downloadPDF"
      @categoryChange="handleCategoryChange"
    />

    <FullCalendar ref="fullCalendar" :options="calendarOptions">
      <template v-slot:eventContent="arg">
        <b>{{ arg.timeText }}</b>
        <i>{{ arg.event.title }}</i>
      </template>
    </FullCalendar>

    <EventPopup
      v-if="activeModal === 'eventPopup'"
      :initialEvent="selectedEvent"
      @close="closePopup"
      @save="handleEventSaveOrUpdate"
    />

    <EventPopover
      v-if="activeModal === 'eventPopover'"
      :event="selectedEvent"
      :style="popoverStyle"
      @close="closePopup"
      @edit-event="handleEditEvent"
    />
  </div>
</template>

<script>

import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-icons/font/bootstrap-icons.css';

import CalendarHeader from './CalendarHeader.vue';
import FullCalendar from '@fullcalendar/vue3';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import EventPopup from './EventPopup.vue';
import EventPopover from './EventPopover.vue';
import bootstrap5Plugin from '@fullcalendar/bootstrap5';
import EventService from '../service/EventService';
import { formatDateTimeLocal, updateUrlParams } from '@/utils/dateUtils';


export default {
  name: 'EventsFullCalendar',
  components: {
    FullCalendar,
    EventPopup,
    EventPopover,
    CalendarHeader,
  },
  data() {
    return {
      activeModal: null,
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
        plugins: [
          interactionPlugin,
          timeGridPlugin,
          bootstrap5Plugin
        ],
        themeSystem : "bootstrap5",
        headerToolbar: {
          left: 'prev, next, today',
          center: 'title',
          right: ''
        },
        initialView: 'timeGridWeek',
        editable: true,
        selectable: true,
        selectAllow: this.checkSameDaySelection,
        selectMirror: false,
        weekends: true,
        dayMaxEvents: false,
        events: null,
        allDaySlot: false,
        datesSet: this.handleWeekChange,
        select: this.handleSelect,
        eventClick: this.handleEventClick,
        eventDrop: this.updateEvent,
        eventResize: this.updateEvent,
      }
    };
  },
  created() { this.eventService = new EventService(); },
  mounted() {
    const { start, end } = this.getInitialDateParams();
    start && end ? this.loadEvents(start, end) : this.loadEvents();

    if (start) {
      this.setInitialDate(start);
    }
  },
  methods: {
    downloadPDF() { /* PDF download logic */ },
    openPopup(type) { this.activeModal = type; },
    closePopup() { this.activeModal = null; },
    handleSelect(selectInfo) {
      this.selectedEvent = {
        start: selectInfo.startStr,
        end: selectInfo.endStr
      };

      this.openPopup('eventPopup')
    },
    handleEditEvent(event) {
      this.selectedEvent = { ...event };
      this.openPopup('eventPopup');
    },
    handleEventSaveOrUpdate(eventData) {
      if (eventData.id) {
        this.updateEventOnCalendar(eventData);
      } else {
        this.addEventToCalendar(eventData);
      }
      this.closePopup();
    },
    updateEventOnCalendar(updatedEvent) {
      const calendarApi = this.$refs.fullCalendar.getApi();
      let calendarEvent = calendarApi.getEventById(updatedEvent.id);

      if (calendarEvent) {
        calendarEvent.setProp('title', updatedEvent.title);
        calendarEvent.setStart(updatedEvent.start);
        calendarEvent.setEnd(updatedEvent.end);
      }

      // this.eventService.updateEventOnServer(updatedEvent);
    },
    addEvent(newEvent) {
      this.addEventToCalendar(newEvent);
      this.closePopup()
    },
    addEventToCalendar(newEvent) {
      const calendarApi = this.$refs.fullCalendar.getApi();
      calendarApi.addEvent(newEvent);
    },
    async handleWeekChange(payload) {
      const { startStr, endStr } = payload;
      await this.loadEvents(startStr, endStr)

      updateUrlParams(startStr);
    },
    handleCategoryChange(categories) {
      const calendarApi = this.$refs.fullCalendar.getApi();
      const start = calendarApi.view.activeStart.toISOString();
      const end = calendarApi.view.activeEnd.toISOString();

      this.selectedCategories = categories
      this.loadEvents(start, end);
    },
    async loadEvents(start, end) {
      this.eventService.getEvents(start, end, this.selectedCategories)
        .then(data => this.calendarOptions.events = data);
    },
    checkSameDaySelection(selectInfo) {
      const start = selectInfo.start;
      const end = selectInfo.end;
      // Checking that the end of the selection is the same day (does not include the next day).
      return start.toISOString().substring(0, 10) === end.toISOString().substring(0, 10);
    },
    updateEvent(eventInfo) {
      const event = {
        id: eventInfo.event.id,
        start: formatDateTimeLocal(eventInfo.event.start),
        end: formatDateTimeLocal(eventInfo.event.end),
      };
      // this.eventService.updateEventOnServer(event);
    },
    getInitialDateParams() {
      const urlParams = new URLSearchParams(window.location.search);

      return {
        start: urlParams.get('start'),
        end: urlParams.get('end')
      };
    },
    handleEventClick(clickInfo) {
      // Approximate width of the popover.
      const popoverWidth = 300;
      // Approximate height of the popover.
      const popoverHeight = 200;
      // Minimum space between the popover and the edge of the window.
      const windowPadding = 10;

      let top = clickInfo.jsEvent.clientY;
      let left = clickInfo.jsEvent.clientX;

      // Adjust for the bottom edge of the window.
      if (window.innerHeight - top < popoverHeight + windowPadding) {
        top -= popoverHeight;
      }

      // Adjust for the right edge of the window.
      if (window.innerWidth - left < popoverWidth + windowPadding) {
        left -= popoverWidth;
      }

      // Prevent popover from going off the top or left edge of the screen.
      top = Math.max(windowPadding, top);
      left = Math.max(windowPadding, left);

      this.popoverStyle = {
        top: `${top}px`,
        left: `${left}px`
      };

      this.selectedEvent = {
        id: clickInfo.event.id,
        title: clickInfo.event.title,
        start: clickInfo.event.start,
        end: clickInfo.event.end,
        // ... other fields.
      };

      this.openPopup('eventPopover');
    },
    setInitialDate(startDate) {
      // Logic to set the start date of the calendar.
      const calendarApi = this.$refs.fullCalendar.getApi();
      calendarApi.gotoDate(startDate);
    },
  }
};
</script>
