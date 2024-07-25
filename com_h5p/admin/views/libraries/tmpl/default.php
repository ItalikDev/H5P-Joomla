<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */


use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;

defined('_JEXEC') or die();
?>
<?php if (version_compare(JVERSION, '4.0.0', '<')) { ?>
  <div id="j-sidebar-container" class="span2">
    <?php echo JHtmlSidebar::render(); ?>
  </div>
<?php } ?>
<div id="j-main-container" class="span10 j-toggle-main">
  <div class="wrap">
    <h2><?php print Text::_('COM_H5P_MENU_LIBRARIES') ?></h2>
    <?php if ($this->hubOn) : ?>
      <h3><?php print Text::_('COM_H5P_LIBRARIES_CONTENTTYPECACHE'); ?></h3>
      <form method="post" id="h5p-update-content-type-cache">
        <div class="h5p postbox">
          <div class="h5p-text-holder">
            <p><?php print Text::_('COM_H5P_LIBRARIES_CONTENTTYPECACHE_INFO') ?></p>
            <table class="form-table">
              <tbody>
                <tr valign="top">
                  <th scope="row"><?php print Text::_('COM_H5P_LIBRARIES_LASTUPDATE'); ?>: </th>
                  <td>
                    <?php
                    //$last_update
                    if ($this->last_update !== '') {
                      echo HtmlHelper::date($this->last_update, 'l, F j, Y H:i:s');
                    } else {
                      echo 'never';
                    }
                    ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="h5p-button-holder">
            <input type="hidden" id="sync_hub" name="sync_hub" value="ef5b3002a2" />
            <input type="submit" name="updatecache" id="updatecache" class="button button-primary button-large" value=<?php print Text::_('COM_H5P_UPDATE') ?> />
          </div>
        </div>
      </form>
    <?php endif; ?>
    <h3 class="h5p-admin-header"><?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_TITLE'); ?></h3>
    <form method="post" enctype="multipart/form-data" id="h5p-library-form">
      <div class="h5p postbox">
        <div class="h5p-text-holder">
          <p><?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_CONTENT') ?></p>
          <input type="file" name="h5p_file" id="h5p-file" />
          <input type="checkbox" name="h5p_upgrade_only" id="h5p-upgrade-only" />
          <label for="h5p-upgrade-only" style="display: inline-block;"><?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_CHECKBOX_1'); ?></label>
          <?php if (H5PJoomlaHelper::current_user_can('disable_h5p_security')) : ?>
            <div class="h5p-disable-file-check">
              <label><input type="checkbox" name="h5p_disable_file_check" id="h5p-disable-file-check" /> <?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_CHECKBOX_2'); ?></label>
              <div class="h5p-warning"><?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_WARNING'); ?></div>
            </div>
          <?php endif; ?>
        </div>
        <div class="h5p-button-holder">
          <input type="submit" name="submit" value="<?php print Text::_('COM_H5P_LIBRARIES_UPLOAD_BUTTON') ?>" class="button button-primary button-large" />
        </div>
      </div>
    </form>
    <h3 class="h5p-admin-header"><?php print Text::_('COM_H5P_LIBRARIES_INSTALLEDLIBRARIES'); ?></h3>
    <div id="h5p-admin-container"><?php print Text::_('COM_H5P_WAITINGFORJS'); ?></div>
  </div>
</div>