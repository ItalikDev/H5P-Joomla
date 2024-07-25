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
    <?php if ($this->library) : ?>
      <h2><?php printf(Text::_('COM_H5P_LIBRARIES_TEMPLATEUPGRADE'), htmlspecialchars($this->library->title), $this->library->major_version, $this->library->minor_version, $this->library->patch_version); ?></h2>
    <?php endif; ?>
    <?php if ($this->settings) : ?>
      <div id="h5p-admin-container"><?php print Text::_('COM_H5P_WAITINGFORJS'); ?></div>
    <?php endif; ?>
  </div>
</div>