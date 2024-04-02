<template>
  <div class="fc-modal-event modal-backdrop" v-click-outside="handleClose" @click="handleBackdropClick">
    <div class="modal fade show" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document" @click.stop>
        <div class="modal-content border-0 shadow-lg rounded-lg">
          <div class="modal-header bg-secondary text-white">
            <h5 class="modal-title">{{this.event.label}} event</h5>
            <button type="button" class="close text-white" @click="handleClose">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="submitEvent">
              <!-- nid: Hidden input for edit mode -->
              <input type="hidden" v-model="event.nid"/>

              <div class="mb-3">
                <input class="form-control" type="text" v-model="event.title" required placeholder="Enter a title"/>
              </div>

              <div class="mb-3">
                <Select2 :options="selectClasses" v-model="event.classId" required @change="changeClass($event)" @select="selectClass($event)" placeholder="Class"/>
              </div>

              <div class="mb-3">
                <input id="room" class="form-control" type="text" v-model="event.room" placeholder="Room"/>
              </div>

              <div class="mb-3">
                <Select2 class="mt-3" :options="selectLocations" required v-model="event.locationId" @change="changeLocation($event)" @select="selectEvent($event)" placeholder="Location"/>
              </div>

              <div class="mb-2">
                <input class="form-control" type="text" v-model="event.instructor" placeholder="Instructor"/>
              </div>

              <div class="mb-2">
                <textarea class="form-control" v-model="event.description" rows="3" placeholder="Session Description"></textarea>
              </div>

              <div class="row g-2">
                <div class="col-md-6">
                  <label for="start" class="form-label">Start date</label>
                  <input type="datetime-local" class="form-control" id="start" v-model="event.startGlobal" name="start" required>
                </div>
                <div class="col-md-6">
                  <label for="end" class="form-label">End date</label>
                  <input type="datetime-local" class="form-control" id="end" v-model="event.endGlobal" name="end" required>
                </div>
              </div>

              <div class="day-selector">
                <h5>Days</h5>
                <div class="form-check" v-for="(day, index) in daysOfWeek" :key="index">
                  <input class="form-check-input" type="checkbox" :value="day.id" v-model="event.days" :id="day.id">
                  <label class="form-check-label" :for="day.id">
                    {{ day.text }}
                  </label>
                </div>
              </div>
              <button type="submit" class="btn btn-success mt-3">{{this.event.label}}</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Select2 from 'vue3-select2-component';
import {
  UPDATE_EVENT_ENDPOINT,
  CREATE_EVENT_ENDPOINT,
  GET_CLASSES_OPTIONS,
  GET_BRANCHES_OPTIONS,
} from '@/config/apiConfig';

