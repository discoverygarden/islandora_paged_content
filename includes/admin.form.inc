<?php

/**
 * @file
 * Handles the display/submission of the admin settings form for this module.
 */

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Ajax callback for the GS textfield.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Drupal form state.
 *
 * @return array
 *   The element to render as part the ajax callback.
 */
function islandora_paged_content_admin_settings_form_gs_ajax_callback(array $form, FormStateInterface $form_state) {
  return $form['pdf_derivative_settings']['islandora_paged_content_gs'];
}

/**
 * Gets a message to display if the djatoka is accessible or not.
 *
 * @param string $djatoka_url
 *   The url to an djatoka instance typically
 *   http://localhost:8080/adore-djatoka.
 *
 * @return string
 *   A message in html detailing if the djatoka is accessible.
 */
function islandora_paged_content_admin_settings_form_djatoka_availible_message($djatoka_url) {
  $file_headers = @get_headers($djatoka_url);
  $djatoka_availible = strstr($file_headers[0], '200') !== FALSE;
  if ($djatoka_availible) {
    $image = [
      '#theme' => 'image',
      '#uri' => Url::fromUri('base:core/misc/icons/73b355/check.svg')->toString(),
    ];
    $message = t('djatoka url is valid.');
  }
  else {
    $image = [
      '#theme' => 'image',
      '#uri' => Url::fromUri('base:core/misc/icons/e32700/error.svg')->toString(),
    ];
    $message = t('Unable to locate djatoka installation at @path', ['@path' => $djatoka_url]);
  }
  return \Drupal::service('renderer')->render($image) . $message;
}

/**
 * Ajax callback for the djatoka url textfield.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Drupal form state.
 *
 * @return array
 *   The element to render as part the ajax callback.
 */
function islandora_paged_content_admin_settings_form_djatoka_ajax_callback(array $form, FormStateInterface $form_state) {
  return $form['islandora_paged_content_djatoka_url'];
}

/**
 * Ajax callback for the pdftotext textfield.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Drupal form state.
 *
 * @return array
 *   The element to render as part the ajax callback.
 */
function islandora_paged_content_admin_settings_form_pdftotext_ajax_callback(array $form, FormStateInterface $form_state) {
  return $form['pdf_paged_content_ingestion_settings']['islandora_paged_content_pdftotext'];
}

/**
 * Ajax callback for the pdfinfo textfield.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Drupal form state.
 *
 * @return array
 *   The element to render as part the ajax callback.
 */
function islandora_paged_content_admin_settings_form_pdfinfo_ajax_callback(array $form, FormStateInterface $form_state) {
  return $form['pdf_paged_content_ingestion_settings']['islandora_paged_content_pdfinfo'];
}
