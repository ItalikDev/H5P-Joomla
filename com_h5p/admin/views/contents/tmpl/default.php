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
    <h2><?= Text::_('COM_H5P_MENU_CONTENTS') ?><a href="/administrator/index.php?option=com_h5p&view=newcontent" class="add-new-h2"><?php print Text::_('COM_H5P_CONTENTS_ADDNEW'); ?></a></h2>
    <div id="h5p-contents">
      <?php print Text::_('COM_H5P_WAITINGFORJS'); ?>
    </div>
  </div>
</div>