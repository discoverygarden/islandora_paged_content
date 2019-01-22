<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * Derives the given pages objects image datastreams.
 */
class ManagePageImages extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_page_images_form';
  }

  /**
   * Derives the given pages objects image datastreams.
   *
   * @param array $form
   *   The Drupal form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   * @param \AbstractObject $object
   *   The page object to be updated.
   *
   * @return array
   *   The Drupal form definition.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');

    $form_state->setStorage(['object' => $object->id]);
    $can_derive = islandora_paged_content_can_derive($object, 'JP2');
    return [
      'description' => [
        '#type' => 'item',
        '#description' => $this->t('You must have the <b>Large Image Solution Pack</b> installed to create images.<br/>This will update the TN, JPG and JP2 datastreams.'),
      ],
      'submit' => [
        '#disabled' => !$can_derive,
        '#type' => 'submit',
        '#value' => $this->t('Create Images'),
      ],
    ];
  }

  /**
   * Submit handler for the manage page images form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->getStorage()['object']);
    if (islandora_paged_content_page_derive_image_datastreams($object)) {
      drupal_set_message($this->t('Successfully created images.'), 'status');
    }
    else {
      drupal_set_message($this->t('Failed to created images.'), 'error');
    }
  }

}
