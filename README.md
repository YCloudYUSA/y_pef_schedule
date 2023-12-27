# Y PEF Schedules tools

The Y PEF Schedule module provides a calendar functionality for scheduling events. It includes a Vue.js component, `fullcalendar-app`, to display and interact with the calendar.

## Installation

1. Download and install the module in your Drupal project.
2. Enable the module by navigating to `Admin > Extend` in your Drupal admin interface.

## Usage

### Displaying the Calendar

To display the calendar, create a new page and use the provided controller:

```php
public function content() {
  return [
    '#theme' => 'y_pef_schedule_calendar',
    '#attached' => [
      'library' => [
        'y_pef_schedule/fullcalendar-app',
      ],
    ],
  ];
}
```
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
Drupal 9
Vue.js
Axios
FullCalendar
vue2-timepicker
vuejs-datepicker
