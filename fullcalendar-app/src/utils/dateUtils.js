// dateUtils.js

/**
 * Converts a JavaScript Date object to an ISO 8601 formatted string without the timezone part.
 * This format (YYYY-MM-DDTHH:mm:ss) is often used for <input type="datetime-local"> HTML elements.
 *
 * @param {Date} date - The Date object to format.
 * @returns {string} The formatted date-time string.
 */
export function formatDateTimeLocal(date) {
  console.log('date', date); // Log the date for debugging purposes; consider removing for production
  return date.toISOString().slice(0, 19);
}

/**
 * Updates the browser's URL by setting new query parameters for start and end dates.
 * This function modifies the history state without causing a page reload.
 *
 * @param {string} start - The start date string to set in the URL.
 * @param {string} end - The end date string to set in the URL.
 */
export function updateUrlParams(start, end) {
  const currentUrl = window.location.href;
  const url = new URL(currentUrl);

  url.searchParams.set('start', start);
  url.searchParams.set('end', end);

  window.history.replaceState({}, '', url.toString());
}
