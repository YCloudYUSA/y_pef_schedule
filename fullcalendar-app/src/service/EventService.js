import axios from 'axios';
import { API_BASE_URL, SCHEDULES_ENDPOINT, UPDATE_EVENT_ENDPOINT } from '@/config/apiConfig';

export default class EventService {

  /**
   * Asynchronously fetches events from the server for the specified date range
   * and category filters. It constructs a query URL with these parameters,
   * makes a GET request to that URL, and then processes the response.
   *
   * If the start and end dates are the same, the function will immediately
   * return an empty array, as there can be no events within a zero-length
   * time span.
   *
   * On success, the function maps the raw event data from the response to a
   * structured format expected by the application. On failure, it throws an
   * error with a message indicating the action that should be taken.
   *
   * @param {string} start - The start date in ISO string format.
   * @param {string} end - The end date in ISO string format.
   * @param {Array} categories - An array of category IDs to filter events.
   * @returns {Promise<Array>} A promise that resolves to an array of event objects.
   */
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
      console.error({
        message: 'Failed to fetch events from the server.',
        action: 'Verify the network connection, check the server status, and ensure the API endpoint is correctly configured and reachable.',
        errorDetails: error.message || error,
        tip: 'Consider checking the server logs for any related error messages. If the problem persists, reach out to the backend team or the API provider for support.'
      });
      throw error;
    }
  }

  /**
   * Retrieves the 'branch' setting from the window.drupalSettings object.
   * If the 'branch' setting is not defined within the drupalSettings,
   * it will return null as the default value.
   *
   * @returns {string|null} The 'branch' setting value if available, otherwise null.
   */
  getBranch() {
    return window.drupalSettings.fullCalendar?.branch || null;
  }

  /**
   * Attempts to update an event on the server via an API call.
   * It sends the updated event data to the server using a POST request.
   * If the request is successful, the updated data from the server is returned.
   * In case of an error, it logs the error and throws an exception with details.
   *
   * @param {object} eventData - The data of the event to update.
   * @returns {Promise<object>} - The updated event data from the server.
   * @throws {Error} - If the update operation fails.
   */
  async updateEventOnServer(eventData) {
    try {
      const response = await axios.post(`${API_BASE_URL}${UPDATE_EVENT_ENDPOINT}`, eventData);
      if (response.status === 200) {
        return response.data;
      }
    } catch (error) {
      // Log detailed error information and throw an error with the message
      console.error('Error updating event:', {
        message: 'Failed to update the event on the server. An error occurred while making the POST request to the event update endpoint.',
        action: 'Check the network connection to ensure the server is reachable. Confirm that the event update endpoint is properly configured and operational.',
        errorDetails: error.message || error,
        tip: 'Review the network request to ensure it was made correctly with the expected payload. Check the server logs for any error messages related to the event update operation. If the issue is persistent, consider reaching out to the backend support team for further assistance.'
      });

      // Throw an error to be handled by the caller of this function
      throw new Error(`Failed to update the event. Please check your network connection or contact support if the problem persists. Error details: ${error.message || error}`);
    }
  }
}
