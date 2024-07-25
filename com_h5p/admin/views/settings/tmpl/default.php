<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;

defined('_JEXEC') or die();
?>
<?php if (version_compare(JVERSION, '4.0.0', '<')) { ?>
  <div id="j-sidebar-container" class="span2">
    <?php echo JHtmlSidebar::render(); ?>
  </div>
<?php } ?>
<div id="j-main-container" class="span10 j-toggle-main">
  <div class="wrap h5p-settings-container">
    <h2><?php print htmlspecialchars(Text::_("COM_H5P_MENU_SETTINGS")); ?></h2>
    <?php if ($this->save !== null) : ?>
      <div id="setting-error-settings_updated" class="updated settings-error">
        <p><strong><?php Factory::getApplication()->enqueueMessage(Text::_('COM_H5P_SETTINGS_SAVE')); ?></strong></p>
      </div>
    <?php endif; ?>
    <form method="post">
      <table class="form-table">
        <tbody>
          <tr valign="top">
            <th scope="row"><?php print Text::_('COM_H5P_SETTINGS_FRAME'); ?></th>
            <td>
              <label>
                <input name="frame" class="h5p-visibility-toggler" data-h5p-visibility-subject-selector=".h5p-toolbar-option" type="checkbox" value="true" <?php if ($this->frame) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_CONTROLLED_BY_AUTHOR_DEFAULT_ON"); ?>
              </label>
              <p class="h5p-setting-desc">
                <?php print Text::_("COM_H5P_SETTINGS_FRAME_DESC"); ?>
              </p>
            </td>
          </tr>
          <tr valign="top" class="h5p-toolbar-option">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_EXPORT"); ?></th>
            <td>
              <select id="export-button" name="download">
                <option value="<?php echo \H5PDisplayOptionBehaviour::NEVER_SHOW; ?>" <?php if ($this->download == \H5PDisplayOptionBehaviour::NEVER_SHOW) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_NEVER_SHOW"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::ALWAYS_SHOW; ?>" <?php if ($this->download == \H5PDisplayOptionBehaviour::ALWAYS_SHOW) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_ALWAYS_SHOW"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::CONTROLLED_BY_PERMISSIONS; ?>" <?php if ($this->download == \H5PDisplayOptionBehaviour::CONTROLLED_BY_PERMISSIONS) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_CONTROLLED_BY_PERMISSIONS"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON; ?>" <?php if ($this->download == \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_CONTROLLED_BY_AUTHOR_DEFAULT_ON"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF; ?>" <?php if ($this->download == \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_CONTROLLED_BY_AUTHOR_DEFAULT_OFF"); ?>
                </option>
              </select>
              <p class="h5p-setting-desc">
                <?php print Text::_("COM_H5P_SETTINGS_TONEVER1"); ?>
              </p>
            </td>
          </tr>
          <tr valign="top" class="h5p-toolbar-option">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_EMBEDSHOW"); ?></th>
            <td>
              <select id="embed-button" name="embed">
                <option value="<?php echo \H5PDisplayOptionBehaviour::NEVER_SHOW; ?>" <?php if ($this->embed == \H5PDisplayOptionBehaviour::NEVER_SHOW) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_NEVER_SHOW"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::ALWAYS_SHOW; ?>" <?php if ($this->embed == \H5PDisplayOptionBehaviour::ALWAYS_SHOW) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_ALWAYS_SHOW"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::CONTROLLED_BY_PERMISSIONS; ?>" <?php if ($this->embed == \H5PDisplayOptionBehaviour::CONTROLLED_BY_PERMISSIONS) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_CONTROLLED_BY_PERMISSIONS"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON; ?>" <?php if ($this->embed == \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_CONTROLLED_BY_AUTHOR_DEFAULT_ON"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF; ?>" <?php if ($this->embed == \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_CONTROLLED_BY_AUTHOR_DEFAULT_OFF"); ?>
                </option>
              </select>
              <p class="h5p-setting-desc">
                <?php print Text::_("COM_H5P_SETTINGS_TONEVER2"); ?>
              </p>
            </td>
          </tr>
          <tr valign="top" class="h5p-toolbar-option">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_COPYRIGHTSHOW"); ?></th>
            <td>
              <label>
                <input name="copyright" type="checkbox" value="true" <?php if ($this->copyright) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_CONTROLLED_BY_AUTHOR_DEFAULT_ON"); ?>
              </label>
            </td>
          </tr>
          <tr valign="top" class="h5p-toolbar-option">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_ABOUTSHOW"); ?></th>
            <td>
              <label>
                <input name="about" type="checkbox" value="true" <?php if ($this->about) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_ALWAYS_SHOW"); ?>
              </label>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_USERRESULTS"); ?></th>
            <td>
              <label>
                <input name="track_user" type="checkbox" value="true" <?php if ($this->track_user) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_LOGRESULTS"); ?>
              </label>
            </td>
          </tr>

          <tr valign="top">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_SAVECONTENT"); ?></th>
            <td>
              <label>
                <input name="save_content_state" type="checkbox" value="true" <?php if ($this->save_content_state) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_RESUMETASK"); ?>
              </label>
              <p class="h5p-auto-save-freq">
                <label for="h5p-freq"><?php print Text::_("COM_H5P_SETTINGS_AUTOSAVE"); ?></label>
                <input id="h5p-freq" name="save_content_frequency" type="text" value="<?php print $this->save_content_frequency ?>" />
              </p>
            </td>
          </tr>
          </tr>
          <tr valign="top">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_SHOWTOGGLEOTHER"); ?></th>
            <td>
              <select id="show_toggle_view_others_h5p_contents" name="show_toggle_view_others_h5p_contents">
                <option value="<?php echo \H5PDisplayOptionBehaviour::NEVER_SHOW; ?>" <?php if ($this->show_toggle_view_others_h5p_contents == \H5PDisplayOptionBehaviour::NEVER_SHOW) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("JNO"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF; ?>" <?php if ($this->show_toggle_view_others_h5p_contents == \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_YESALL"); ?>
                </option>
                <option value="<?php echo \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON; ?>" <?php if ($this->show_toggle_view_others_h5p_contents == \H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON) : ?>selected="selected" <?php endif; ?>>
                  <?php print Text::_("COM_H5P_SETTINGS_YESONLY"); ?>
                </option>
              </select>
              <p class="h5p-setting-desc">
                <?php print Text::_("COM_H5P_SETTINGS_ALLOWRESTRICT"); ?>
              </p>
            </td>
          <tr valign="top">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_ADDCONTENTMETHOD"); ?></th>
            <td class="h5p-action-bar-settings">
              <div>
                <?php print Text::_('COM_H5P_SETTINGS_ADDCONTENTMETHODQ'); ?>
              </div>
              <div>
                <label>
                  <input type="radio" name="insert_method" value="id" <?php if ($this->insert_method == "id") : ?>checked="checked" <?php endif; ?> />
                  <?php print Text::_("COM_H5P_SETTINGS_REFERENCE") . ' id'; ?>
                </label>
              </div>
              <div>
                <label>
                  <input type="radio" name="insert_method" value="slug" <?php if ($this->insert_method == "slug") : ?>checked="checked" <?php endif; ?> />
                  <?php printf(Text::_('COM_H5P_SETTINGS_REFERENCE') . ' <a href="%s" target="_blank">slug</a>', 'https://en.wikipedia.org/wiki/Semantic_URL#Slug'); ?>
                </label>
              </div>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_CONTENTTYPES"); ?></th>
            <td>
              <label>
                <input name="enable_lrs_content_types" type="checkbox" value="true" <?php if ($this->enable_lrs_content_types) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_LRS"); ?>
              </label>
              <p class="h5p-setting-desc">
                <?php print Text::_("COM_H5P_SETTINGS_LRSDESCR"); ?>
              </p>
              <label class="h5p-hub-setting">
                <input class="h5p-settings-disable-hub-checkbox" name="enable_hub" type="checkbox" value="true" <?php if ($this->enable_hub) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_USEHUB"); ?>
              </label>
              <p class="h5p-setting-desc">
                <?php print Text::_("COM_H5P_SETTINGS_USEHUBDESC"); ?>
              </p>
            </td>
          </tr>

          <tr valign="top">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_DEVELMODE"); ?></th>
            <td>
              <label>
                <input name="dev_mode" type="checkbox" value="true" <?php if ($this->dev_mode) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_DEVELMODEDESC"); ?>
              </label>
            </td>
          </tr>

          <tr valign="top" style="height:5px;">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_DEVELDIR"); ?></th>
            <td>
              <label>
                <input name="library_development" type="checkbox" value="true" <?php if ($this->library_development) : ?> checked="checked" <?php endif; ?> />
                <?php printf(Text::_("COM_H5P_SETTINGS_DEVELDIRDESC"), (new H5PJoomlaHelper())->get_h5p_path() . "/development'"); ?>
              </label>
            </td>
          </tr>

          <tr style="height:10px;"></tr>
          <tr valign="top">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_STAT"); ?></th>
            <td>
              <label>
                <input name="send_usage_statistics" type="checkbox" value="true" <?php if ($this->send_usage_statistics) : ?> checked="checked" <?php endif; ?> />
                <?php print Text::_("COM_H5P_SETTINGS_STATACTION"); ?>
              </label>
              <p class="h5p-setting-desc">
                <?php printf(Text::_("COM_H5P_SETTINGS_STATDESC"), 'https://h5p.org/tracking-the-usage-of-h5p'); ?>
              </p>
            </td>
          </tr>

          <tr style="height:10px;"></tr>
          <tr valign="top">
            <th scope="row"><?php print Text::_("COM_H5P_SETTINGS_HUBACCOUNT"); ?></th>
            <?php
            $plugin = H5PJoomlaHelper::get_instance();
            $core = $plugin->get_h5p_instance('core');
            try {
              $accountInfo = $core->hubAccountInfo();

              if (!$accountInfo) {
            ?>
                <td>
                  <?php printf(Text::_('COM_H5P_SETTINGS_HUBACCOUNTREGISTER') . ' <a href="%s">here</a>.', '/administrator/index.php?option=com_h5p&view=settings&task=h5p_hub_registration&layout=registration'); ?>
                </td>
              <?php
              } else {
                $markup = '';

                if (!empty($accountInfo->name)) {
                  $markup .= '<div>' . htmlspecialchars($accountInfo->name) . '</div>';
                }
                if (!empty($accountInfo->contactPerson)) {
                  $markup .= '<div>' . htmlspecialchars($accountInfo->contactPerson) . '</div>';
                }
                if (!empty($accountInfo->email)) {
                  $markup .= '<div>' . htmlspecialchars($accountInfo->email) . '</div>';
                }

                $hasAddress = !empty($accountInfo->address);
                $hasZipAndCity = !empty($accountInfo->zip) && !empty($accountInfo->city);

                if ($hasAddress || $hasZipAndCity) {
                  $markup .= '<div>';
                  if ($hasAddress) {
                    $markup .= htmlspecialchars($accountInfo->address);
                  }
                  if ($hasZipAndCity) {
                    if ($hasAddress) {
                      $markup .= ', ';
                    }
                    $markup .= htmlspecialchars($accountInfo->zip) . ' ' . htmlspecialchars($accountInfo->city);
                  }
                  $markup .= '</div>';
                }

                if (!empty($accountInfo->country)) {
                  $markup .= '<div>' . htmlspecialchars($accountInfo->country) . '</div>';
                }

                if (!empty($accountInfo->phone)) {
                  $markup .= '<div>' . htmlspecialchars($accountInfo->phone) . '</div>';
                }
              ?>
                <td>
                  <div>
                    <img src="<?php print $accountInfo->logo ?>" style="max-width: 5em; display: inline-block; vertical-align: top" />
                    <div style="display: inline-block">'
                      <?php print $markup ?>
                      <div style="margin-top: 1em">
                        <?php printf(Text::_('COM_H5P_SETTINGS_HUBACCOUNTCHANGE') . ' <a href="%s">here</a>.', '/administrator/index.php?option=com_h5p&view=settings&task=h5p_hub_registration&layout=registration'); ?>
                        </div? </div? </div>
                </td>
            <?php
              }
            } catch (Exception $e) {
              // Not showing account form before secret has been fixed
            }
            ?>
          </tr>
        </tbody>
      </table>
      <input type="hidden" name="save_these_settings" />
      <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
    </form>
  </div>
</div>