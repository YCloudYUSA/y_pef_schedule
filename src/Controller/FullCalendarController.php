<?php

declare(strict_types=1);

namespace Drupal\y_pef_schedule\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Manages calendar views and event data interactions within the FullCalendar
 * integration.
 *
 * This controller class is central to the FullCalendar integration with Drupal,
 * facilitating the retrieval and manipulation of category, and session data.
 * It serves as a bridge between the FullCalendar JavaScript library and the
 * Drupal backend, ensuring that calendar views are populated with up-to-date
 * and correctly formatted data. The class provides several key functionalities:
 *
 * - Fetching lists of events, categories, and sessions in formats suitable
 *   for use within FullCalendar views or for other client-side applications
 *   requiring structured event data.
 * - Offering endpoints for JSON responses that include detailed event
 *   information, categorized listings, and session times, thereby supporting
 *   dynamic calendar interactions such as event creation, updates, and
 *   categorization.
 * - Implementing utility methods for date conversions and database queries,
 *   streamlining the process of working with event dates and times across
 *   different time zones and formats.
 *
 * Through these capabilities, the FullCalendarController plays a crucial role
 * in enabling rich, interactive calendar features within Drupal-based
 * applications, supporting a wide range of scheduling and event management
 * scenarios.
 */
class FullCalendarController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a CommentController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * *   The configuration factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('config.factory')
    );
  }

  /**
   * Renders the calendar view with the provided branch and start date.
   *
   * @param string $branch
   *    The branch ID for which the calendar is displayed.
   *
   * @return array
   *    A render array for the calendar view.
   */
  public function calendarView(string $branch): array {
    $fullcalendar_settings = $this->configFactory->get('y_pef_schedule.settings');
    $node_storage = $this->entityTypeManager->getStorage('node');

    $build = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'fullcalendar-app',
        'class' => ['fullcalendar-app'],
      ],
      '#attached' => [
        'library' => [
          'y_pef_schedule/custom-calendar-styles',
          'y_pef_schedule/y_pef_schedule-app',
        ],
      ],
    ];
    $build['#cache']['tags'][] = 'config:y_pef_schedule.settings';

    $build['#attached']['drupalSettings']['fullCalendar'] = [
      'branch_title' => $node_storage->load($branch)?->getTitle(),
      'branch_id' => $branch,
      'slotDuration' => $fullcalendar_settings->get('slot_duration'),
      'snapDuration' => $fullcalendar_settings->get('snap_duration'),
      'slotLabelInterval' => $fullcalendar_settings->get('slot_label_interval'),
      'minTime' => $fullcalendar_settings->get('min_time'),
      'maxTime' => $fullcalendar_settings->get('max_time'),
    ];

    return $build;
  }

  /**
   * Provides a page listing all branches.
   *
   * @return array
   *    A render array for the branches list.
   */
  public function branchesListPage(): array {
    $list = $this->getBranches();

    return [
      '#theme' => 'branches_list',
      '#branches' => $list,
    ];
  }

  /**
   * Updates an existing event based on the request data.
   *
   * @param Request $request
   *    The current request object.
   *
   * @return JsonResponse
   */
  public function updateEvent(Request $request): JsonResponse {
    $event_data = $request->toArray();
    $node_storage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($event_data['nid']);
    if (isset($event_data['title']) && $event_data['title']) {
      $node->setTitle($event_data['title']);
    }

    return $this->changeSession($node, $event_data);
  }

  /**
   * Creates a new event based on the request data.
   *
   * @param Request $request
   *    The current request object.
   *
   * @return JsonResponse
   */
  public function createEvent(Request $request): JsonResponse {
    $event_data = $request->toArray();

    $node_storage = $this->entityTypeManager->getStorage('node');
    $session = $node_storage->create([
      'uid' => $this->currentUser()->id(),
      'lang' => 'und',
      'type' => 'session',
      'title' => $event_data['title'],
    ]);

    return $this->changeSession($session, $event_data);
  }

  /**
   * Changes the session details based on the provided data.
   *
   * This method updates various fields of a session entity. If an 'eventClass'
   * is provided in the data array, it updates the 'field_session_class' with
   * the 'target_id' and 'target_revision_id'. If a 'locationId' is provided,
   * it updates the 'field_session_location' with the 'target_id'. It also
   * sets the 'field_session_time' by retrieving the session time using
   * the 'getSessionTime' method. Additional session fields are updated by
   * the 'setFieldsSession' method. The session entity is then saved to persist
   * these changes.
   *
   * @param EntityInterface $session
   *   The session entity that is being updated.
   * @param array $data
   *   An associative array containing the session data to update. Expected keys
   *   are 'eventClass', 'locationId', and other keys that are processed inside
   *   the 'setFieldsSession' method.
   *
   * @return JsonResponse
   *   A JsonResponse object containing the 'id' of the updated session.
   */
  public function changeSession(EntityInterface $session, array $data): JsonResponse {
    if (isset($data['eventClass']) && $data['eventClass']) {
      $session->set('field_session_class', [
        'target_id' => $data['eventClass'],
        'target_revision_id' => $data['eventClass'],
      ]);
    }

    if (isset($data['locationId']) && $data['locationId']) {
      $session->set('field_session_location', ['target_id' => $data['locationId']]);
    }

    $session->set('field_session_time', $this->getSessionTime($session, $data));

    $this->setFieldsSession($session, $data);
    $session->save();

    $class = $session->get('field_session_class')->entity;
    $activity = $class->get('field_class_activity')->entity;
    $color = $activity->get('field_activity_color')->value ??  $this->getDefaultColor();
    return new JsonResponse(['id' => $session->id(), 'color' => $color]);
  }

  /**
   * Sets the session fields based on the provided data.
   *
   * This method iterates over a predefined list of session fields and updates
   * each field with the corresponding value from the data array, if present.
   *
   * @param EntityInterface &$session
   *   The session entity that is being updated. This parameter is passed by
   *   reference, allowing the method to modify the original entity directly.
   * @param array $data
   *   An associative array containing the new values for the session fields. The keys
   *   should match the values specified in the `$fields` array, and the values should
   *   be the new data to set for each field.
   */
  protected function setFieldsSession(EntityInterface &$session, array $data): void {
    $fields = [
      'field_session_room' => 'room',
      'field_session_instructor' => 'field_session_instructor',
      'field_session_description' => 'description',
    ];
    foreach ($fields as $key => $dataKey) {
      if (!empty($data[$dataKey])) {
        $session->set($key, $data[$dataKey]);
      }
    }
  }

  /**
   * Creates or updates a Paragraph entity to represent the session time.
   *
   * This method is responsible for handling the session time data within a
   * Paragraph entity. If the session already has a 'field_session_time',
   * it attempts to load the corresponding Paragraph entity. If the entity
   * doesn't exist or if there's no session time set, it creates a new
   * Paragraph entity of type 'session_time'.
   *
   * @param EntityInterface $session
   *   The session entity that the time is associated with.
   * @param array $scheduleData
   *   An associative array containing the schedule data for the session.
   *   Expected keys are 'days', 'startGlobal', and 'endGlobal', which
   *   represent the days of the session and its start and end times in a
   *   global timezone.
   *
   * @return array
   *   An array with two keys: 'target_id' and 'target_revision_id',
   *   representing the ID and revision ID of the session time Paragraph entity.
   */
  private function getSessionTime($session, array $scheduleData): array {
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');

    if (!$session->get('field_session_time')->getValue() || !($time_paragraph = $paragraph_storage->load($session->field_session_time->target_id))) {
      $time_paragraph = $paragraph_storage->create(['type' => 'session_time']);
      $time_paragraph->isNew();
    }

    if (isset($scheduleData['days']) && $scheduleData['days']) {
      $time_paragraph->set('field_session_time_days', explode(',', $scheduleData['days']));
    }

    $time_paragraph->set('field_session_time_date',
      [
        'value' => $this->convertDate($scheduleData['startGlobal']),
        'end_value' => $this->convertDate($scheduleData['endGlobal']),
      ]
    );

    $time_paragraph->save();

    return [
      'target_id' => $time_paragraph->id(),
      'target_revision_id' => $time_paragraph->getRevisionId(),
    ];
  }

  /**
   * Converts a date string into an ISO 8601 date format in UTC.
   *
   * This method takes a date string as input and converts it into the ISO 8601
   * format, specifically truncating the format to the first 19 characters to
   * remove any timezone or fractional second data, effectively standardizing
   * the format to 'YYYY-MM-DDTHH:MM:SS'. The conversion takes into account
   * the default timezone set in the Drupal system configuration, ensuring that
   * the input date is correctly interpreted before converting it to UTC.
   *
   * @param string $date
   *   The date string to be converted. The string should be in a format
   *   recognized by the DrupalDateTime constructor.
   *
   * @return string
   *   The formatted date string in ISO 8601 format in UTC timezone,
   *   truncated to 'YYYY-MM-DDTHH:MM:SS'.
   */
  protected function convertDate(string $date): string {
    $date_obj = new DrupalDateTime($date, \Drupal::configFactory()->get('system.date')->get('timezone')['default']);
    return substr($date_obj->format('c', ['timezone' => 'UTC']), 0, 19);
  }

  /**
   * Retrieves a list of published branches from the 'node_field_data' table.
   *
   * This method executes a database query to select node IDs (nid) and titles
   * from the 'node_field_data' table where nodes are of the 'branch' content
   * type and have a status indicating they are published. The results are
   * ordered by the title of the node to ensure a consistent and alphabetical
   * ordering of branches.
   *
   * Additionally, the query is tagged with 'openy_home_branch_get_locations'
   * and 'node_access' to ensure that any alterations or access checks specific
   * to the site's configuration or modules are applied, respecting the Drupal
   * node access control system.
   *
   * @return array
   *   An associative array where the key is the node ID (nid) of the branch,
   *   and the value is the title of the branch.
   */
  public function getBranches() {
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.status', NodeInterface::PUBLISHED);
    $query->condition('n.type', 'branch');
    $query->orderBy('n.title');
    $query->addTag('openy_home_branch_get_locations');
    $query->addTag('node_access');

    return $query->execute()->fetchAllKeyed();
  }

  /**
   * Provides a JSON response containing a list of branches.
   *
   * This method leverages the `getBranches` method to retrieve an associative
   * array of branch IDs and titles, and then encapsulates this array within a
   * JSON response.
   *
   * @return JsonResponse
   *   A JsonResponse object that contains the branches in an associative array
   *   format, where each branch ID is a key, and its corresponding title is
   *   the value.
   */
  public function getBranchesOptions(): JsonResponse {
    return new JsonResponse($this->getBranches());
  }

  /**
   * Provides a JSON response containing a list of published classes.
   *
   * This method executes a database query to select node IDs (nid) and titles
   * from the 'node_field_data' table where nodes are of the 'class' content
   * type and have a status indicating they are published.
   *
   * @return JsonResponse
   *   A JsonResponse object that contains the classes in an associative array
   *   format, where each class ID (nid) is a key, and its corresponding title
   *   is the value.
   */
  public function getClassesOptions(): JsonResponse {
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.status', NodeInterface::PUBLISHED);
    $query->condition('n.type', 'class');
    $query->orderBy('n.title');
    $query->range(0, 100);
    $res = $query->execute()->fetchAllKeyed();

    return new JsonResponse($res);
  }

  /**
   * Retrieves a list of published activity categories from the database and
   * provides them in a JSON response.
   *
   * This method queries the 'node_field_data' table to select node IDs (nid)
   * and titles for nodes of the 'activity' content type that are published.
   *
   * @return JsonResponse
   *   A JsonResponse object containing an array of categories. Each category
   *   is represented as an associative array with 'name' and 'color' keys,
   *   where 'name' corresponds to the category title and 'color' is a
   *   placeholder value.
   */
  public function getCategories(): JsonResponse {
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.status', NodeInterface::PUBLISHED);
    $query->leftJoin('node__field_activity_color', 'nc', 'nc.entity_id = n.nid');
    $query->addField('nc', 'field_activity_color_value', 'color');
    $query->condition('n.type', 'activity');
    $query->orderBy('n.title');
    $result = $query->execute();

    $categories = [];
    foreach ($result as $record) {
      // TODO: We assume that the color will be determined later, so for now we put a placeholder.

      $categories[] = [
        'name' => $record->title,
        'color' => $record->color ?? $this->getDefaultColor(),
      ];
    }

    return new JsonResponse($categories);
  }

  /**
   * Get default color event if color not set in activity node
   *
   * @return string color event
   */
  public static function getDefaultColor() {
    return '#3788d8';
  }

}
