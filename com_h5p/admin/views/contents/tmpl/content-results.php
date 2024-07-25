<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();
?>
<?php if (version_compare(JVERSION, '4.0.0', '<')) { ?>
  <div id="j-sidebar-container" class="span2">
    <?php echo JHtmlSidebar::render(); ?>
  </div>
<?php } ?>
<div id="j-main-container" class="span10 j-toggle-main">
  <div class="wrap">
    <h2>
      <?php printf(Text::_('COM_H5P_CONTENTS_RESULTSFOR') . '"%s"', htmlspecialchars($this->content['title'], ENT_HTML5)); ?>
      <a href="<?php print Uri::root() . '/administrator/index.php?option=com_h5p&view=contents&task=show&layout=show-content&id='  . $this->content['id']; ?>" class="add-new-h2"><?php print Text::_('COM_H5P_VIEW'); ?></a>
      <?php if (H5PJoomlaHelper::current_user_can_edit($this->content)) : ?>
        <a href="<?php print Uri::root() . '/administrator/index.php?option=com_h5p&view=newcontent&id=' . $this->content['id']; ?>" class="add-new-h2"><?php print Text::_('COM_H5P_EDIT'); ?></a>
      <?php endif; ?>
    </h2>
    <div id="h5p-content-results">
      <?php print Text::_('COM_H5P_WAITINGFORJS'); ?>
    </div>
  </div>
</div>