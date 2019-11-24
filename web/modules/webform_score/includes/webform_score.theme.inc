<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */


/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

/**
 * Prepares variables for webform score submission HTML template.
 *
 * Default template: webform-submission-score-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 *   - score_breakdown: Score information for Submission
 */
function template_preprocess_webform_submission_score_html(&$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $webform_submission->getWebform();

  $variables['webform_id'] = $webform->id();
  $elements = $webform->getElementsInitialized();

  // Loop through each score elements.
  foreach ($variables['submission_score']['score_fields'] as $submission_field) {
    // Set the name, use element name if present.
    $variables['score_breakdown'][$submission_field['name']]['name'] = [
      '#markup' => isset($elements[$submission_field['name']]) ? $elements[$submission_field['name']]['#title'] : $submission_field['name'],
    ];
    // Set the score for element.
    $variables['score_breakdown'][$submission_field['name']]['score'] = [
      '#markup' => $submission_field['score'] . '/' . $submission_field['max_score']
    ];
  }
}

/**
 * Prepares variables for webform score submission text template.
 *
 * Default template: webform-submission-score-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 *   - score_breakdown: Score information for Submission
 */
function template_preprocess_webform_submission_score_text(&$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $webform_submission->getWebform();

  $elements = $webform->getElementsInitialized();

  // Loop through each score elements.
  foreach ($variables['submission_score']['score_fields'] as $submission_field) {
    $variables['score_breakdown'][$submission_field['name']]['name'] = $submission_field['name'];

    // Set the name, use element name if present.
    if (isset($elements[$submission_field['name']])) {
      $variables['score_breakdown'][$submission_field['name']]['name'] = $elements[$submission_field['name']]['#title'];
    }

    // Set the score for element.
    $variables['score_breakdown'][$submission_field['name']]['score'] =  $submission_field['score'] . '/' . $submission_field['max_score'];
  }
}
