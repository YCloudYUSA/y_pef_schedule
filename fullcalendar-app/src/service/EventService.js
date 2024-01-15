import axios from 'axios';
import moment from "moment/moment";


export default class EventService {

  getEvents(start, end, categories = []) {
    if (start === end) {
      return Promise.resolve([]);
    }


    const categoryParams = categories.length > 0 ? '/' + categories.join(',') : '';
    const url = 'http://yusaopeny.docksal.site/schedules/get-event-data-date-range/'+ this.getBranch() +'/' + moment(start).unix() + '/' + moment(end).unix() + categoryParams;

    return axios.get(url)
      .then(res => {

        console.log('res data from event service', res.data);

        return res.data.map(event => ({
          id: event.nid,
          title: event.name,
          start: event.time_start_calendar,
          end: event.time_end_calendar,
        }));
      });
  }

  getBranch() {
    return window.drupalSettings.path?.branch;
  }

  async updateEventOnServer(eventData) {
    try {
      console.log('Updating event on the server ...');
      const response = await axios.post('/admin/openy/schedules/update-event', eventData);

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
      console.error('Error during update:', error);
      // Обробка помилок
    }
  }
}
