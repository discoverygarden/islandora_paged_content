<?php

/**
 * @file
 * The page management menu.
 */

/**
 * Displays all the page management forms in a set of vertical tabs.
 *
 * @param AbstractObject $object
 *   The page object to manage.
 *
 * @return array
 *   A renderable array containing all the management forms related to
 *   page objects.
 */
function islandora_paged_content_manage_page_menu(AbstractObject $object) {
  $manage_tabs = [
    'pdf' => [
      '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
      '#title' => t('Create a PDF'),
      '#type' => 'details',
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePagePdf', $object),
      '#open' => FALSE,
    ],
    'ocr' => [
      '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
      '#title' => t('Perform OCR'),
      '#type' => 'details',
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePageOcr', $object),
      '#open' => FALSE,
    ],
    'images' => [
      '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
      '#title' => t('Create Images'),
      '#type' => 'details',
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePageImages', $object),
      '#open' => FALSE,
    ],
    'edit_ocr' => [
      '#access' => isset($object['OCR']) && isset($object['JPG']) && islandora_datastream_access(ISLANDORA_PAGED_CONTENT_EDIT_OCR, $object['OCR']),
      '#title' => t('Edit OCR'),
      '#type' => 'details',
      'form' => isset($object['OCR']) && isset($object['JPG']) ? \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePageEditOcr', $object) : NULL,
      '#open' => FALSE,
    ],
    '#cache' => [
      'max-age' => 0,
    ],
  ];
  $context = ['object' => $object];
  \Drupal::moduleHandler()->alter('islandora_paged_content_page_management_tabs', $manage_tabs, $context);
  return $manage_tabs;
}
