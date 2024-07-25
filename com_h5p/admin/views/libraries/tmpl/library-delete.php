<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();
?>
<?php if (version_compare(JVERSION, '4.0.0', '<')) { ?>
  <div id="j-sidebar-container" class="span2">
    <?php echo JHtmlSidebar::render(); ?>
  </div>
<?php } ?>
<div id="j-main-container" class="span10 j-toggle-main">
  <div class="wrap">
    <h2><?php print $this->library->title ?></h2>
    <form method="post" enctype="multipart/form-data" id="h5p-library-form">
      <p><?php print Text::_('COM_H5P_LIBRARIES_DELETEQ'); ?></p>
      <input type="hidden" id="lets_delete_this" name="lets_delete_this" value="ef5b3002a2" />
      <input type="submit" name="submit" value="<?php print Text::_('COM_H5P_LIBRARIES_DELETEA') ?>" class="button button-primary button-large" />
    </form>
  </div>
</div>