export default {
  name: 'EventPopup',
  components: {
    Select2,
  },
  props: {
    initialEvent: {
      type: Object,
      required: true,
    }
  },
  data() {
    return {
      event: {
        ...this.initialEvent,
        location: null,
        eventClass: null
      },
      selectClasses: [],
      selectLocations: [],
      currentLocation: null,
      daysOfWeek: [
        { id: 'sunday', text: 'Sunday' },
        { id: 'monday', text: 'Monday' },
        { id: 'tuesday', text: 'Tuesday' },
        { id: 'wednesday', text: 'Wednesday' },
        { id: 'thursday', text: 'Thursday' },
        { id: 'friday', text: 'Friday' },
        { id: 'saturday', text: 'Saturday' },
      ],
      selectedDays: [],
    };
  },
  watch: {
    initialEvent: {
      deep: true,
      immediate: true,
      handler(newVal) {
        this.event = {
          ...newVal,
          start: this.formatDateTimeLocal(newVal.start),
          end: this.formatDateTimeLocal(newVal.end),
          days: newVal.days ? newVal.days.split(',') : [],
          label: this.event.nid ? 'Update' : 'Create',
        };
        this.event.startGlobal =  this.event.startGlobal ? this.formatDateTimeLocal(newVal.startGlobal) : this.event.start
        this.event.endGlobal =  this.event.endGlobal ? this.formatDateTimeLocal(newVal.endGlobal) : this.event.end

        // Automatic selection of the clicked day.
        if (newVal.clickedDay) {
          this.event.days = [newVal.clickedDay];
        }
      }
    }
  },
  methods: {
    handleBackdropClick() {
      this.handleClose();
    },
    loadClasses() {
      axios.get(GET_CLASSES_OPTIONS)
        .then(response => {
          this.selectClasses = Object.entries(response.data).map(([id, title]) => ({
            id: id,
            text: title
          }));
        })
        .catch(error => {
          console.error('Error loading classes:', {
            message: 'Failed to load class options from the server. Please check the server connection and ensure the endpoint "/fullcalendar-api/get-classes-options" is correctly configured.',
            action: 'Verify the network connection, check the server logs for any errors related to the "/fullcalendar-api/get-classes-options" endpoint, and ensure that the endpoint is properly implemented and accessible.',
            errorDetails: error.message || error,
            tip: 'If the problem persists, consider contacting the server administrator or technical support with the details of this error log.'
          });
        });
    },
    loadLocations() {
      axios.get(GET_BRANCHES_OPTIONS)
        .then(response => {
          this.selectLocations = Object.entries(response.data).map(([id, title]) => ({
            id: id,
            text: title
          }))
          if (this.event.location) {
            const matchingLocation = this.selectLocations.find(location => this.event.location === location.text);
            this.event.locationId = matchingLocation ? matchingLocation.id : null;
            if (!matchingLocation) {
              console.warn('Warning: Location not found. Action needed:', {
                message: `The location '${this.event.location}' was not found in the loaded options.`,
                action: 'Ensure the event\'s location matches one of the available options in /fullcalendar-api/get-branches-options. It might require updating the event location or ensuring the /fullcalendar-api/get-branches-options endpoint returns all expected location options.',
                suggestion: 'Check the list of locations returned by /fullcalendar-api/get-branches-options for completeness. If the location is missing, update the backend to include all necessary locations.',
              });
            }
          }
        })
        .catch(error => {
          console.error('Error loading locations:', {
            message: 'Failed to load locations from /fullcalendar-api/get-branches-options. Please check if the server is running and the endpoint is correctly configured.',
            action: 'Verify the server status, check the network connection, and ensure the /fullcalendar-api/get-branches-options endpoint is accessible and returning the correct data structure.',
            errorDetails: error.message || error,
            tip: 'If the problem persists, consider reviewing server logs for more details or contacting technical support with this error information.'
          });
        });
    },
    formatDateTimeLocal(dateString) {
      const date = new Date(dateString);
      const offset = date.getTimezoneOffset();
      date.setMinutes(date.getMinutes() - offset);
      return date.toISOString().slice(0, 19);
    },
    // TODO: Move to service.
    async sendEventToServer(eventData) {
      // Create an array of day IDs. If none are selected, use all days.
      const dayIds = this.event.days.length ? this.event.days : this.daysOfWeek.map(day => day.id);
      // Assuming the server expects a string of comma-separated values.
      eventData.days = dayIds.join(',');
      eventData.location = this.event.location;
      eventData.eventClass = this.event.eventClass;

      try {
        const url = eventData.nid ? UPDATE_EVENT_ENDPOINT : CREATE_EVENT_ENDPOINT;

        const response = await axios.post(url, eventData);
        if (response.status === 200) {
          if (response.data.id) {
            eventData.nid = response.data.id;
            eventData.color = response.data.color;
          }

          this.$emit('save', eventData);
          this.handleClose();
        }
      } catch (error) {
        console.error('Error sending event to the server. Action Needed: Please check the network connection and try again.', {
          eventData,
          errorDetails: error.message || error,
          url: eventData.nid ? 'Updating event' : 'Creating new event',
          action: 'If the problem persists, contact support with this error log.',
        });
      }
    },
    submitEvent() {
      this.sendEventToServer(this.event);
    },
    handleClose() {
      this.$emit('close');
    },
    changeLocation(value) {
      this.event.location = value;
    },
    selectEvent({ id, text }) {
      this.event.location = id;
    },
    changeClass(value) {
      this.event.eventClass = value;
    },
    selectClass({ id, text }) {
      this.event.eventClass = id;
    }
  },
  created() {
    this.loadClasses();
    this.loadLocations();
  }
};
</script>

<style lang="scss">
.fullcalendar-app {
  .modal.show {
    display: block;
    padding-right: 17px;
  }

  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1050;
  }

  .modal-dialog {
    max-width: 500px;
    margin: 3.5rem auto;

    @media (max-width: 1550px) {
      margin: 100px auto;
    }
  }

  .fc-modal-event {
    .modal-header {
      border-bottom: none;
      border-top-left-radius: unset;
      border-top-right-radius: unset;
    }
  }

  .modal-body {
    padding: 1.5rem;
  }

  .form-compact {
    textarea {
      resize: none;
    }
  }

  .day-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;

    h5 {
      grid-column: span 2;
      margin-top: 0;
    }
  }

  .form-check {
    display: flex;
    align-items: center;

    &-input {
      $check-border: rgba(0, 0, 0, 0.25);

      cursor: pointer;
      appearance: none;
      background-color: #fff;
      margin: 0;
      font: inherit;
      color: currentColor;
      width: 1.15em;
      height: 1.15em;
      border: 0.1em solid $check-border;
      border-radius: 0.15em;
      transform: translateY(-0.075em);
      display: grid;
      place-content: center;

      &:checked {
        background-color: #007bff;
        border-color: #007bff;

        &::before {
          content: "";
          width: 0.65em;
          height: 0.65em;
          clip-path: polygon(14% 44%, 50% 80%, 86% 21%, 77% 14%, 50% 68%, 21% 35%);
          transform: scale(1.2);
          background-color: #fff;
        }
      }
    }

    &-label {
      cursor: pointer;
      padding-left: 25px;
    }
  }

  .btn-success {
    background-color: #4CAF50;
    border-color: #4CAF50;

    &:hover {
      background-color: #45a049;
    }
  }
}
</style>
