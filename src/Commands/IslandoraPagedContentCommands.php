<?php

namespace Drupal\islandora_paged_content\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commandfile for Islandora Paged Content.
 */
class IslandoraPagedContentCommands extends DrushCommands {

  /**
   * Consolidates and appends paged content page OCR...
   *
   * ... to any paged content objects with no OCR datastream.
   *
   * @usage drush -u1 paged-content-consolidate-missing-ocr
   *   Trigger OCR consolidation
   * @validate-module-enabled islandora_ocr,islandora_paged_content,islandora
   * @islandora-user-wrap
   *
   * @command paged:content-consolidate-missing-ocr
   * @aliases pccmo,paged-content-consolidate-missing-ocr
   */
  public function contentConsolidateMissingOcr() {
    module_load_include('inc', 'islandora_paged_content', 'includes/batch');
    batch_set(islandora_paged_content_consolidate_missing_ocr_batch());
    drush_backend_batch_process();
  }

}
