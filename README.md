# Y PEF Schedule

The Y PEF Schedule module provides a calendar functionality for scheduling events.
It includes a Vue.js component, `fullcalendar-app`, to display and interact with the calendar.

### Requirements

* [Drupal](https://www.drupal.org/project/drupal)
* [Open Y Repeat](https://github.com/ynorth-projects/openy_repeat)
* [Vue.js 3](https://vuejs.org/)
* [Axios-HTTP](https://axios-http.com/)
* [FullCalendar](https://fullcalendar.io/)

## Installation

```shell
composer require ycloudyusa/y_pef_schedule
drush en y_pef_schedule
```

1. Install as you would normally install a contributed Drupal module. For further information, see
   [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).
2. Enable the module by navigating to **Admin** > **Extend** (`/admin/modules`) in your Drupal admin interface, then enabling "Y PEF Schedules Admin tool"

## Configuration

1. Configure the calendar settings at **Admin** > **YMCA Website Services** > **Settings** > **Schedules calendar settings** (`/admin/openy/settings/schedules-calendar`)
2. Go to **Admin** > **Content** > **Schedules Calendar** (`/admin/openy/branch-schedules`) and select a branch.

After choosing a branch, you can view the calendar. The calendar features include:

1. Viewing events in weekly or daily format.
2. Viewing the main information of the event (by clicking on the event).
3. Creating a new event (using the Session Content Type).
4. Updating existing events.
5. Downloading the schedule in PDF format.
6. Filtering results by categories.

### Customization

A few options are available for advanced customization of the calendar.

#### Retrieving Events

The module provides controllers to handle AJAX requests for fetching events. To create a custom request, use the following route in your JavaScript code:

```javascript
axios.get('/fullcalendar-api/get-event-data-date-range/{location}/{start}/{end}/{category}')
  .then(response => {
    const events = response.data;
    // Process the received events as needed
  })
  .catch(error => {
    console.error('Error fetching events:', error);
  });
```
Replace `{location}`, `{start}`, `{end}`, and `{category}` with the appropriate values.

#### Creating Events

The `fullcalendar-app` component allows users to create events interactively. When a date is clicked, a modal form is displayed for users to enter event details such as title, category, time, and date.

To customize the form or extend the functionality, refer to the [Vue.js component documentation](https://fullcalendar.io/docs/vue) and customize the handleDateClick and createEvent methods in the fullcalendar-app component.

## Troubleshooting

### Known issues

1. After creating a series of events, it is created, but only one event is displayed in the calendar, the page must be refreshed to see the correct data
2. The color is fixed to the session and not to the category
3. PDF format is A3
