<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * {@inheritdoc}
 */
class ManagePagesImages extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_pages_images_form';
  }

  /**
   * Derives the image datastreams for each page object.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');

    $form_state->setStorage(['object' => $object]);
    return [
      'description' => [
        '#type' => 'item',
        '#description' => $this->t('You must have the <b>Large Image Solution Pack</b> installed to create image derivatives.<br/> This will update the TN, JPG and JP2 datastreams for each page object.'),
      ],
      'submit' => [
        '#disabled' => !islandora_paged_content_can_create_images(),
        '#type' => 'submit',
        '#value' => $this->t('Create Images'),
      ],
    ];
  }

  /**
   * Triggers a batch to derive image datastreams in each page object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = $form_state->getStorage()['object'];
    $pages = array_keys(islandora_paged_content_get_pages($object));
    $batch = islandora_paged_content_create_images_batch($object, $pages);
    batch_set($batch);
  }

}
