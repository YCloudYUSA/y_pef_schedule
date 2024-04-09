<?php declare(strict_types=1);

namespace Drupal\y_pef_schedule\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Manages repeat schedules and provides related functionality.
 */
class RepeatScheduleManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a RepeatScheduleManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Retrieves the default color for events if no color is set in the activity.
   *
   * @return string
   *   The default color for events.
   */
  public function getDefaultColor(): string {
    $fullcalendar_settings = $this->configFactory->get('y_pef_schedule.settings');
    return $fullcalendar_settings->get('default_color');
  }

}
