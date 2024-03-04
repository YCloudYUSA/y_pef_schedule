<?php

namespace Drupal\y_pef_schedule\Controller;

use DateTimeZone;
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
    $this->joinTables($query);
    $this->addFields($query);
    $this->addCondition($query, $start_date, $end_date, $categories, $location, $request);
    $result = $query->execute()->fetchAll();

    return $this->proccessResult($result, $this->getWeekDate($start_date));
  }

  /**
   * @param array $result
   *   Result from database query
   * @param array $week
   *   Date of the searching week.
   *
   * @return array
   *   Result events.
   *
   * @throws \Exception
   */
  protected function proccessResult($result, $week) {

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

      $result[$key]->description = strip_tags($item->description ?? '');
      $result[$key]->class_info = $classes_info[$item->class];

      $tz_default = new \DateTimeZone(date_default_timezone_get());
      $tz_utc = new \DateTimeZone('UTC');

      $result[$key]->time_start_calendar_global = $this->convertDate($item->time_start_calendar_global, $tz_utc, $tz_default, 'Y-m-d H:i:s');
      $result[$key]->time_end_calendar_global = $this->convertDate($item->time_end_calendar_global, $tz_utc, $tz_default, 'Y-m-d H:i:s');
      $result[$key]->time_start = $this->convertDate($item->start, $tz_utc, $tz_default, 'g:iA');
      $result[$key]->time_end = $this->convertDate($item->end, $tz_utc, $tz_default, 'g:iA');
      $result[$key]->time_start_calendar = $week[$item->weekday] . ' ' . $this->convertDate($item->start, $tz_utc, $tz_default, 'H:i:s');
      $result[$key]->time_end_calendar = $week[$item->weekday] . ' ' . $this->convertDate($item->start + $item->duration * 60, $tz_utc, $tz_default, 'H:i:s');
      $result[$key]->timezone = date_default_timezone_get();
    }

    return $result;
  }

  /**
   * Join table to query
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select Query object.
   * @return void
   */
  protected function joinTables(&$query): void {
    $query->leftJoin('node', 'n', 're.session = n.nid');
    $query->innerJoin('node_field_data', 'nd', 're.location = nd.nid');
    $query->innerJoin('node_field_data', 'nds', 'n.nid = nds.nid');
    $query->leftJoin('node__field_session_color', 'nfc', 'n.nid = nfc.entity_id');
    $query->leftJoin('node__field_session_description', 'nfd', 'n.nid = nfd.entity_id');
    $this->addSubjoin($query);
  }

  /**
   * Add subjoin to query
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select Query object.
   * @return void
   */
  protected function addSubjoin($query): void {
    $subquery = $this->database->select('node__field_session_time', 'nft');
    $subquery->innerJoin('paragraph__field_session_time_days', 'ptd', 'ptd.entity_id = nft.field_session_time_target_id');
    $subquery->innerJoin('paragraph__field_session_time_date', 'psd', 'psd.entity_id = nft.field_session_time_target_id');
    $subquery->addField('nft', 'entity_id', 'id');
    $subquery->addField('psd', 'field_session_time_date_value', 'start_date');
    $subquery->addField('psd', 'field_session_time_date_end_value', 'end_date');
    $subquery->addExpression('GROUP_CONCAT(ptd.field_session_time_days_value)', 'days');
    $subquery->groupBy('nft.field_session_time_target_id');
    $query->leftJoin($subquery, 'sq', 're.session = sq.id');
  }

  /**
   * Select fields.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select Query object.
   * @return void
   */
  protected function addFields(&$query) {
    $query->addField('n', 'nid');
    $query->addField('nd', 'title', 'location');
    $query->addField('nds', 'title', 'name');
    $query->addField('sq', 'days');
    $query->addField('sq', 'start_date', 'time_start_calendar_global');
    $query->addField('sq', 'end_date', 'time_end_calendar_global');
    $query->addField('nfc', 'field_session_color_value', 'color');
    $query->addField('nfd', 'field_session_description_value', 'description');
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
      'start',
      'end',
    ]);
  }

  /**
   * Add condition to query
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select Query object.
   * @return void
   */
  protected function addCondition(&$query, $start_date, $end_date, $categories, $location, $request) {
    $tz_default = new \DateTimeZone(date_default_timezone_get());
    $tz_utc = new \DateTimeZone('UTC');
    $query->condition('re.start', $this->convertDate($end_date, $tz_default, $tz_utc, 'U'), '<=');
    $query->condition('re.end', $this->convertDate($start_date, $tz_default, $tz_utc, 'U'), '>=');

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
  }

  /**
   * @param string $start
   *    Start date.
   * @return array
   *   Days of the week from start date
   */
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

  /**
   * Convert date to user format and change timezone.
   *
   * @param string $date
   *    Date iso format or unix time
   * @param DateTimeZone $tzFrom
   *   Convert from Timezone
   * @param DateTimeZone $tzTo
   *   Convert to Timezone
   * @param string $format
   *   Format date
   * @return string
   *   Date format
   * @throws \Exception
   */
  protected function convertDate($date, $tzFrom, $tzTo, $format) {
    if (is_numeric($date)) {
      $date_object = new \DateTime();
      $date_object->setTimestamp($date);
    }
    else {
      $date_object = new \DateTime($date, $tzFrom);
    }

    $date_object->setTimezone($tzTo);
    return $date_object->format($format);
  }

}
