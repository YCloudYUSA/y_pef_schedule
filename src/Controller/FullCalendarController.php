<?php

namespace Drupal\y_pef_schedule\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Node Revision routes.
 */
class FullCalendarController extends ControllerBase {

  public function myPage(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['id' => 'fullcalendar-app'],
    ];
  }

}
