<template>
  <div class="event-popover card" v-click-outside="closePopover">
    <div class="card-body">
      <h5 class="card-title m-0"><strong>{{ event.title }}</strong></h5>
      <button type="button" class="close" aria-label="Close" @click="$emit('close')">
        <span aria-hidden="true">&times;</span>
      </button>
      <p class="card-text">{{ formatDate(event.end) }}</p>
      <hr>

      <p class="card-text">
        <div class="opacity-50 small">Time</div>
        {{ formatTime(event.start) }} - {{ formatTime(event.end) }}
      </p>
      <hr>

      <p class="card-text">
        <div class="opacity-50 small">Room</div>
        <div> {{event.room}}</div>
      </p>
      <hr>

      <p class="card-text">
        <div class="opacity-50 small">Instructor</div>
        <div> {{event.instructor}}</div>
      </p>

    </div>
    <div class="card-footer text-right">
      <button type="button" class="btn btn-secondary" @click="editEvent">Edit</button>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    event: {
      type: Object,
      required: true
    }
  },
  methods: {
    formatTime(date) {
      const dateString = new Date(date);
      return dateString.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true })
    },
    formatDate(date) {
      const dateString = new Date(date).toDateString();
      return `${dateString.slice(0, 3)}, ${dateString.slice(4, 10)}`;
    },
    editEvent() {
      this.$emit('edit-event', this.event);
    }
  }
};
</script>

<style scoped>
.event-popover {
  position: absolute;
  z-index: 100;
  width: auto;
  max-width: 600px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

button.close {
  position: absolute;
  top: 10px;
  right: 15px;
}
hr {
  margin: 0.3rem;
}
.card-body {
  text-align: left;
}

.card-text {
  margin-bottom: 0.5rem;
}
.card-header {
  display: flex;
  justify-content: space-between;
}

.card-footer {
  padding: 0.75rem 1.25rem;
  background-color: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.event-popover .card-title {
  padding-right: 30px;
  padding-bottom: 10px;
}
</style>
