<?php declare(strict_types=1);

namespace Drupal\y_pef_schedule\Form;

use Drupal\colorapi\Plugin\DataType\HexColorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for configuring FullCalendar settings.
 *
 * This form allows site administrators to customize various aspects of the
 * FullCalendar integration, ensuring the calendar functionality matches the
 * specific needs and preferences of the site. Settings include configurable
 * time intervals for calendar slots, the granularity of event snapping, and the
 * interval for slot labels, all of which enhance the user experience by
 * tailoring the calendar's behavior and presentation.
 */
class FullCalendarSettingsForm extends ConfigFormBase {

  const SETTINGS = 'y_pef_schedule.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'y_pef_schedule_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    $form['slot'] = [
      '#type' => 'details',
      '#title' => $this->t('Slot settings'),
      '#open' => TRUE,
    ];
    $form['slot']['slot_duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slot Duration'),
      '#default_value' => $config->get('slot_duration'),
      '#description' => $this->t('The slotDuration setting defines the length of each time slot in the calendar (e.g., "00:30:00" for 30 minutes).'),
    ];
    $form['slot']['snap_duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Snap Duration'),
      '#default_value' => $config->get('snap_duration'),
      '#description' => $this->t('The snapDuration setting determines the granularity of time slot selection and event dragging (e.g., "00:30:00" for 30 minutes).'),
    ];
    $form['slot']['slot_label_interval'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slot Label Interval'),
      '#default_value' => $config->get('slot_label_interval'),
      '#description' => $this->t('The slotLabelInterval setting specifies the interval between time labels displayed along the calendar\'s axis (e.g., "01:00" for every hour).'),
    ];

    $form['time'] = [
      '#type' => 'details',
      '#title' => $this->t('Time settings'),
      '#open' => TRUE,
    ];
    $form['time']['min_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Min Time'),
      '#default_value' => $config->get('min_time') ?? '04:00:00',
      '#description' => $this->t('Enter the earliest time in HH:MM:SS format that you want to be visible in the calendar’s view. This setting helps to limit the view to specific hours, making the calendar more relevant to the user’s needs.'),
    ];
    $form['time']['max_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max Time'),
      '#default_value' => $config->get('max_time') ?? '23:00:00',
      '#description' => $this->t('Enter the latest time in HH:MM:SS format that you want to be visible in the calendar’s view. This time represents the cutoff point at which the calendar view will stop displaying events for the day.'),
    ];

    $form['default_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Color'),
      '#default_value' => $config->get('default_color'),
      '#description' => $this->t('Enter the default color for activities in the calendar. Enter the color in hexadecimal string format #XXXXXX where X is a hexadecimal character (0-9, a-f).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(static::SETTINGS)
      ->set('slot_duration', $form_state->getValue('slot_duration'))
      ->set('snap_duration', $form_state->getValue('snap_duration'))
      ->set('slot_label_interval', $form_state->getValue('slot_label_interval'))
      ->set('min_time', $form_state->getValue('min_time'))
      ->set('max_time', $form_state->getValue('max_time'))
      ->set('default_color', $form_state->getValue('default_color'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $form_state->getValue('slot_duration'))) {
      $form_state->setErrorByName('slot_duration', $this->t('The Slot Duration must be in the format of "HH:MM:SS".'));
    }

    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $form_state->getValue('snap_duration'))) {
      $form_state->setErrorByName('snap_duration', $this->t('The Snap Duration must be in the format of "HH:MM:SS".'));
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $form_state->getValue('slot_label_interval'))) {
      $form_state->setErrorByName('slot_label_interval', $this->t('The Slot Label Interval must be in the format of "HH:MM".'));
    }

    if (!preg_match(HexColorInterface::HEXADECIMAL_COLOR_REGEX, $form_state->getValue('default_color'))) {
      $form_state->setErrorByName('default_color', $this->t('%value is not a valid hexadecimal color string. Hexadecimal color strings are in the format #XXXXXX where X is a hexadecimal character (0-9, a-f).', ['%value' => $form_state->getValue('default_color')]));
    }
  }

}
