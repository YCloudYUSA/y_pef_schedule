// apiConfig.js

const API_BASE_URL = window.location.origin + (window.drupalSettings.path.baseUrl ?? '/');
const SCHEDULES_ENDPOINT = API_BASE_URL + 'fullcalendar-api/get-event-data-date-range';
const UPDATE_EVENT_ENDPOINT = API_BASE_URL + 'fullcalendar-api/update-event';
const CREATE_EVENT_ENDPOINT = API_BASE_URL + 'fullcalendar-api/create-event';
const GET_CLASSES_OPTIONS = API_BASE_URL + 'fullcalendar-api/get-classes-options';
const GET_BRANCHES_OPTIONS = API_BASE_URL + 'fullcalendar-api/get-branches-options';
const GET_SCHEDULES_CATEGORIES = API_BASE_URL + 'fullcalendar-api/get-schedules-categories';

export {
  API_BASE_URL,
  SCHEDULES_ENDPOINT,
  UPDATE_EVENT_ENDPOINT,
  CREATE_EVENT_ENDPOINT,
  GET_CLASSES_OPTIONS,
  GET_BRANCHES_OPTIONS,
  GET_SCHEDULES_CATEGORIES,
};
