<?php

declare(strict_types = 1);

namespace Drupal\y_pef_schedule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Node Revision routes.
 */
class FullCalendarController extends ControllerBase {

  public function calendarView($branch, $start_date): array {
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['id' => 'fullcalendar-app'],
      '#attached' => [
        'drupalSettings' => [
          'path' => ['bra']
        ]
      ],
    ];
    $build['#attached']['drupalSettings']['path']['branch'] = $branch;

    return $build;
  }

  public function branches(): array {
    $list = $this->getBranches();
    return [
      '#theme' => 'branches_list',
      '#branches' => $list,
    ];
  }

  public function updateEvent(Request $request) {
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

  public function createEvent(Request $request) {
    $event = $request->toArray();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

//    /** @var \Drupal\openy_daxko_gxp_syncer\syncer\SessionManager $session_manager */
//    $session_manager = \Drupal::service('openy_daxko_gxp_syncer.session_manager');

    $session = $node_storage->create([
      'uid' => 1,
      'lang' => 'und',
      'type' => 'session',
      'title' => $event['title'],
    ]);

//    $session->set('field_session_class', $session_manager->getClass($scheduleData));
    $session->set('field_session_class', [
      'target_id' => 126,
      'target_revision_id' => 126,
    ]);
    $session->set('field_session_time', $this->getSessionTime($event));
    $session->set('field_session_location', ['target_id' => 4]);
    $session->save();

    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');

    return $response;
  }

  /**
   * Create paragraphs with session time.
   */
  private function getSessionTime(array $scheduleData) {
    $paragraphs = [];
    $paragraph = Paragraph::create(['type' => 'session_time']);
    $paragraph->set('field_session_time_days', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
//    $paragraph->set('field_session_time_date',
//      [
//        'value' => '2024-01-11T17:43:47',
//        'end_value' => '2024-01-26T17:43:51',
//      ]
//    );
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
   * Get branches list.
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

}
