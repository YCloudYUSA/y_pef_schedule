// dateUtils.js

/**
 * Converts a JavaScript Date object to an ISO 8601 formatted string without
 * the timezone part.
 *
 * @param {Date} date - The Date object to format.
 * @returns {string} The formatted date-time string.
 */
export function formatDateTimeLocal(date) {
  return date.toISOString().slice(0, 19);
}

/**
 * Updates the browser's URL by setting new query parameters for start date.
 * This function modifies the history state without causing a page reload.
 *
 * @param {string} start - The start date string to set in the URL.
 */
export function updateUrlParams(start) {
  const currentUrl = window.location.href;
  const url = new URL(currentUrl);

  url.searchParams.set('start', start);

  window.history.replaceState({}, '', url.toString());
}
