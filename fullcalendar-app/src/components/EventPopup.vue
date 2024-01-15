<template>
<!--  <div class="modal-backdrop" @click="handleBackdropClick">-->
  <div class="modal-backdrop" v-click-outside="handleClose">
    <div class="modal fade show" tabindex="-1" role="dialog" style="display: block; padding-right: 17px;">
      <div class="modal-dialog modal-dialog-centered" role="document" @click.stop>
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Create event</h5>
            <button type="button" class="close text-white" @click="handleClose">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="submitEvent">
              <div class="form-group">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" v-model="event.title" name="title" placeholder="Enter a title">
                <div v-if="titleError" class="text-danger mt-1">{{ titleError }}</div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="start" class="form-label">Start date</label>
                  <input type="datetime-local" class="form-control" id="start" v-model="event.start" name="start">
                  <div v-if="startError" class="text-danger mt-1">{{ startError }}</div>
                </div>
                <div class="form-group col-md-6">
                  <label for="end" class="form-label">End date</label>
                  <input type="datetime-local" class="form-control" id="end" v-model="event.end" name="end">
                  <div v-if="endError" class="text-danger mt-1">{{ endError }}</div>
                </div>
              </div>
              <div class="form-group">
                <label for="color" class="form-label">Color</label>
                <input type="color" class="form-control form-control-color" id="color" v-model="event.color" name="color" title="Виберіть колір">
              </div>
              <button type="submit" class="btn btn-success">Create</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>



<script>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useForm, useField } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';

export default {
  name: 'EventPopup',
  props: {
    initialEvent: {
      type: Object,
      required: true,
    }
  },
  setup(props, { emit }) {
    const { handleSubmit, resetForm } = useForm();
    // Використання ref для збереження значення event
    const event = ref({ ...props.initialEvent });

    // Стежимо за змінами initialEvent і оновлюємо локальний стан event
    watch(() => props.initialEvent, (newVal) => {
      event.value = {
        ...newVal,
        start: formatDateTimeLocal(newVal.start),
        end: formatDateTimeLocal(newVal.end),
        color: '#000000'
      };
      resetForm({
        values: {
          title: newVal.title || '',
        }
      });
    }, { deep: true, immediate: true });

    // Функція для обробки натискання клавіш
    // const handleKeydown = (event) => {
    //   if (event.key === 'Escape') {
    //     handleClose();
    //   }
    // };
    // // Додаємо слухача подій при монтуванні компонента
    // onMounted(() => {
    //   window.addEventListener('keydown', handleKeydown);
    // });
    //
    // // Видаляємо слухача подій при демонтуванні компонента
    // onBeforeUnmount(() => {
    //   window.removeEventListener('keydown', handleKeydown);
    // });

    function formatDateTimeLocal(dateString) {
      const date = new Date(dateString);
      const offset = date.getTimezoneOffset();
      date.setMinutes(date.getMinutes() - offset);
      return date.toISOString().slice(0, 19);
    }

    const sendEventToServer = async (eventData) => {
      try {
        console.log('Sending event to the server ...');
        const response = await axios.post('/admin/openy/schedules/create-event', eventData);

        // If the request is successful, get the event id from the response
        if (response.status === 200) {
          // const eventId = response.data.id;
          // // Add the event id to the event object
          // event.id = eventId;
          // // Add the event to the FullCalendar instance
          // this.$refs.fullCalendar.getApi().addEvent(event);
          // // Close the modal window
          // this.showModal = false;
          // Show a success message
          // alert('Event saved successfully!');
        }
      } catch (error) {
        console.error('Помилка при відправці події:', error);
        // Обробка помилок
      }
    };

    // const handleBackdropClick = () => {
    //   handleClose();
    // };
    const handleClose = () => {
      emit('close');
    };

    const submitEvent = handleSubmit(async (values) => {
      console.log('Form is valid, submitting:', values);
      try {
        emit('save', { ...event.value, title: values.title });
        await sendEventToServer(event.value);
        handleClose();
      } catch (error) {
        console.error('Error submitting event:', error);
      }
    });
    return {
      event,
      submitEvent,
      // handleBackdropClick,
      handleClose
    };
  }
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1050; /* Забезпечте належне позиціонування на сторінці */
}
</style>
