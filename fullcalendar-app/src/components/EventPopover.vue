<template>
  <div class="event-popover card" v-click-outside="closePopover">
  <div class="card-header">
      <h5 class="card-title m-0">{{ event.title }}</h5>
      <button type="button" class="close" aria-label="Close" @click="$emit('close')">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="card-body">
      <p class="card-text"><strong>Start:</strong> {{ formatDate(event.start) }}</p>
      <p class="card-text"><strong>End:</strong> {{ formatDate(event.end) }}</p>
    </div>
    <div class="card-footer text-right">
      <button type="button" class="btn btn-primary" @click="editEvent">Edit</button>
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
    formatDate(date) {
      return new Date(date).toLocaleString();
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

.card-body {
  text-align: left;
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
  padding-right: 10px;
}
</style>
