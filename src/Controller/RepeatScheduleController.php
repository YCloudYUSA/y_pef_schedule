<?php

namespace Drupal\y_pef_schedule\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Datetime\DrupalDateTime;
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
    $query->leftJoin('node__field_session_color', 'nfc', 'n.nid = nfc.entity_id');
    $query->leftJoin('node__field_session_description', 'nfd', 'n.nid = nfd.entity_id');
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
      'weekday',
    ]);

    $subquery = $this->database->select('node__field_session_time', 'nft');
    $subquery->innerJoin('paragraph__field_session_time_days', 'ptd', 'ptd.entity_id = nft.field_session_time_target_id');
    $subquery->innerJoin('paragraph__field_session_time_date', 'psd', 'psd.entity_id = nft.field_session_time_target_id');
    $subquery->addField('nft', 'entity_id', 'id');
    $subquery->addField('psd', 'field_session_time_date_value', 'start_date');
    $subquery->addField('psd', 'field_session_time_date_end_value', 'end_date');
    $subquery->addExpression('GROUP_CONCAT(ptd.field_session_time_days_value)', 'days');
    $subquery->groupBy('nft.field_session_time_target_id');
    $query->leftJoin($subquery, 'sq', 're.session = sq.id');

    $query->addField('sq', 'days');
    $query->addField('sq', 'start_date', 'time_start_calendar_global');
    $query->addField('sq', 'end_date', 'time_end_calendar_global');
    $query->addField('nfc', 'field_session_color_value', 'color');
    $query->addField('nfd', 'field_session_description_value', 'description');
    $query->addField('re', 'start', 'start_timestamp');
    $query->addField('re', 'end', 'end_timestamp');
    // Query conditions.
//    $query->distinct();

    $query->condition('re.start', $this->converUnixTime($end_date), '<=');
    $query->condition('re.end',  $this->converUnixTime($start_date), '>=');

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

    $week = $this->getWeekDate($start_date);
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

      if (!empty($item->description)) {
        $result[$key]->description = strip_tags($item->description);
      }

      $result[$key]->class_info = $classes_info[$item->class];

      $result[$key]->time_start_sort = $this->dateFormatter->format((int) $item->start_timestamp, 'custom', 'Hi');

      $tz = new \DateTimeZone(date_default_timezone_get());
      // Convert timezones for start_time and end_time.
      $time_start = new \DateTime();
      $time_start->setTimestamp($item->start_timestamp);
      $time_start->setTimezone($tz);
      $time_end = new \DateTime();
      $time_end->setTimestamp($item->end_timestamp);
      $time_end->setTimezone($tz);
      $tzUtc = new \DateTimeZone('UTC');
      $time_start_calendar_global = new \DateTime($item->time_start_calendar_global, $tzUtc);
      $time_start_calendar_global->setTimezone($tz);
    $time_end_calendar_global = new \DateTime($item->time_end_calendar_global, $tzUtc);
      $time_end_calendar_global->setTimezone($tz);

      $result[$key]->time_start_calendar_global = $time_start_calendar_global->format('Y-m-d H:i:s');
      $result[$key]->time_end_calendar_global = $time_end_calendar_global->format('Y-m-d H:i:s');

      $result[$key]->time_start = $time_start->format('g:iA');
      $result[$key]->time_end = $time_end->format('g:iA');

      // Example of calendar format 2018-08-21 14:15:00.
      $result[$key]->time_start_calendar = $week[$item->weekday] . ' ' . $this->dateFormatter->format((int) $item->start_timestamp, 'custom', 'H:i:s');
      $result[$key]->time_end_calendar = $week[$item->weekday] . ' ' . $this->dateFormatter->format((int) $item->start_timestamp + $item->duration * 60, 'custom', 'H:i:s');
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

  protected function converUnixTime($datetime) {
    $tz =\Drupal::configFactory()->get('system.date')->get('timezone')['default'];
    $date_obj = new DrupalDateTime($datetime, $tz);
    $r = $date_obj->format('U', ['timezone'=>  $tz]);
    return $r;
  }

  protected function getWeekDate($start) {
    $week = [];
    $tz = \Drupal::configFactory()->get('system.date')->get('timezone')['default'];
    $date = new DrupalDateTime($start, $tz);
    while (true) {
      $day = $date->format('N');
      if (isset($week[$day])) {
        break;
      }
      $week[$day] = $date->format('Y-m-d');
      $date->modify('+1 day');
    }
    return $week;
  }
}
