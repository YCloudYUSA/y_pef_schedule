<?php

namespace Drupal\y_pef_schedule\Controller;

use DateTimeZone;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\openy_repeat\Controller\RepeatController;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Manages scheduling and event data for repeated occurrences in a calendar
 * system.
 *
 * This controller extends the `RepeatController` to provide specialized
 * handling of scheduling information, particularly focusing on events that
 * occur on a repeating basis. It includes functionality for fetching event
 * data within specific date ranges, processing and formatting this data for
 * use in scheduling views, and handling AJAX requests to dynamically update
 * scheduling information based on user interactions or specified criteria.
 *
 * Key functionalities include:
 * - Retrieving detailed event information filtered by date range, location,
 *   and categories, making it suitable for generating comprehensive scheduling
 *   overviews.
 * - Converting date and time information between time zones and formats to
 *   ensure consistency and usability across different locales and user
 *   preferences.
 * - Dynamically responding to AJAX requests for scheduling information,
 *   enabling real-time updates to scheduling views without requiring page
 *   reloads.
 *
 * Methods such as `getDateRangeData`, `processResult`, `joinTables`, and
 * others work together to query, aggregate, and format the necessary data from
 * the database. This controller thus serves as a crucial component in
 * applications requiring detailed and flexible handling of repeating events and
 * schedules.
 */
