<?php

namespace Drupal\webform_score\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_score\Plugin\WebformScoreValidatorManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformYamlTidy;

class WebformScore {
  use StringTranslationTrait;

  protected $validatorManager;

  /** @var \Drupal\Core\Database\Database */
  protected $database;

  public function __construct(WebformScoreValidatorManagerInterface $scoreValidatorManager, Connection $database) {
    $this->validatorManager = $scoreValidatorManager;
    $this->database = $database;
  }

  /**
   * Create FAPI fields for configuring element scoring.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform entity object.
   * @param string $webform_element_key
   *   Webform element key id
   * @param string $webform_element_type
   *   Webform element type
   *
   * @return mixed
   *   Array of FAPI elements.
   */
  public function buildElementForm(Webform $webform, $webform_element_key, $webform_element_type) {
    // Get the config for the webform.
    $config = $this->getScoreConfig($webform);
    // Get config for this element.
    $element_config = isset($config[$webform_element_key]) ? $config[$webform_element_key] : [];

    $form['score'] = [
      '#type' => 'details',
      '#title' => $this->t('Score'),
    ];

    $form['score']['scoreable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scoreable'),
      '#description' => $this->t('Check this option if this element should be scored.'),
      '#return_value' => TRUE,
      '#default_value' => !empty($element_config)
    ];

    $form['score']['score_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Score Amount'),
      '#description' => $this->t('Provide the max value to be recorded when scoring this element.'),
      '#states' => [
        'visible' => [
          ':input[name="scoreable"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => isset($element_config['score_amount']) ? $element_config['score_amount'] : '',
    ];

    $form['score']['score_validation_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Score Validation'),
      '#description' => $this->t('Select the validation method to determine if the user gets score.'),
      '#states' => [
        'visible' => [
          ':input[name="scorable"]' => ['checked' => TRUE],
        ],
      ],
      '#options'=> $this->getValidationOptions($webform_element_type),
      '#empty_option' => $this->t('Select Method'),
      '#default_value' => isset($element_config['score_validation_method']) ? $element_config['score_validation_method'] : '',
    ];

    // Get validators for this element type.
    $validation_methods = $this->validatorManager->getElementInstances($webform_element_type);

    // Loop through each validation method.
    foreach ($validation_methods as $validation_method) {
      $plugin_id = $validation_method->getPluginId();

      $form['score'][$plugin_id . '_container'] = [
        '#type' => 'fieldset',
        '#title' => $validation_method->getPluginLabel(),
        '#states' => [
          'visible' => [
            ':input[name="score_validation_method"]' => ['value' => $plugin_id],
          ],
        ],
      ];
      // Config for this validation method?
      $validation_config = [];
      if ($element_config['score_validation_method'] == $plugin_id) {
        if (!empty($element_config['score_validation_data'])) {
          $validation_config = $element_config['score_validation_data'];
        }
      }
      // Additional config fields for this method?
      if ($validation_fields = $validation_method->buildConfigForm($webform_element_type, $validation_config)) {
        // Add config fields.
        $form['score'][$plugin_id . '_container'] += $validation_fields;
      }
    }

