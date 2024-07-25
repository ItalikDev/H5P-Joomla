<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

use Joomla\CMS\Language\Text;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
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
      <?php print $this->content['title'] ?>
      <?php if (H5PJoomlaHelper::current_user_can_view_content_results($this->content)) : ?>
        <a href="<?php print Uri::root() . 'administrator/index.php?option=com_h5p&view=contents&task=results&layout=content-results&id=' . $this->content['id']; ?>" class="add-new-h2"><?php print Text::_('COM_H5P_RESULTS'); ?></a>
      <?php endif; ?>
      <?php if (H5PJoomlaHelper::current_user_can_edit($this->content)) : ?>
        <a href="<?php print Uri::root() . 'administrator/index.php?option=com_h5p&view=newcontent&id=' . $this->content['id']; ?>" class="add-new-h2"><?php print Text::_('COM_H5P_EDIT'); ?></a>
      <?php endif; ?>
    </h2>
    <div class="h5p-wp-admin-wrapper">
      <div class="h5p-content-wrap">
        <?php print $this->embed_code; ?>
      </div>
      <?php if (H5PJoomlaHelper::current_user_can('edit_h5p_contents')) : ?>
        <div class="postbox h5p-sidebar">
          <h2><?php print Text::_('COM_H5P_CONTENTS_SHORTCODE'); ?></h2>
          <div class="h5p-action-bar-settings h5p-panel">
            <p><?php print Text::_("COM_H5P_CONTENTS_WHATNEXT"); ?></p>
            <p><?php print Text::_('COM_H5P_CONTENTS_SHORTCODEDESC'); ?></p>
            <code>[h5p id="<?php print $this->content['id'] ?>"]</code>
          </div>
        </div>
      <?php endif; ?>
      <div class="postbox h5p-sidebar">
        <h2><?php print Text::_('COM_H5P_CONTENTS_TAGS'); ?></h2>
        <div class="h5p-action-bar-settings h5p-panel">
          <?php if (empty($this->content['tags'])) : ?>
            <p style="font-style: italic;"><?php print Text::_('COM_H5P_CONTENTS_NOTAGS'); ?></p>
          <?php else : ?>
            <p><?php print Text::_($this->content['tags']); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>