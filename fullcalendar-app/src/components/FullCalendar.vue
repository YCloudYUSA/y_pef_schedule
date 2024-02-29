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
        <i>{{ arg.event.title }}</i>
        <br>
        <b>{{ arg.timeText }}</b>
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
import axios from 'axios';
import html2pdf from 'html2pdf.js';


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
      initializationCompleted: false,
      categories: [],
      calendarOptions: {
        plugins: [
          interactionPlugin,
          timeGridPlugin,
          bootstrap5Plugin
        ],
        themeSystem : "bootstrap5",
        initialView: window.innerWidth > 768 ? 'timeGridWeek' : 'timeGridDay',
        editable: true,
        selectable: true,
        selectAllow: this.checkSameDaySelection,
        selectMirror: true,
        weekends: true,
        dayMaxEvents: false,
        events: null,
        allDaySlot: false,
        // Each slot lasting 30 minutes.
        slotDuration: '00:30:00',
        // Clicks and drags are "captured" every 30 minutes.
        snapDuration: '00:30:00',
        // Timestamps every hour.
        slotLabelInterval: '01:00',
        timeZone: 'local',
        nowIndicator: true,
        slotLabelFormat: {
          hour: 'numeric',
          minute: '2-digit',
          omitZeroMinute: false,
          hour12: false,
        },
        datesSet: this.handleWeekChange,
        select: this.handleSelect,
        eventClick: this.handleEventClick,
        eventDrop: this.updateEvent,
        eventResize: this.updateEvent,
      }
    };
  },
  created() {
    this.eventService = new EventService();
    this.loadCategories();
  },
  mounted() {
    const { start, end, categories } = this.getUrlParams();

    if (categories.length > 0) {
      this.handleCategoryChange(categories, true);
    }

    if (start && end) {
      this.setInitialDate(start);
      this.loadEvents(start, end, true)
    }
    else {
      // If the URL parameters are not set, we perform the initialization by default.
      const today = new Date();
      const start = today.toISOString().split('T')[0];
      const end = new Date(today.setDate(today.getDate() + 7)).toISOString().split('T')[0];
      this.loadEvents(start, end, true);
    }
  },
  methods: {
    downloadPDF() {
      // We are forced to clone the calendar in order to modify its elements
      // before transferring to the PDF, otherwise, the user will see changes
      // on the page when the PDF is loaded.
      const element = document.getElementById('fullcalendar-app'); // The original element of the calendar.
      const clone = element.cloneNode(true); // Create a deep copy of an element

      // Get the text from the calendar header.
      const scheduleTitle = document.querySelector('.fc-toolbar-title').textContent;
      const fileName = `Weekly schedule (${scheduleTitle}).pdf`;

      // Setting styles for copy.
      clone.style.padding = '0';
      clone.style.margin = '0';
      clone.querySelectorAll('.fullcalendar--header, .fc-header-toolbar').forEach(function(element) {
        element.style.display = 'none';
      });

      // Adding copy to the DOM for PDF generation. The copy will be deleted immediately, so the user will not see the change.
      document.body.appendChild(clone);

      // Setting parameters for html2pdf.
      const options = {
        margin: [5, 5],
        filename: fileName,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: {
          unit: 'mm',
          format: 'a3',
          orientation: 'landscape',
          compressPDF: true
        }
      };

      // Generate PDF from copy.
      html2pdf().from(clone).set(options).toPdf().get('pdf').then(function (pdf) {
        document.body.removeChild(clone); // Removing a copy from the DOM after creating a PDF.
        pdf.save(options.filename);
      }).catch(function(error) {
        console.error('Помилка при створенні PDF:', error);
        document.body.removeChild(clone); // Make sure the copy is deleted even in case of error.
      });
    },
    openPopup(type) { this.activeModal = type; },
    closePopup() { this.activeModal = null; },
    handleSelect(selectInfo) {
      this.selectedEvent = {
        start: selectInfo.startStr,
        end: selectInfo.endStr,
        location: this.eventService.getBranch(),
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
      // We check that initialization is complete before calling loadEvents.
      if (this.initializationCompleted) {
        const { startStr, endStr } = payload;
        await this.loadEvents(startStr, endStr);
        updateUrlParams(startStr, endStr);
      }
    },
    handleCategoryChange(categories, isMounted) {
      const calendarApi = this.$refs.fullCalendar.getApi();
      const start = calendarApi.view.activeStart.toISOString();
      const end = calendarApi.view.activeEnd.toISOString();

      this.selectedCategories = categories
      // If the function is called on mount, we avoid calling loadEvents again.
      if (!isMounted) {
        this.loadEvents(start, end);
      }

      updateUrlParams(start, end, categories);
    },
    async loadEvents(start, end, isInitialization = false) {
      // A condition to prevent redundant calls during initialization.
      if (isInitialization || this.initializationCompleted) {
        this.eventService.getEvents(start, end, this.selectedCategories)
          .then(data => {
            this.calendarOptions.events = data;

            // We establish that the initialization is complete.
            if (isInitialization) this.initializationCompleted = true;
          });
      }
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
    getUrlParams() {
      const urlParams = new URLSearchParams(window.location.search);

      return {
        start: urlParams.get('start'),
        end: urlParams.get('end'),
        categories: urlParams.get('categories')?.split(',') || []
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
        room: clickInfo.event.extendedProps.room,
        colorEvent: clickInfo.event.extendedProps.colorEvent,
        instructor: clickInfo.event.extendedProps.instructor,
        description: clickInfo.event.extendedProps.description,
        locationId: clickInfo.event.extendedProps.locationId,
        classId: clickInfo.event.extendedProps.classId,
        days: clickInfo.event.extendedProps.days,
        startGlobal: clickInfo.event.extendedProps.startGlobal,
        endGlobal: clickInfo.event.extendedProps.endGlobal,
        // ... other fields.
      };

      this.openPopup('eventPopover');
    },
    setInitialDate(startDate) {
      // Logic to set the start date of the calendar.
      const calendarApi = this.$refs.fullCalendar.getApi();
      calendarApi.gotoDate(startDate);
    },
    loadCategories() {
      axios.get('/schedules-categories')
        .then(response => {
          this.categories = response.data
        })
        .catch(error => {
          console.error('Error loading classes:', error);
        });
    },
  }
};
</script>