    return $form;
  }

  /**
   * Submit callback function for saving score configuration for a Webform
   * element.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform entity
   * @param string $webform_element_key
   *   Element key
   * @param string $webform_element_type
   *   Element type
   * @param array $values
   *   Submitted values
   */
  public function submitElementForm(Webform $webform, $webform_element_key, $webform_element_type, $values) {
    // Get webform config.
    $config = $this->getScoreConfig($webform);
    $element_config = [];
    // Is this element scoreable?
    if ($values['scoreable']) {
      // Get validation methods.
      $validation_methods = $this->validatorManager->getElementInstances($webform_element_type);
      // Submitted validation method exists?
      if (isset($validation_methods[$values['score_validation_method']])) {
        // Get validation data.
        $data = $validation_methods[$values['score_validation_method']]->submitConfigForm($webform_element_type, $values);
        // Set config values.
        $element_config = [
          'score_amount' => $values['score_amount'],
          'score_validation_method' => $values['score_validation_method'],
          'score_validation_data' => $data,
        ];
      }
    }
    // Retrieved configuration?
    if (!empty($element_config)) {
      // Update config array
      $config[$webform_element_key] = $element_config;
    // Else empty config, and previous element config?
    } elseif (isset($config[$webform_element_key])) {
      // Remove old element config.
      unset($config[$webform_element_key]);
    }
    // save updated config.
    $this->setScoreConfig($webform, $config);
  }

  /**
   * Attach Webform Score form elements to the Webform Source form.
   * @param array $form
   *   Form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object
   * @param string  $form_id
   *   Form Id string.
   */
  public function alterWebformSourceForm(&$form, FormStateInterface $form_state, $form_id) {
    $webform = $form_state->getBuildInfo()['callback_object']->getEntity();
    // Does this webform have a score config?
    if ($config = $this->getScoreConfig($webform)) {
      // Decode the elements.
      $elements = Yaml::decode($form['elements']['#default_value']);
      // Add score config to each
      foreach ($elements as $name => &$element_config) {
        if (isset($config[$name]) && !isset($element_config['#score_amount'])) {
          foreach ($config[$name] as $score_config_name => $score_config_value) {
            $element_config['#' . $score_config_name] = is_array($score_config_value) ? serialize($score_config_value) : $score_config_value;
          }
        }
      }
      //Recode the elements
      $form['elements']['#default_value'] = Yaml::encode($elements);
    }
  }

  /**
   * Custom submission callback for Webform Source form.
   */
  public function submitWebformSourceForm($form, FormStateInterface &$form_state) {
    $webform = $form_state->getBuildInfo()['callback_object']->getEntity();
    $elements = Yaml::decode($form_state->getValues()['elements']);
    $config = $this->getScoreConfig($webform);
    foreach ($elements as $element_name => &$element_config) {
      if (isset($element_config['#score_amount'])) {
        $data = unserialize($element_config['#score_validation_data']);
        if ($data !== false) {
          $element_config['#score_validation_data'] = $data;
        }
        $config[$element_name] = [
          'score_amount' => $element_config['#score_amount'],
          'score_validation_method' => $element_config['#score_validation_method'],
          'score_validation_data' => $element_config['#score_validation_data'],
        ];
        unset($element_config['#score_amount'], $element_config['#score_validation_data'], $element_config['#score_validation_method']);
      }
    }
    $form_state->setValue('elements', Yaml::encode($elements));
    $this->setScoreConfig($webform, $config);
  }

  /**
   * Save score data for Webform Submission
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   Webform Submission Entity.
   */
  public function saveSubmission(WebformSubmissionInterface $webformSubmission) {
    $records = [];
    // Get the webform from the submission.
    $webform = $webformSubmission->getWebform();
    // Does this webform have score configuration?
    if ($config = $this->getScoreConfig($webform)) {
      // Get submitted form data.
      $data  = $webformSubmission->getData();
      // Loop through the config elements.
      foreach ($config as $element_name => $element_config) {
        // Get the validator to use for this element.
        if ($validator = $this->validatorManager->findInstance($config[$element_name]['score_validation_method'])) {
          // Get submitted value, if there is one.
          $submitted_value = isset($data[$element_name]) ? $data[$element_name] : '';
          // prep record for saving.
          $records[] = [
            $webform->id(),
            $webformSubmission->id(),
            $element_name,
            $validator->getScore($config[$element_name], $submitted_value),
            $config[$element_name]['score_amount'],
          ];
        }
      }
    }

    // Have records to save?
    if (!empty($records)) {
      // Create insert query
      $query = $this->database->insert('webform_score_field_score')->fields(['webform_id', 'sid', 'name', 'score', 'max_score']);
      // Loop through each record and add to values (to perform multi-insert).
      foreach ($records as $values) {
        $query->values($values);
      }
      // Execute query.
      $query->execute();
    }
  }

  /**
   * Retrieve score information for a WebformSubmission entity.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   Webform Submission Entity.
   *
   * @return bool|mixed
   *   Array of Score data or boolean false.
   */
  public function getSubmission(WebformSubmissionInterface $webformSubmission) {
    $submission = false;
    // Pass to get multiple submissions.
    $submission_ids = [$webformSubmission->id()];
    if ($submissions = $this->loadMultipleSubmissions($submission_ids)) {
      // retrieve the first.
      $submission = array_pop($submissions);
    }
    return $submission;
  }

  /**
   * Retrieve Score data for multiple WebformSubmission Entities.
   *
   * @param array $webform_submission_ids
   *   Array of Webform Submission Entity IDs
   *
   * @return array
   *   Array of Webform Score data arrays.
   */
  private function loadMultipleSubmissions(array $webform_submission_ids) {
    $submissions = [];
    //@TODO: make this static cached.
    $submission_data = $this->database->select('webform_score_field_score','wsfs')
      ->fields('wsfs')
      ->condition('sid', $webform_submission_ids)
      ->execute()
      ->fetchAllAssoc('score_id', \PDO::FETCH_ASSOC);
    // Loop through each result to group by WebformSubmission ID.
    foreach ($submission_data as $score_id => $data) {
      // If not yet set, then build basic score details about submission.
      if (!isset($submissions[$data['sid']])) {
        $submissions[$data['sid']] = [
          'sid' => $data['sid'],
          'webform_id' => $data['webform_id'],
          'score_fields' => [],
        ];
      }
      // Append score data for this element.
      $submissions[$data['sid']]['score_fields'][$score_id] = $data;
    }
    // score submissions?
    if ($submissions) {
      foreach ($submissions as &$submission) {
        $submission += $this->calculateScore($submission['score_fields']);
      }
    }
    return $submissions;
  }

  /**
   * Callback method to create a render array for displaying Submission Score
   * data.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   WebformSubmission Entity.
   * @param string $method
   *   Render method. Defaults to 'html'
   *
   * @return array|bool|string
   *   Render array, YAML string or boolean FALSE.
   */
  public function renderSubmission(WebformSubmissionInterface $webformSubmission, $method = 'html') {
    $render_array = false;
    // Able to retrieve submission values?
    if ($submission_data = $this->getSubmission($webformSubmission)) {
      // Looking to format as YAML?
      if ($method == 'yaml') {
        // Build simple array to YAML encode.
        $yaml = [
          'score' => $submission_data['score'] . '/' . $submission_data['max_score'],
          'element_scores' => [],
        ];

        foreach ($submission_data['score_fields'] as $element) {
          $yaml['element_scores'][$element['name']] = $element['score'] . '/' . $element['max_score'];
        }

        $render_array = Yaml::encode($yaml);
        $yaml = WebformYamlTidy::tidy($yaml);
      } else {
        // Render content.
        $render_array = [
          '#theme' => 'webform_submission_score_' . $method,
          '#webform_submission' => $webformSubmission,
          '#submission_score' => $submission_data,
        ];
      }
    }

    return $render_array;
  }

  /**
   * Calculates the overall score for a WebformSubmission.
   *
   * @param array $score_data
   *   Array of submission score data
   *
   * @return array
   *   Array containing the score and possible max score.
   */
  private function calculateScore(array $score_data) {
    $score = ['score' => 0, 'max_score' => 0];
    foreach ($score_data as $score_field) {
      $score['score'] += $score_field['score'];
      $score['max_score'] += $score_field['max_score'];
    }
    return $score;
  }

  /**
   * Provide form fields for Score validation methods. Based on Webform element
   * type.
   *
   * @param string $webform_element_type
   *   The Webform element type.
   *
   * @return array
   *   Array of Form API elements for various validation methods.
   */
  public function getValidationOptions($webform_element_type) {
    $options = [];

    $validation_methods = $this->validatorManager->getElementInstances($webform_element_type);

    foreach ($validation_methods as $validation_method) {
      $options[$validation_method->getPluginId()] = $validation_method->getPluginLabel();
    }

    return $options;
  }

  /**
   * Retrieve Score configuration settings for a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   Webform entity
   *
   * @return array
   *   Array of Score settings or empty array.
   */
  private function getScoreConfig(WebformInterface $webform) {
    $config = [];
    if ($config = $webform->getThirdPartySetting('webform_score', 'webform_score')) {
      // Unserialize validation data for each.
      foreach ($config as &$element_config) {
        //$element_config['score_validation_data'] = unserialize($element_config['score_validation_data']);
      }
    }

    return $config;
  }

  /**
   * Save config settings for a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   Webform entity
   * @param array $config
   *   Array of Score config settings.
   */
  private function setScoreConfig(WebformInterface $webform, array $config = []) {
    // If the config is empty, remove the thirdparty settings.
    if (empty($config)) {
      $webform->unsetThirdPartySetting('webform_score', 'webform_score');
    }
    else {
      // foreach element config, serialize the validation data
      foreach ($confg as &$element_config) {
        //$element_config['score_validation_data'] = serialize($element_config['score_validation_data']);
      }

      $webform->setThirdPartySetting('webform_score', 'webform_score', $config);
    }
  }

  /**
   * Delete Score data for a webform submission
   *
   * @param int $webform_submission_id
   *   The webform submission entity id.
   */
  public function deleteSubmission($webform_submission_id) {
    $this->database->delete('webform_score_field_score')
      ->condition('sid', $webform_submission_id)
      ->execute();
  }

  /**
   * Delete score data and config for a Webform entity.
   *
   * @param \Drupal\webform\WebformInterface $webform
   */
  public function deleteWebform(WebformInterface $webform) {
    // Call setScoreConfig with no config, to trigger unset.
    $this->setScoreConfig($webform);
    // Delete any score data from DB.
    $this->database->delete('webform_score_field_score')
      ->condition('webform_id', $webform->id())
      ->execute();
  }

  /**
   * Delete config and submission data for a removed webform element.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   Webform entity
   *
   * @param string $element_id
   *   Element id
   */
  public function deleteWebformElement(WebformInterface $webform, $element_id) {
    // Get webform config.
    if ($config = $this->getScoreConfig($webform)) {
      // Config settings for this element?
      if (isset($config[$element_id])) {
        // Remove config settings
        unset($config[$element_id]);
        // Re-save config.
        $this->setScoreConfig($webform, $config);
      }
    }
    // Delete any submission data for this element.
    $this->database->delete('webform_score_field_score')
      ->condition('webform_id', $webform->id())
      ->condition('name', $element_id)
      ->execute();
  }
}
