// apiConfig.js

const API_BASE_URL = window.location.origin;
const SCHEDULES_ENDPOINT = '/schedules/get-event-data-date-range';
const UPDATE_EVENT_ENDPOINT = '/admin/openy/branch-schedules/update-event';
const CREATE_EVENT_ENDPOINT = '/admin/openy/branch-schedules/create-event';

export {
  API_BASE_URL,
  SCHEDULES_ENDPOINT,
  UPDATE_EVENT_ENDPOINT,
  CREATE_EVENT_ENDPOINT,
};
