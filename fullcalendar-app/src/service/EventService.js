import axios from 'axios';
import { getTime } from 'date-fns'; // Importing formatISO to handle dates
import { API_BASE_URL, SCHEDULES_ENDPOINT, UPDATE_EVENT_ENDPOINT } from '@/config/apiConfig'; // Importing config

export default class EventService {

  async getEvents(start, end, categories = []) {
    if (start === end) {
      return [];
    }

    // Convert start and end dates to ISO strings which are effectively timestamps
    const formattedStart = Math.floor(getTime(start) / 1000);
    const formattedEnd = Math.floor(getTime(end) / 1000);

    const branch = encodeURIComponent(this.getBranch());
    const categoryParams = categories.length > 0 ? '/' + categories.join(',') : '';
    const url = `${API_BASE_URL}${SCHEDULES_ENDPOINT}/${branch}/${formattedStart}/${formattedEnd}${categoryParams}`;

    try {
      const response = await axios.get(url);
      return response.data.map(event => ({
        id: event.nid,
        title: event.name,
        start: event.time_start_calendar,
        end: event.time_end_calendar,
      }));
    } catch (error) {
      // Implement better error handling, e.g., showing error messages to the user
      throw new Error('Failed to fetch events.');
    }
  }

  getBranch() {
    return window.drupalSettings?.path?.branch || null;
  }

  async updateEventOnServer(eventData) {
    try {
      const response = await axios.post(`${API_BASE_URL}${UPDATE_EVENT_ENDPOINT}`, eventData);

      // If the request is successful, get the event id from the response
      if (response.status === 200) {
        // Show a success message
        // alert('Event saved successfully!');
        return response.data;
      }
    } catch (error) {
      throw new Error('Failed to update the event.');
    }
  }
}
