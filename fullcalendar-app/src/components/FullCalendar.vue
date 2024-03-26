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
      ref="popover"
      v-if="activeModal === 'eventPopover'"
      :event="selectedEvent"
      :style="popoverStyle"
      @close="closePopup"
      @edit-event="handleEditEvent"
    />
  </div>
</template>

<script>
import CalendarHeader from './CalendarHeader.vue';
import FullCalendar from '@fullcalendar/vue3';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import EventPopup from './EventPopup.vue';
import EventPopover from './EventPopover.vue';
import bootstrap5Plugin from '@fullcalendar/bootstrap5';
import EventService from '../service/EventService';
import {
  combineDateTime,
  updateUrlParams
} from '@/utils/dateUtils';
import axios from 'axios';
import html2pdf from 'html2pdf.js';
import { format } from 'date-fns';

import dayGridPlugin from '@fullcalendar/daygrid'
import listPlugin from '@fullcalendar/list'
import {
  GET_SCHEDULES_CATEGORIES,
} from '@/config/apiConfig';

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
          bootstrap5Plugin,

          dayGridPlugin,
          listPlugin,
        ],
        headerToolbar: {
          left: 'title',
          center: 'timeGridWeek,listDay',
          right: 'today,prev,next',
        },
        views: {
          listDay: { buttonText: 'day' },
        },
        themeSystem : "bootstrap5",
        initialView: window.innerWidth > 992 ? 'timeGridWeek' : 'listDay',
        editable: true,
        eventResizableFromStart: false,
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
        eventAllow: this.eventAllow,
        dayHeaderContent: (args) => {
          const dayOfWeekShort = args.date.toLocaleString('en-US', { weekday: 'short' });
          const dayOfMonth = args.date.getDate();

          return {
            html: `
                    <div class="day-header">
                      ${dayOfWeekShort} <span>${dayOfMonth}</span>
                    </div>
                  `
          };
        },
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

    // Ensure the Vue component has been fully mounted and all previous DOM
    // updates have been processed.
    Vue.nextTick().then(() => {
      // Check if the drupalSettings object exists and contains the
      // fullCalendar settings.
      if (window.drupalSettings && window.drupalSettings.fullCalendar) {
        // If the settings are present, update the calendarOptions object with
        // the settings from drupalSettings. This updates the slotDuration,
        // snapDuration, and slotLabelInterval properties of the calendarOptions
        // object, which are used to configure the behavior and appearance of
        // the FullCalendar component within the Vue application.
        this.calendarOptions.slotDuration = window.drupalSettings.fullCalendar.slotDuration;
        this.calendarOptions.snapDuration = window.drupalSettings.fullCalendar.snapDuration;
        this.calendarOptions.slotLabelInterval = window.drupalSettings.fullCalendar.slotLabelInterval;
      }
    });
  },
  methods: {
    downloadPDF() {
      // We are forced to clone the calendar in order to modify its elements
      // before transferring to the PDF, otherwise, the user will see changes
      // on the page when the PDF is loaded.
      const element = document.getElementById('fullcalendar-app');
      // Create a deep copy of an element.
      const clone = element.cloneNode(true);

      // Get the text from the calendar header.
      const scheduleTitle = document.querySelector('.fc-toolbar-title').textContent;
      const fileName = `Weekly schedule (${scheduleTitle}).pdf`;

      // Setting styles for copy.
      clone.style.padding = '0';
      clone.style.marginTop = '10px';
      clone.style.marginLeft = '25px';
      clone.style.marginRight = '25px';

      let tables = clone.querySelectorAll('.fc-timeGridWeek-view table');
      if (tables.length) {
        tables.forEach((table) => {
          table.style.maxWidth = '1488px';
        });
      }

      const calendarBranchHeader = clone.querySelectorAll('.calendar-branch-header')[0];
      if (calendarBranchHeader.length) {
        calendarBranchHeader.style.padding = '0';
        calendarBranchHeader.style.fontSize = '10px';
        calendarBranchHeader.style.position = 'absolute';
        calendarBranchHeader.style.right = '30px';
      }

      if (clone.querySelectorAll('.fc-listDay-view').length) {
        clone.querySelectorAll('.fc-listDay-view')[0].style.position = 'relative';
        clone.querySelectorAll('.fc-listDay-view table')[0].style.marginBottom = '0';
        clone.querySelectorAll('.fc-listDay-view tbody td')
          .forEach(function(element) {
            element.style.height = '3rem';
        });
      }

      clone.querySelectorAll('.fullcalendar--header, .fc-header-toolbar .fc-toolbar-chunk:not(:first-child), .calendar-branch-info h4')
        .forEach(function(element) {
        element.style.display = 'none';
      });

      // Adding copy to the DOM for PDF generation. The copy will be deleted immediately, so the user will not see the change.
      document.body.appendChild(clone);

      // Setting parameters for html2pdf.
      const options = {
        margin: [3, 5],
        filename: fileName,
        image: { type: 'jpeg', quality: 0.99 },
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
        // Removing a copy from the DOM after creating a PDF.
        document.body.removeChild(clone);
        pdf.save(options.filename);
      }).catch(function(error) {
        console.error('Error creating PDF. Action needed:', {
          message: 'Failed to create a PDF document from the provided HTML content. An error occurred during the PDF generation process.',
          action: 'Check if all dependencies for the html2pdf library are correctly loaded and ensure the HTML structure provided to the library does not contain any elements or attributes that could cause the generation to fail.',
          errorDetails: error.message || error,
          tip: 'Review any warnings or errors in the console related to html2pdf or its dependencies. Consider testing the PDF generation process with a simplified HTML structure to isolate the issue. If the problem persists, you might need to explore alternative PDF generation libraries or methods that suit your specific requirements better.'
        });
        // Make sure the copy is deleted even in case of error.
        document.body.removeChild(clone);
      });
    },
    openPopup(type) { this.activeModal = type; },
    closePopup() { this.activeModal = null; },
    eventAllow(dropInfo, draggedEvent) {
      if (dropInfo.start.getDay() === draggedEvent.start.getDay()) {
        return true;
      }
      else {
        return false;
      }
    },
    handleSelect(selectInfo) {
      // 'monday', 'tuesday', ...
      const clickedDay = format(selectInfo.start, 'EEEE').toLowerCase();

      this.selectedEvent = {
        start: selectInfo.startStr,
        end: selectInfo.endStr,
        location: this.eventService.getBranchTitle(),
        clickedDay: clickedDay,
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
        calendarEvent.setProp('color', updatedEvent.colorEvent);
        calendarEvent.setExtendedProp('colorEvent', updatedEvent.colorEvent);
        calendarEvent.setExtendedProp('description', updatedEvent.description);
        calendarEvent.setExtendedProp('instructor', updatedEvent.instructor);
        calendarEvent.setExtendedProp('room', updatedEvent.room);
      }
      // this.eventService.updateEventOnServer(updatedEvent);
    },
    addEvent(newEvent) {
      this.addEventToCalendar(newEvent);
      this.closePopup()
    },
    addEventToCalendar(newEvent) {
      const calendarApi = this.$refs.fullCalendar.getApi();
      newEvent.id = newEvent.nid + '-' + Math.random().toString(16).slice(2);

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
        id: eventInfo.event.extendedProps.nid,
        nid: eventInfo.event.extendedProps.nid,
        startGlobal: combineDateTime(eventInfo.event.extendedProps.startGlobal, eventInfo.event.start),
        endGlobal: combineDateTime(eventInfo.event.extendedProps.endGlobal, eventInfo.event.end),
      };
       this.eventService.updateEventOnServer(event);
    },
    getUrlParams() {
      const urlParams = new URLSearchParams(window.location.search);
      const calendarApi = this.$refs.fullCalendar.getApi();

      return {
        start: urlParams.get('start') ?? format(calendarApi.view.activeStart, "yyyy-MM-dd'T'HH:mm:ssXXX"),
        end: urlParams.get('end') ?? format(calendarApi.view.activeEnd, "yyyy-MM-dd'T'HH:mm:ssXXX"),
        categories: urlParams.get('categories')?.split(',') || []
      };
    },
    handleEventClick(clickInfo) {
      this.selectedEvent = {
        id: clickInfo.event.id,
        nid: clickInfo.event.extendedProps.nid,
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
      };

      this.openPopup('eventPopover');

      this.$nextTick(() => {
        // Ensure the popover element is ready in the DOM
        if (this.$refs.popover) {
          // Get the popover element and its dimensions
          const popoverElement = this.$refs.popover.$el;
          const popoverRect = popoverElement.getBoundingClientRect();

          // Get the event element dimensions and position
          const eventRect = clickInfo.el.getBoundingClientRect();
          // Get the calendar scroll container dimensions and position
          const calendarRect = document.querySelector('.fc-scrollgrid').getBoundingClientRect();

          // Determine the optimal horizontal position for the popover
          let left;
          // Check if there's enough space to the right of the event for the popover
          if (eventRect.right + popoverRect.width <= calendarRect.right) {
            // Position the popover to the right of the event
            left = eventRect.right;
          } else if (eventRect.left - popoverRect.width >= calendarRect.left) {
            // Position the popover to the left of the event if there's not enough space to the right
            left = eventRect.left - popoverRect.width;
          } else {
            // If there's insufficient space on either side, center the popover horizontally with respect to the event
            left = eventRect.left + (eventRect.width - popoverRect.width) / 2;
          }

          // Determine the optimal vertical position for the popover
          let top;
          // Check if there's enough space above the event for the popover
          if (eventRect.top - popoverRect.height >= calendarRect.top) {
            // Position the popover above the event
            top = eventRect.top - popoverRect.height;
          } else if (eventRect.bottom + popoverRect.height <= calendarRect.bottom) {
            // Position the popover below the event if there's not enough space above
            top = eventRect.bottom;
          } else {
            // If there's insufficient space above or below, center the popover vertically with respect to the event
            top = eventRect.top + (eventRect.height - popoverRect.height) / 2;
          }

          // Adjust position to ensure the popover stays within the calendar view
          top = Math.max(calendarRect.top, Math.min(top, calendarRect.bottom - popoverRect.height));
          left = Math.max(calendarRect.left, Math.min(left, calendarRect.right - popoverRect.width));

          // Apply the calculated styles to the popover for it to appear in the correct position
          this.popoverStyle = {
            top: `${top}px`,
            left: `${left}px`,
            position: 'absolute',
            zIndex: 100
          };
        }
      });
    },
    setInitialDate(startDate) {
      // Logic to set the start date of the calendar.
      const calendarApi = this.$refs.fullCalendar.getApi();
      calendarApi.gotoDate(startDate);
    },
    loadCategories() {
      axios.get(GET_SCHEDULES_CATEGORIES)
        .then(response => {
          this.categories = response.data
        })
        .catch(error => {
          console.error('Error loading categories:', {
            message: 'Failed to load categories from "/fullcalendar-api/get-schedules-categories". Please check the server connection and ensure the endpoint is correctly configured.',
            action: 'Verify the server status, check the network connection, and ensure that the "/fullcalendar-api/get-schedules-categories" endpoint is accessible, properly implemented, and returns the expected data structure.',
            errorDetails: error.message || error,
            tip: 'Review the server logs for any error messages related to the "/fullcalendar-api/get-schedules-categories" endpoint. This can provide insights into why the request failed. If the issue persists, consider reaching out to the backend team for further assistance or checking the endpoint configuration for any recent changes that might have affected its accessibility or functionality.'
          });
        });
    },
  }
};
</script>
