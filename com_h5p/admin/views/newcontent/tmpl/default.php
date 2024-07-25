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
      <?php if ($this->content === NULL || is_string($this->content)) : ?>
        <?= Text::_('COM_H5P_MENU_NEWCONTENT') ?>
      <?php else : ?>
        <?php print Text::_('COM_H5P_EDIT'); ?> <em><?php print Text::_($this->content['title']); ?></em>
        <a href="<?php print Uri::root() . 'administrator/index.php?option=com_h5p&view=contents&layout=show-content&task=show&id=' . $this->content['id']; ?>" class="add-new-h2"><?php print Text::_('COM_H5P_VIEW'); ?></a>
        <?php if (H5PJoomlaHelper::current_user_can_view_content_results($this->content)) : ?>
          <a href="<?php print Uri::root() . 'administrator/index.php?option=com_h5p&view=contents&task=results&layout=content-results&id=' . $this->content['id']; ?>" class="add-new-h2"><?php print Text::_('COM_H5P_RESULTS'); ?></a>
        <?php endif; ?>
      <?php endif; ?>
    </h2>
    <?php if (!$this->contentExists || H5PJoomlaHelper::current_user_can_edit($this->content)) : ?>
      <form method="post" enctype="multipart/form-data" id="h5p-content-form">
        <div id="post-body-content">
          <div class="h5p-upload">
            <input type="file" name="h5p_file" id="h5p-file" />
            <?php if (H5PJoomlaHelper::current_user_can('disable_h5p_security')) : ?>
              <div class="h5p-disable-file-check">
                <label><input type="checkbox" name="h5p_disable_file_check" id="h5p-disable-file-check" /> <?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_CHECKBOX_2'); ?></label>
                <div class="h5p-warning"><?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_WARNING'); ?></div>
              </div>
            <?php endif; ?>
          </div>
          <div class="h5p-create">
            <div class="h5p-editor"><?php print Text::_('COM_H5P_WAITINGFORJS'); ?></div>
          </div>
          <?php if ($this->examplesHint) : ?>
            <div class="no-content-types-hint">
              <p><?php printf('COM_H5P_NEWCONTENT_MESSAGELINE1', 'https://h5p.org/content-types-and-applications'); ?></p>
              <p><?php printf('COM_H5P_NEWCONTENT_MESSAGELINE2', 'https://wordpress.org/support/plugin/h5p', 'https://h5p.org/forum', 'https://gitter.im/h5p/CommunityChat'); ?></p>
            </div>
          <?php endif ?>
        </div>
        <div class="postbox h5p-sidebar">
          <h2><?php print Text::_('COM_H5P_LIBRARIES_TABLE_ACTIONS'); ?></h2>
          <div id="minor-publishing" <?php if (H5PJoomlaHelper::getSetting('h5p_hub_is_enabled')) : print 'style="display:none"';
                                      endif; ?>>
            <label><input type="radio" name="action" value="upload" <?php if ($this->upload) : print ' checked="checked"';
                                                                    endif; ?> /><?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_BUTTON'); ?></label>
            <label><input type="radio" name="action" value="create" /><?php print Text::_('COM_H5P_CREATE'); ?></label>
            <input type="hidden" name="library" value="<?php print $this->library; ?>" />
            <input type="hidden" name="parameters" value="<?php echo  htmlspecialchars($this->parameters, ENT_QUOTES); ?>" />
          </div>
          <div id="major-publishing-actions" class="submitbox">
            <?php if ($this->content !== NULL && !is_string($this->content)) : ?>
              <a class="submitdelete deletion" href=<?php print "/administrator/index.php?option=com_h5p&view=newcontent&delete=1&id=" . $this->content['id'] ?>><?php print Text::_('COM_H5P_DELETE') ?></a>
            <?php endif; ?>
            <input type="submit" name="submit-button" value="<?php $this->content === NULL ? print Text::_('COM_H5P_CREATE') : print Text::_('COM_H5P_UPDATE') ?>" class="button button-primary button-large" />
          </div>
        </div>
        <?php if (isset($this->display_options['frame'])) : ?>
          <div class="postbox h5p-sidebar">
            <div role="button" class="h5p-toggle" tabindex="0" aria-expanded="true" aria-label="<?php print Text::_('COM_H5P_NEWCONTENT_TOGGLEPANEL'); ?>"></div>
            <h2><?php print Text::_('COM_H5P_NEWCONTENT_DISPLAYOPTION'); ?></h2>
            <div class="h5p-action-bar-settings h5p-panel">
              <label>
                <input name="frame" type="checkbox" class="h5p-visibility-toggler" data-h5p-visibility-subject-selector=".h5p-action-bar-buttons-settings" value="true" <?php if ($this->display_options[\H5PCore::DISPLAY_OPTION_FRAME]) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_NEWCONTENT_DISPLAYTOOLBAR"); ?>
              </label>
              <?php if (isset($this->display_options[\H5PCore::DISPLAY_OPTION_DOWNLOAD]) || isset($this->display_options[\H5PCore::DISPLAY_OPTION_EMBED]) || isset($this->display_options[\H5PCore::DISPLAY_OPTION_COPYRIGHT])) : ?>
                <div class="h5p-action-bar-buttons-settings">
                  <?php if (isset($this->display_options[\H5PCore::DISPLAY_OPTION_DOWNLOAD])) : ?>
                    <label title="<?php print Text::_("COM_H5P_NEWCONTENT_DISPLAYOPTION_TOOLTIP1"); ?>">
                      <input name="download" type="checkbox" value="true" <?php if ($this->display_options[\H5PCore::DISPLAY_OPTION_DOWNLOAD]) : ?> checked="checked" <?php endif; ?> />
                      <?php print Text::_("COM_H5P_NEWCONTENT_DISPLAYOPTION_ALOOWDOWNLOAD"); ?>
                    </label>
                  <?php endif; ?>
                  <?php if (isset($this->display_options[\H5PCore::DISPLAY_OPTION_EMBED])) : ?>
                    <label>
                      <input name="embed" type="checkbox" value="true" <?php if ($this->display_options[\H5PCore::DISPLAY_OPTION_EMBED]) : ?> checked="checked" <?php endif; ?> />
                      <?php print Text::_("COM_H5P_NEWCONTENT_DISPLAYOPTION_EMBEDBUTTON"); ?>
                    </label>
                  <?php endif; ?>
                  <?php if (isset($this->display_options[\H5PCore::DISPLAY_OPTION_COPYRIGHT])) : ?>
                    <label>
                      <input name="copyright" type="checkbox" value="true" <?php if ($this->display_options[\H5PCore::DISPLAY_OPTION_COPYRIGHT]) : ?> checked="checked" <?php endif; ?> />
                      <?php print Text::_("COM_H5P_NEWCONTENT_DISPLAYOPTION_COPYRIGHTBUTTON"); ?>
                    </label>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        <div class="postbox h5p-sidebar">
          <div role="button" class="h5p-toggle" tabindex="0" aria-expanded="true" aria-label="<?php print Text::_('COM_H5P_NEWCONTENT_TOGGLEPANEL'); ?>"></div>
          <h2><?php print Text::_('COM_H5P_CONTENTS_TAGS'); ?></h2>
          <div class="h5p-panel">
            <textarea rows="2" name="tags" class="h5p-tags"><?php if ($this->contentExists) : print Text::_($this->content['tags']);
                                                            endif; ?></textarea>
            <p class="howto"><?php print Text::_('COM_H5P_NEWCONTENT_TAGSEPARATE'); ?></p>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>