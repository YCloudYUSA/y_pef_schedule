<template>
  <div class="fc-modal-event modal-backdrop" v-click-outside="handleClose" @click="handleBackdropClick">
    <div class="modal fade show" tabindex="-1" role="dialog" style="display: block; padding-right: 17px;">
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

              <div class="mt-2">
                <input type="color" class="form-control form-control-color" id="color" v-model="event.colorEvent" name="color" title="Choose a color">
              </div>

              <button type="submit" class="btn btn-success mt-3">{{this.event.label}}</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
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
}
.fc-modal-event .modal-header {
  border-bottom: none;
  border-top-left-radius: unset;
  border-top-right-radius: unset;
}
.modal-body {
  padding: 1.5rem;
}
.form-compact textarea {
  resize: none;
}
@media (max-width: 576px) {
  .modal-dialog {
    margin: 1.5rem auto;
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
}
.day-selector h5 {
  grid-column: span 2;
  margin-top: 0;
}
.form-check {
  display: flex;
  align-items: center;
}
.form-check-input {
  margin-right: 0.5rem;
  cursor: pointer;
}
.form-check-label {
  cursor: pointer;
  padding-left: 25px;
}
.btn-success {
  background-color: #4CAF50;
  border-color: #4CAF50;
}
.btn-success:hover {
  background-color: #45a049;
}
.form-check-input {
  appearance: none;
  background-color: #fff;
  margin: 0;
  font: inherit;
  color: currentColor;
  width: 1.15em;
  height: 1.15em;
  border: 0.1em solid rgba(0, 0, 0, 0.25);
  border-radius: 0.15em;
  transform: translateY(-0.075em);
  display: grid;
  place-content: center;
}
.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}
.form-check-input:checked::before {
  content: "";
  width: 0.65em;
  height: 0.65em;
  clip-path: polygon(14% 44%, 50% 80%, 86% 21%, 77% 14%, 50% 68%, 21% 35%);
  transform: scale(1.2);
  background-color: #fff;
}
</style>
<script>

import axios from 'axios';
import Select2 from 'vue3-select2-component';
import {object} from "yup";

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
          colorEvent: newVal.colorEvent || '#3788d8',
          days: newVal.days ? newVal.days.split(',') : [],
          label: this.event.nid ? 'Update' : 'Create',
        };
        this.event.startGlobal =  this.event.startGlobal ? this.formatDateTimeLocal(newVal.startGlobal) : this.event.start
        this.event.endGlobal =  this.event.endGlobal ? this.formatDateTimeLocal(newVal.endGlobal) : this.event.end
      }
    }
  },
  methods: {
    handleBackdropClick() {
      this.handleClose();
    },
    loadClasses() {
      axios.get('/classes-options')
        .then(response => {
          this.selectClasses = Object.entries(response.data).map(([id, title]) => ({
            id: id,
            text: title
          }));
        })
        .catch(error => {
          console.error('Error loading classes:', error);
        });
    },
    loadLocations() {
      axios.get('/branches-options')
        .then(response => {
          this.selectLocations = Object.entries(response.data).map(([id, title]) => ({
            id: id,
            text: title
          }))
          if (this.event.location) {
            this.event.locationId = this.selectLocations.filter((location) => this.event.location === location.text)[0].id
          }
        })
        .catch(error => {
          console.error('Error loading options:', error);
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
        console.log('Sending event to the server ...', eventData);
        // TODO: Should be const in configuration.
        let url;
        if (eventData.nid) {
          url ='/admin/openy/schedules/update-event';
        } else {
          url = '/admin/openy/schedules/create-event';
        }
        const response = await axios.post(url, eventData);
        if (response.status === 200) {

          if (response.data.id) {
            eventData.nid = response.data.id;
          }

          this.$emit('save', eventData);
          this.handleClose();
        }
      } catch (error) {
        console.error('Error sending event to the server:', error);
      }
    },
    submitEvent() {
      this.event.color = this.event.colorEvent;
      this.sendEventToServer(this.event);
    },
    handleClose() {
      this.$emit('close');
    },
    changeLocation(value) {
      console.log('Change event:', value);
      this.event.location = value;
    },
    selectEvent({ id, text }) {
      console.log('Select event:', { id, text });
      this.event.location = id;
    },
    changeClass(value) {
      console.log('Change class:', value);
      this.event.eventClass = value;
    },
    selectClass({ id, text }) {
      console.log('Select class:', { id, text });
      this.event.eventClass = id;
    }
  },
  created() {
    this.loadClasses();
    this.loadLocations();
  }
};
</script>
