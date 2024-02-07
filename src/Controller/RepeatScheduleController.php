<?php

namespace Drupal\y_pef_schedule\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\openy_repeat\Controller\RepeatController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * {@inheritdoc}
 */
class RepeatScheduleController extends RepeatController {

  /**
   * {@inheritdoc}
   */
  public function ajaxSchedulerByDateRange(Request $request, $location, $start, $end, $categories) {
    $result = $this->getDateRangeData($request, $location, $start, $end, $categories);
    return new JsonResponse($result);
  }

  /**
   * @TODO: Add query tag and alter.
   * Gets events data for given location, date, category, instructor or class.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $location
   * @param string $date
   * @param string $category
   * @param string $instructor
   * @param string $class
   *
   * @return array
   */
  public function getDateRangeData($request, $location, $start_date, $end_date, $categories) {
    $query = $this->database->select('repeat_event', 're');
    $query->leftJoin('node', 'n', 're.session = n.nid');
    $query->innerJoin('node_field_data', 'nd', 're.location = nd.nid');
    $query->innerJoin('node_field_data', 'nds', 'n.nid = nds.nid');
    $query->addField('n', 'nid');
    $query->addField('nd', 'title', 'location');
    $query->addField('nds', 'title', 'name');
    $query->fields('re', [
      'class',
      'session',
      'room',
      'instructor',
      'category',
      'register_url',
      'register_text',
      'duration',
      'productid',
    ]);
    $query->addField('re', 'start', 'start_timestamp');
    $query->addField('re', 'end', 'end_timestamp');
    // Query conditions.
    $query->distinct();

    $query->condition('re.start', $end_date, '<=');
    $query->condition('re.end', $start_date, '>=');

    if (!empty($categories)) {
      $query->condition('re.category', explode(',', $categories), 'IN');
    }
    if (!empty($location)) {
      $query->condition('nd.title', explode(';', rawurldecode($location)), 'IN');
    }
    $exclusions = $request->get('excl');
    if (!empty($exclusions)) {
      $query->condition('re.category', explode(';', $exclusions), 'NOT IN');
    }
    $limit = $request->get('limit');
    if (!empty($limit)) {
      $query->condition('re.category', explode(';', $limit), 'IN');
    }
    if (!empty($instructor)) {
      $query->condition('re.instructor', $instructor);
    }
    if (!empty($class)) {
      $query->condition('re.class', $class);
    }
    $result = $query->execute()->fetchAll();

    $locations_info = $this->getLocationsInfo();

    $classesIds = [];
    foreach ($result as $key => $item) {
      $classesIds[$item->class] = $item->class;
    }
    $classes_info = $this->getClassesInfo($classesIds);

    $class_name = [];
    foreach ($result as $key => $item) {
      $result[$key]->location_info = $locations_info[$item->location];

      if (isset($classes_info[$item->class]['path'])) {
        $query = UrlHelper::buildQuery([
          'location' => $locations_info[$item->location]['nid'],
        ]);
        if (!in_array($item->name, $class_name)) {
          $classes_info[$item->class]['path'] .= '?' . $query;
          $class_name[] = html_entity_decode($item->name);
        }
      }

      $result[$key]->class_info = $classes_info[$item->class];

      $result[$key]->time_start_sort = $this->dateFormatter->format((int) $item->start_timestamp, 'custom', 'Hi');

      // Convert timezones for start_time and end_time.
      $time_start = new \DateTime();
      $time_start->setTimestamp($item->start_timestamp);
      $time_start->setTimezone(new \DateTimeZone(date_default_timezone_get()));
      $time_end = new \DateTime();
      $time_end->setTimestamp($item->end_timestamp);
      $time_end->setTimezone(new \DateTimeZone(date_default_timezone_get()));
      $result[$key]->time_start = $time_start->format('g:iA');
      $result[$key]->time_end = $time_end->format('g:iA');

      // Example of calendar format 2018-08-21 14:15:00.
      $result[$key]->time_start_calendar = $this->dateFormatter->format((int) $item->start_timestamp, 'custom', 'Y-m-d H:i:s');
      $result[$key]->time_end_calendar = $this->dateFormatter->format((int) $item->start_timestamp + $item->duration * 60, 'custom', 'Y-m-d H:i:s');
      $result[$key]->timezone = date_default_timezone_get();

      // Durations.
      $result[$key]->duration_minutes = $item->duration % 60;
      $result[$key]->duration_hours = ($item->duration - $result[$key]->duration_minutes) / 60;
    }

    usort($result, function ($item1, $item2) {
      if ((int) $item1->time_start_sort == (int) $item2->time_start_sort) {
        return 0;
      }
      return (int) $item1->time_start_sort < (int) $item2->time_start_sort ? -1 : 1;
    });

    return $result;
  }

}
