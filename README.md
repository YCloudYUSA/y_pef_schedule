# Y PEF Schedule

The Y PEF Schedule module provides a calendar functionality for scheduling events.
It includes a Vue.js component, `fullcalendar-app`, to display and interact with the calendar.

## Installation

1. Download and install the module in your Drupal project.
2. Enable the module by navigating to `Admin > Extend` in your Drupal admin interface.
3. Go to the `/admin/openy/branch-schedules` and select a branch.

## Usage
After choosing a brunch, you can view the calendar, the main features of the calendar:
1. View events in weekly or daily format
2. Viewing the main information of the event (clicking on the event)
3. Creating a new event (content type session)
4. Update of existing events
5. Downloading the schedule in PDF format
6. Filtering results by categories

### Issues or things worth knowing (TUDUS):
1. After creating a series of events, it is created, but only one event is displayed in the calendar, the page must be refreshed to see the correct data
2. The color is fixed to the session and not to the category
3. PDF format is A3

### Retrieving Events
The module provides controllers to handle AJAX requests for fetching events. Use the following route in your JavaScript code:

```javascript
axios.get('/schedules/get-event-data-date-range/{location}/{start}/{end}/{category}')
  .then(response => {
    const events = response.data;
    // Process the received events as needed
  })
  .catch(error => {
    console.error('Error fetching events:', error);
  });
```
Replace {location}, {start}, {end}, and {category} with the appropriate values.

### Creating Events
The fullcalendar-app component allows users to create events interactively. When a date is clicked, a modal form is displayed for users to enter event details such as title, category, time, and date.

To customize the form or extend the functionality, refer to the Vue.js component documentation and customize the handleDateClick and createEvent methods in the fullcalendar-app component.

### Requirements
* Drupal 9
* Open Y Repeat
* Vue.js 3
* Axios
* FullCalendar