class RepeatScheduleController extends RepeatController {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    $instance->logger = $container->get('logger.factory')->get('y_pef_schedule');
    return $instance;
  }

  /**
   * Retrieves event data within a specified date range, filtered by location
   * and categories.
   *
   * This method constructs a database query to fetch data for repeat events
   * from the 'repeat_event' table. It dynamically joins additional tables,
   * adds necessary fields, and applies filtering conditions based on
   * the input parameters: start date, end date, categories, and location.
   * The method is designed to provide comprehensive event data suitable for
   * rendering events in calendar views or for other scheduling purposes.
   *
   * The query execution results are then processed to format the data
   * appropriately for the consuming client, adjusting the date to the
   * beginning of the week if necessary to align with calendar week views.
   *
   * @param Request $request
   *   The current request object, potentially containing additional parameters
   *   or filters to apply to the query.
   * @param string $location
   *   The location identifier to filter the events by.
   * @param string $start_date
   *   The start date of the date range.
   * @param string $end_date
   *   The end date of the date range.
   * @param string $categories
   *   A comma-separated list of category identifiers to filter the events.
   *   This allows for retrieval of events that fall into specific categories.
   *
   * @return array
   *   An array of events within the specified date range.
   * @throws \Exception
   */
  public function getDateRangeData(Request $request, string $location, string $start_date, string $end_date, string $categories): array {
    $query = $this->database->select('repeat_event', 're');
    $this->joinTables($query);
    $this->addFields($query);
    $this->addCondition($query, $start_date, $end_date, $categories, $location, $request);
    $result = $query->execute()->fetchAll();

    // If the number of results is greater than 1000, log a warning.
    if (count($result) > 1000) {
      $this->logger->warning('Large date range selection: @count results fetched for location @location from @start_date to @end_date with categories @categories.', [
        '@count' => count($result),
        '@location' => $location,
        '@start_date' => $start_date,
        '@end_date' => $end_date,
        '@categories' => $categories,
      ]);
    }
    // If the number of results exceeds 5000, throw an exception.
    if (count($result) > 5000) {
      throw new \Exception("Excessive amount of data: More than 5000 results fetched. Please narrow down your selection criteria.");
    }

    return $this->processResult($result, $this->getWeekDate($start_date));
  }

  /**
   * Processes the results from a database query to enrich event data with
   * additional details.
   *
   * This method takes a set of event records and augments them with location
   * information, class information, and formatted date/time values. It fetches
   * location and class information based on the IDs present in the result set,
   * thus minimizing database queries. It also formats the start and end times
   * of each event for display in different timezones and formats,
   * incorporating the week's date for calendar-based views. Additionally, it
   * ensures descriptions are HTML-safe.
   *
   * @param array $result
   *   An array of stdClass objects representing event records from the
   *   database.
   * @param array $week
   *   An associative array mapping weekdays to their respective dates for the
   *   week of interest.
   *
   * @return array
   *   An enriched array of event data, with added location info, class info,
   *   and formatted dates.
   *
   * @throws \Exception
   *  Throws an exception if date conversion fails.
   */
  protected function processResult(array $result, array $week): array {
    $locations_info = $this->getLocationsInfo();
    $classesIds = array_column($result, 'class');
    $classes_info = $this->getClassesInfo(array_unique($classesIds));

    $tz_default = new \DateTimeZone(date_default_timezone_get());
    $tz_utc = new \DateTimeZone('UTC');

    $class_name = [];
    foreach ($result as $key => $item) {
      $item->location_info = $locations_info[$item->location] ?? null;
      $classPath = $classes_info[$item->class]['path'] ?? '';

      if ($classPath && !in_array($item->name, $class_name)) {
        $query = UrlHelper::buildQuery(['location' => $item->location_info['nid']]);
        $classes_info[$item->class]['path'] = $classPath . '?' . $query;
        $class_name[] = html_entity_decode($item->name);
      }

      $item->description = strip_tags($item->description ?? '');
      $item->class_info = $classes_info[$item->class] ?? null;

      $item->color = $item->color ?? FullCalendarController::getDefaultColor();
      // Simplify date conversion by creating a helper method if repeated logic
      $item->time_start_calendar_global = $this->convertDate($item->time_start_calendar_global, $tz_utc, $tz_default, 'Y-m-d H:i:s');
      $item->time_end_calendar_global = $this->convertDate($item->time_end_calendar_global, $tz_utc, $tz_default, 'Y-m-d H:i:s');
      $item->time_start = $this->convertDate($item->start, $tz_utc, $tz_default, 'g:iA');
      $item->time_end = $this->convertDate($item->end, $tz_utc, $tz_default, 'g:iA');
      // Adjust the calculation for end time to correctly reflect the duration
      $item->time_start_calendar = $week[$item->weekday] . ' ' . $this->convertDate($item->start, $tz_utc, $tz_default, 'H:i:s');
      $item->time_end_calendar = $week[$item->weekday] . ' ' . $this->convertDate($item->start + $item->duration * 60, $tz_utc, $tz_default, 'H:i:s');
      $item->timezone = date_default_timezone_get();
    }

    return $result;
  }

  /**
   * Adds table joins to a select query for fetching event data.
   *
   * This method augments a given select query with a series of JOIN operations
   * to pull related information from various node and field tables.
   *
   * Specifically, it performs the following actions:
   * - Left joins the 'node' table to include session node data.
   * - Inner joins the 'node_field_data' table twice: once for location data
   *   (alias 'nd') and once for session node titles (alias 'nds').
   * - Left joins the 'node__field_session_color' and
   *   'node__field_session_description' tables to include session color and
   *   description data, respectively.
   * Additionally, it calls the `addSubjoin` method to apply any
   * subclass-specific joins.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query object to which the joins will be added.
   *
   * @return void
   */
  protected function joinTables(SelectInterface &$query): void {
    $query->leftJoin('node', 'n', 're.session = n.nid');
    $query->innerJoin('node_field_data', 'nd', 're.location = nd.nid');
    $query->innerJoin('node_field_data', 'nds', 'n.nid = nds.nid');
    $query->leftJoin('node__field_session_description', 'nfd', 'n.nid = nfd.entity_id');
    $query->leftJoin('node__field_class_activity', 'nfca', 'nfca.entity_id = re.class');
    $query->leftJoin('node__field_activity_color', 'nfac', 'nfca.field_class_activity_target_id  = nfac.entity_id');
    $this->addSubjoin($query);
  }

  /**
   * Enhances a select query with a subquery join for session time data.
   *
   * After constructing the subquery, it's left joined to the main query on
   * the session ID, allowing the main query to access the aggregated session
   * time information as part of its result set.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query object to which the subquery join will be added.
   *
   * @return void
   */
  protected function addSubjoin(SelectInterface $query): void {
    $subquery = $this->database->select('node__field_session_time', 'nft');
    $subquery->innerJoin('paragraph__field_session_time_days', 'ptd', 'ptd.entity_id = nft.field_session_time_target_id');
    $subquery->innerJoin('paragraph__field_session_time_date', 'psd', 'psd.entity_id = nft.field_session_time_target_id');

    // Assuming 'nft.field_session_time_target_id' uniquely identifies each
    // 'nft.entity_id', and thus, each set of fields you're selecting, we
    // include all the fields directly. This does not change the logic but
    // makes it compliant with ONLY_FULL_GROUP_BY.
    $subquery->addField('nft', 'entity_id', 'id');
    $subquery->addField('psd', 'field_session_time_date_value', 'start_date');
    $subquery->addField('psd', 'field_session_time_date_end_value', 'end_date');
    $subquery->addExpression('GROUP_CONCAT(DISTINCT ptd.field_session_time_days_value ORDER BY ptd.field_session_time_days_value)', 'days');
    $subquery->groupBy('nft.field_session_time_target_id');

    // Important: Also group by the additional fields to be fully compliant.
    $subquery->groupBy('nft.entity_id');
    $subquery->groupBy('psd.field_session_time_date_value');
    $subquery->groupBy('psd.field_session_time_date_end_value');

    $query->leftJoin($subquery, 'sq', 're.session = sq.id');
  }

  /**
   * Adds fields to be selected from the database in a structured query.
   *
   * This method specifies the fields to be retrieved from various tables in
   * the database query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query object being constructed.
   *
   * @return void
   */
  protected function addFields(SelectInterface &$query): void {
    $query->addField('n', 'nid');
    $query->addField('nd', 'title', 'location');
    $query->addField('nds', 'title', 'name');
    $query->addField('sq', 'days');
    $query->addField('sq', 'start_date', 'time_start_calendar_global');
    $query->addField('sq', 'end_date', 'time_end_calendar_global');
    $query->addField('nfac', 'field_activity_color_value', 'color');
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
   * Applies filtering conditions to a database query based on input parameters.
   *
   * This method refines the query object by adding conditions that filter
   * the results according to specified start and end dates, categories, and
   * location. It ensures that only events within the given date range and
   * matching the specified categories and location are selected. Additionally,
   * it handles request-specific parameters for excluding certain categories or
   * applying a limit to the category selection.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query object to be modified with additional conditions.
   * @param string $start_date
   *   The start date for the event search, in a string format recognized by DateTime.
   * @param string $end_date
   *   The end date for the event search, in a string format recognized by DateTime.
   * @param string $categories
   *   A comma-separated list of category IDs to include in the search.
   * @param string $location
   *   A semicolon-separated list of location titles to include in the search.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object, potentially containing 'excl' (exclusions) and 'limit' parameters for further refining the query.
   *
   * @return void
   */
  protected function addCondition(SelectInterface &$query, string $start_date, string $end_date, string $categories, string $location, Request $request): void {
    // Define the default and UTC timezones
    $tz_default = new \DateTimeZone(date_default_timezone_get());
    $tz_utc = new \DateTimeZone('UTC');

    // Convert start and end dates to UTC and compare against event start/end times
    $query->condition('re.start', $this->convertDate($end_date, $tz_default, $tz_utc, 'U'), '<=');
    $query->condition('re.end', $this->convertDate($start_date, $tz_default, $tz_utc, 'U'), '>=');

    // Apply category and location filters if provided
    if (!empty($categories)) {
      $query->condition('re.category', explode(',', $categories), 'IN');
    }
    if (!empty($location)) {
      $query->condition('nd.nid', $location);
    }

    // Handle exclusions and limits from the request
    $exclusions = $request->get('excl');
    if (!empty($exclusions)) {
      $query->condition('re.category', explode(';', $exclusions), 'NOT IN');
    }
    $limit = $request->get('limit');
    if (!empty($limit)) {
      $query->condition('re.category', explode(';', $limit), 'IN');
    }

    $query->isNotNull('sq.start_date');
    $query->isNotNull('sq.end_date');
  }

  /**
   * Generates an associative array mapping each day of the week to its date,
   * starting from a given date.
   *
   * This method calculates the dates for a complete week starting from the
   * specified start date. It iterates through each day of the week starting
   * from the given date, creating an associative array where the keys are the
   * day of the week numbers (1 for Monday through 7 for Sunday) and the values
   * are the corresponding dates in 'Y-m-d' format. The iteration stops once it
   * cycles back to the starting day of the week, ensuring each day of the week
   * is represented exactly once.
   *
   * The method uses the configured default timezone from Drupal's system settings to accurately
   * handle the date calculations.
   *
   * @param string $start
   *   The start date from which the week's dates are to be calculated.
   *   The date should be in a format recognized by DrupalDateTime, such
   *   as 'Y-m-d'.
   *
   * @return array
   *   An associative array mapping the day of the week numbers to their
   *   respective dates for the week starting from the given date. For example:
   *   [
   *     1 => '2023-01-02', // Monday
   *     2 => '2023-01-03', // Tuesday, etc.
   *     ...
   *     7 => '2023-01-08'  // Sunday
   *   ]
   */
  protected function getWeekDate(string $start): array {
    $week = [];
    $tz = $this->configFactory->get('system.date')->get('timezone')['default'];
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
   * Converts a given date from one timezone to another and formats it
   * according to the specified pattern.
   *
   * This method takes a date input, which can be in ISO format or as a Unix
   * timestamp, and converts it from the original timezone to the target
   * timezone. It then formats the date according to the specified format
   * string. This utility function is versatile, supporting both string and
   * numeric date inputs, making it suitable for various date conversion needs
   * within the application. It can be particularly useful for displaying dates
   * to users in their local timezone or for converting dates between different
   * timezones for processing.
   *
   * @param int|string $date
   *   The date to convert, provided either as an ISO 8601 date string or a Unix timestamp.
   * @param \DateTimeZone $tzFrom
   *   The timezone from which to convert the date. This is where the date is currently set.
   * @param \DateTimeZone $tzTo
   *   The target timezone to which the date will be converted.
   * @param string $format
   *   The format string to apply to the date after conversion, according to PHP's date() function syntax.
   *
   * @return string
   *   The converted date formatted as specified by the $format parameter.
   *
   * @throws \Exception
   *   If the provided date string or timestamp is invalid, an exception may be
   *   thrown during the creation of the \DateTime object.
   */
  protected function convertDate(int|string $date, DateTimeZone $tzFrom, DateTimeZone $tzTo, string $format): string {
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

  /**
   * Handles AJAX requests for scheduling information within a specified date
   * range.
   *
   * @param Request $request
   *   The current request object, encapsulating all HTTP request data.
   * @param string $location
   *   The location identifier used to filter events.
   * @param string $start
   *   The start date of the date range for which scheduling information is
   *   requested, in 'Y-m-d' format.
   * @param string $end
   *   The end date of the date range, inclusive, also in 'Y-m-d' format.
   * @param string $categories
   *   A comma-separated list of category identifiers to further filter the
   *   events.
   *
   * @return JsonResponse
   *   A JsonResponse object containing the filtered scheduling information.
   *   The structure of the response data depends on the implementation of
   *   `getDateRangeData` but typically includes event details such as titles,
   *   dates, locations, and categories.
   */
  public function ajaxSchedulerByDateRange(Request $request, string $location, string $start, string $end, string $categories): JsonResponse {
    $result = $this->getDateRangeData($request, $location, $start, $end, $categories);
    return new JsonResponse($result);
  }

}
