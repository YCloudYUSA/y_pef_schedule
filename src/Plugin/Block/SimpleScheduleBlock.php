<?php

declare(strict_types=1);

namespace Drupal\y_pef_schedule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\y_pef_schedule\Controller\FullCalendarController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a simple schedule block.
 *
 * @Block(
 *   id = "ws_simple_schedule",
 *   admin_label = @Translation("Simple Schedule"),
 *   category = @Translation("Common blocks"),
 * )
 */
final class SimpleScheduleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ConfigFactoryInterface $configFactory,
    protected ClassResolverInterface $classResolver,
    protected FullCalendarController $fullCalendarController,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('class_resolver'),
      $container->get('class_resolver')->getInstanceFromDefinition('\Drupal\y_pef_schedule\Controller\FullCalendarController')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'location' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['location'] = [
      '#type' => 'select',
      '#title' => $this->t('Location'),
      '#description' => $this->t('Select the location you would like to display scheduled events.'),
      '#default_value' => $this->configuration['location'],
      '#options' => $this->fullCalendarController->getBranches()
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['location'] = $form_state->getValue('location');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $build['content'] = $this->fullCalendarController->calendarView(
      $this->configuration['location'],
      ['showTitle' => false, 'editable' => false]
    );
    return $build;
  }

}
