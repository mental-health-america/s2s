<?php
/**
 * Contains \Drupal\recently_read\Form\SettingsForm.
 */
namespace Drupal\recently_read\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provices the recently read config form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a \Drupal\recently_read\Form\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeRepositoryInterface $entity_type_repository, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($config_factory);

    $this->entityTypeRepository = $entity_type_repository;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.repository'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recently_read_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['recently_read.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('recently_read.settings');
    $config = $config->get('recently_read_config');

    $form['#tree'] = TRUE;

    $form['recently_read_config'] = array(
      '#type' => 'fieldset',
      '#title' => t('Recently Read Config'),
    );

    $all_view_modes = $this->entityDisplayRepository->getAllViewModes();
    $labels = $this->entityTypeRepository->getEntityTypeLabels();
    ksort($all_view_modes);
    foreach ($all_view_modes as $entity_type => $view_mode) {
      $form['recently_read_config'][$entity_type] = array(
        '#type' => 'fieldset',
        '#title' => t('Recently Read ' . $entity['label'] . ' config'),
      );
      $form['recently_read_config'][$entity_type]['enable'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable'),
        '#default_value' => $config[$entity_type]['enable'] ? $config[$entity_type][enable] : FALSE, 
      );
      $form['recently_read_config'][$entity_type]['max_record'] = array(
        '#type' => 'textfield',
        '#title' => t('Max Record for Recently Read @entity',array('@entity' => $entity['label'])),
        '#default_value' => $config[$entity_type]['max_record'] ? $config[$entity_type]['max_record'] : 10, 
      );
      // set up the view mode options.
      foreach ($view_mode as $key => $info) {
        $view_mode_options[$key] = $info['label'];
      }

      $form['recently_read_config'][$entity_type]['view_mode'] = array(
        '#type' => 'checkboxes',
        '#title' => t('View mode for track'),
        '#default_value' => $config[$entity_type]['view_mode'] ? $config[$entity_type]['view_mode'] : array('full' => 'full'), 
        '#options' => $view_mode_options,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('recently_read.settings')
      ->set('recently_read_config', $values['recently_read_config'])
      ->save();
  }
}
