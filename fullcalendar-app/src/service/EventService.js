import axios from 'axios';
import { API_BASE_URL, SCHEDULES_ENDPOINT, UPDATE_EVENT_ENDPOINT } from '@/config/apiConfig';

export default class EventService {

  async getEvents(start, end, categories = []) {
    if (start === end) {
      return [];
    }

    // Convert start and end dates to ISO strings which are effectively timestamps
    const formattedStart = start.substr(0, 19);
    const formattedEnd = end.substr(0, 19);

    const branch = encodeURIComponent(this.getBranch());
    const categoryParams = categories.length > 0 ? '/' + categories.join(',') : '';
    const url = `${API_BASE_URL}${SCHEDULES_ENDPOINT}/${branch}/${formattedStart}/${formattedEnd}${categoryParams}`;

    try {
      const response = await axios.get(url);
      return response.data.map(event => ({
        id: event.nid + '-' + Math.random().toString(16).slice(2),
        nid: event.nid,
        title: event.name,
        start: event.time_start_calendar,
        end: event.time_end_calendar,
        startGlobal: event.time_start_calendar_global,
        endGlobal: event.time_end_calendar_global,
        color: event.color,
        colorEvent: event.color,
        room: event.room,
        instructor: event.instructor,
        description: event.description,
        days: event.days,
        locationId: event.location_info.nid,
        classId: event.class_info.nid,
      }));
    } catch (error) {
      // Implement better error handling, e.g., showing error messages to the user
      throw new Error('Failed to fetch events.');
    }
  }

  getBranch() {
    return window.drupalSettings.fullCalendar?.branch || null;
  }

  async updateEventOnServer(eventData) {
    try {
      const response = await axios.post(`${API_BASE_URL}${UPDATE_EVENT_ENDPOINT}`, eventData);
      if (response.status === 200) {
        return response.data;
      }
    } catch (error) {
      throw new Error(`Failed to update the event. Please check your network connection or contact support if the problem persists. Error details: ${error.message || error}`);
    }
  }
}
