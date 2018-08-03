<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * Form for viewing hint.
 */
class ManageViewingHint extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_viewing_hint_form';
  }

  /**
   * Form handler for setting the Viewing mode hint for a paged content.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $viewing_hint = islandora_paged_content_get_viewing_hint($object);
    $viewing_hint = isset($viewing_hint) ? $viewing_hint : 'notset';
    $form_state->setStorage(['pid' => $object->id]);
    return [
      'viewing_hint' => [
        '#type' => 'radios',
        '#title' => $this->t('Page display mode'),
        '#description' => $this->t("This affects how Islandora viewers display page sequences. 'Paged' here is equivalent to the '2-up' mode in IA Book Reader. If left as 'System Default', global option for each viewer will be respected."),
        '#options' => [
          'notset' => $this->t('System Default'),
          'paged' => $this->t('Paged'),
          'individual' => $this->t('Individual'),
        ],
        '#default_value' => $viewing_hint,
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Set Page display mode'),
      ],
    ];
  }

  /**
   * Submit handler for setting the Viewing mode hint for paged content.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->getStorage()['pid']);
    $viewing_hint = $form_state->getValue('viewing_hint') == 'notset' ? NULL : $form_state->getValue('viewing_hint');
    islandora_paged_content_set_viewing_hint($object, $viewing_hint);
    drupal_set_message($this->t('Page display mode updated.'));
  }

}
