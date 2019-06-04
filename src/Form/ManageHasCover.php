<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * Form for setting the hasCover property flag.
 */
class ManageHasCover extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_hascover_form';
  }

  /**
   * Form handler for setting the hasCover property flag.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $hascover = islandora_paged_content_get_hascover($object);
    $form_state->setStorage(['pid' => $object->id]);
    return [
      'has_cover' => [
        '#type' => 'radios',
        '#title' => $this->t('Book Cover?'),
        '#description' => $this->t("This affects how Paged CMODEL Viewers
          display their first page in a sequence. If set to 'Without Cover',
          the first page will be displayed on the left side, for a Left-to-Right
          progression, which means the display will start with a full 2 page
          spread. Needs to be used in conjuction with a 'Paged' Page display
          mode or the global '2-up' setting for the IA Book Reader viewer."),
        '#options' => [
          'true' => $this->t('Has Cover or First Recto (default)'),
          'false' => $this->t('Without Cover'),
        ],
        '#default_value' => $hascover,
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Set Book Cover Presence'),
      ],
    ];
  }

  /**
   * Submit handler for setting the Viewing mode hint for paged content.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->getStorage()['pid']);
    $hascover = $form_state->getValue('has_cover');
    islandora_paged_content_set_hascover($object, $hascover);
    drupal_set_message($this->t('Book Cover Presence set.'));
  }

}
