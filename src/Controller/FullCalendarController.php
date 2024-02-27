<?php

declare(strict_types = 1);

namespace Drupal\y_pef_schedule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
      '#attributes' => ['id' => 'fullcalendar-app'],
      '#attached' => [
        'library' => [
          'y_pef_schedule/custom-calendar-styles',
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
   * @return Response
   */
  public function updateEvent(Request $request): Response {
    // TODO: Will be good to reuse code from the \Drupal\openy_daxko_gxp_syncer\syncer\SessionManager::updateSession
    $event = $request->toArray();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($event['id']);

    $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $time_paragraph = $paragraph_storage->load($node->field_session_time->target_id);

    $time_paragraph->set('field_session_time_date',
      [
        'value' => $event['start'],
        'end_value' => $event['end'],
      ]
    );
    $time_paragraph->save();

    $paragraphs[] = [
      'target_id' => $time_paragraph->id(),
      'target_revision_id' => $time_paragraph->getRevisionId(),
    ];

    $node->set('field_session_time', $paragraphs);
    $node->save();

    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');

    return $response;
  }

  /**
   * Creates a new event based on the request data.
   *
   * @param Request $request
   *    The current request object.
   *
   * @return Response
   */
  public function createEvent(Request $request): Response {
    $event_data = $request->toArray();
    $this->createSession($event_data);

    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');

    return $response;
  }

  /**
   * Creates a new session node with the provided data.
   *
   * @param array $data
   *    The event data.
   *
   * @return Response
   */
  public function createSession(array $data): Response {
    // TODO: \Drupal\openy_daxko_gxp_syncer\syncer\SessionManager::createSession
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $session = $node_storage->create([
      'uid' => \Drupal::currentUser()->id(),
      'lang' => 'und',
      'type' => 'session',
      'title' => $data['title'],
    ]);

    // todo getRevision id
    $session->set('field_session_class', [
      'target_id' => $data['eventClass'],
      'target_revision_id' => $data['eventClass'],
    ]);

    $session->set('field_session_time', $this->getSessionTime($data));
    $session->set('field_session_location', ['target_id' => $data['location']]);
    $session->save();

    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');

    return $response;
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
  private function getSessionTime(array $scheduleData): array {
    $paragraphs = [];
    $paragraph = Paragraph::create(['type' => 'session_time']);
    $paragraph->set('field_session_time_days', explode(',', $scheduleData["days"]));

    // Format: 'value' => '2024-01-11T17:43:47',
    $paragraph->set('field_session_time_date',
      [
        'value' => $scheduleData['start'],
        'end_value' => $scheduleData['end'],
      ]
    );
    $paragraph->isNew();
    $paragraph->save();

    $paragraphs[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    return $paragraphs;
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
    $res =  $query->execute()->fetchAllKeyed();

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
