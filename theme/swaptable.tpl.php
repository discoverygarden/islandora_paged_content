<?php
/**
 * @file
 */
?>
<div id='<?php print $element['#id']; ?>'>
  <div class="swaptable-display-select">
    <?php print \Drupal::service("renderer")->render($element['display']); ?>
  </div>
  <table class="swaptable-wrapper">
    <tbody>
      <tr>
        <td valign="top" style="vertical-align: top;">
          <?php // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// print theme('table', $table['left']);
 ?>
          <?php // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// print theme('pager', $pager['left']);
 ?>
          <?php print \Drupal::service("renderer")->render($element['page']['left']); ?>
        </td>
        <td valign="top" style="vertical-align: top;">
          <?php // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// print theme('table', $table['right']);
 ?>
          <?php // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// print theme('pager', $pager['right']);
 ?>
          <?php print \Drupal::service("renderer")->render($element['page']['right']); ?>
        </td>
      </tr>
    </tbody>
  </table>
  <?php print \Drupal::service("renderer")->render($element['order']); ?>
  <?php print \Drupal::service("renderer")->render($element['modified']); ?>
  <?php print \Drupal::service("renderer")->render($element['load']); ?>
</div>
