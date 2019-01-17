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
  module_load_include('inc', 'islandora', 'includes/breadcrumb');
  //drupal_set_breadcrumb(islandora_get_breadcrumbs($object));
  // @FIXME
// drupal_set_title() has been removed. There are now a few ways to set the title
// dynamically, depending on the situation.
//
//
// @see https://www.drupal.org/node/2067859
// drupal_set_title($object->label);

  $manage_tabs = [
    'manage_page' => [
      '#type' => 'vertical_tabs',
      'pdf' => [
        '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
        '#title' => t('Create a PDF'),
        '#type' => 'fieldset',
        'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePagePdf', $object),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ],
      'ocr' => [
        '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
        '#title' => t('Perform OCR'),
        '#type' => 'fieldset',
        'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePageOcr', $object),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ],
      'images' => [
        '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
        '#title' => t('Create Images'),
        '#type' => 'fieldset',
        'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePageImages', $object),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ],
      'edit_ocr' => [
        '#access' => isset($object['OCR']) && isset($object['JPG']) && islandora_datastream_access(ISLANDORA_PAGED_CONTENT_EDIT_OCR, $object['OCR']),
        '#title' => t('Edit OCR'),
        '#type' => 'fieldset',
        'form' => isset($object['OCR']) && isset($object['JPG']) ? \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePageEditOcr', $object) : NULL,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ],
    ],
  ];
  $context = ['object' => $object];
  \Drupal::moduleHandler()->alter('islandora_paged_content_page_management_tabs', $manage_tabs, $context);
  return $manage_tabs;
}
