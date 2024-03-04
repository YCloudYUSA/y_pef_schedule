<?php

declare(strict_types=1);

namespace Drupal\y_pef_schedule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles calendar views and event management for the FullCalendar integration.
 */
class FullCalendarController extends ControllerBase {

  /**
   * Renders the calendar view with the provided branch and start date.
   *
   * @param string $branch
   *    The branch ID for which the calendar is displayed.
   * @param string $start_date
   *    The start date for calendar events.
   *
   * @return array
   *    A render array for the calendar view.
   */
  public function calendarView($branch, $start_date): array {
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
    $build['#attached']['drupalSettings']['path']['branch'] = $branch;

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
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($event_data['nid']);
    if ($event_data['title']) {
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

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $session = $node_storage->create([
      'uid' => \Drupal::currentUser()->id(),
      'lang' => 'und',
      'type' => 'session',
      'title' => $event_data['title'],
    ]);

    return $this->changeSession($session, $event_data);
  }

  /**
   * Set fields session.
   *
   * @param array $data
   *    The event data.
   *
   * @return JsonResponse
   */
  public function changeSession($session, array $data): JsonResponse {
    if ($data['eventClass']) {
      $session->set('field_session_class', [
        'target_id' => $data['eventClass'],
        'target_revision_id' => $data['eventClass'],
      ]);
    }

    if ($data['locationId']) {
      $session->set('field_session_location', ['target_id' => $data['locationId']]);
    }

    $session->set('field_session_time', $this->getSessionTime($session, $data));

    $this->setFieldsSession($session, $data);
    $session->save();

    return new JsonResponse(['id' => $session->id()]);
  }

  protected function setFieldsSession(&$session, $data) {
    $fields = [
      'field_session_room' => 'room',
      'field_session_instructor' => 'field_session_instructor',
      'field_session_description' => 'description',
      'field_session_color' => 'colorEvent',
    ];
    foreach ($fields as $key => $dataKey) {
      if (!empty($data[$dataKey])) {
        $session->set($key, $data[$dataKey]);
      }
    }
  }

  /**
   * Creates a Paragraph entity for session time.
   *
   * @param array $scheduleData
   *    The schedule data including start and end times.
   *
   * @return array
   *    An array of target IDs and revision IDs for the session time.
   */
  private function getSessionTime($session, array $scheduleData): array {
    $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    if (!$session->get('field_session_time')->getValue() || !($time_paragraph = $paragraph_storage->load($session->field_session_time->target_id))) {
      $time_paragraph = $paragraph_storage->create(['type' => 'session_time']);
      $time_paragraph->isNew();
    }

    if ($scheduleData['days']) {
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

  protected function convertDate($date) {
    $date_obj = new DrupalDateTime($date, \Drupal::configFactory()->get('system.date')->get('timezone')['default']);
    return substr($date_obj->format('c', ['timezone' => 'UTC']), 0, 19);
  }

  /**
   * Retrieves a list of branches.
   *
   * @return array
   *    An associative array of branch IDs and titles.
   */
  public function getBranches() {
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.status', NodeInterface::PUBLISHED);
    $query->condition('n.type', 'branch');
    $query->orderBy('n.title');
    $query->addTag('openy_home_branch_get_locations');
    $query->addTag('node_access');

    return $query->execute()->fetchAllKeyed();
  }

  /**
   * Provides a JSON response with a list of branches.
   *
   * @return JsonResponse
   *    A JSON response containing the branches.
   */
  public function getBranchesOptions(): JsonResponse {
    return new JsonResponse($this->getBranches());
  }

  /**
   * Provides a JSON response with a list of classes.
   *
   * @return JsonResponse
   *    A JSON response containing the classes.
   */
  public function getClassesOptions(): JsonResponse {
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.status', NodeInterface::PUBLISHED);
    $query->condition('n.type', 'class');
    $query->orderBy('n.title');
    $res = $query->execute()->fetchAllKeyed();

    return new JsonResponse($res);
  }

  public function getCategories(): JsonResponse {
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.status', NodeInterface::PUBLISHED);
    $query->condition('n.type', 'activity');
    $query->orderBy('n.title');
    $result = $query->execute();

    $categories = [];
    foreach ($result as $record) {
      // TODO: We assume that the color will be determined later, so for now we put a placeholder.
      $color = '#FFD700';

      $categories[] = [
        'name' => $record->title,
        'color' => $color,
      ];
    }

    return new JsonResponse($categories);
  }

}
