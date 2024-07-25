<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
?>

<?php if (version_compare(JVERSION, '4.0.0', '<')) { ?>
  <div id="j-sidebar-container" class="span2">
    <?php echo JHtmlSidebar::render(); ?>
  </div>
<?php } ?>
<div id="j-main-container" class="span10 j-toggle-main">
  <div class="wrap">
    <h2>
      <?php print Text::_('COM_H5P_NEWCONTENT_BEFORESTART'); ?>
    </h2>
    <div class="notice">
      <form method="post">
        <p><?php print Text::_('COM_H5P_NEWCONTENT_BEFORESTART_LINE1'); ?></p>
        <p><?php print Text::_('COM_H5P_NEWCONTENT_BEFORESTART_LINE2'); ?></p>
        <p>
          <?php sprintf('COM_H5P_NEWCONTENT_BEFORESTART_LINE3', 'https://h5p.org/tracking-the-usage-of-h5p'); ?>
        </p>
        <p><button class="button-primary" name="consent" type="submit" value="1"><?php print Text::_('COM_H5P_NEWCONTENT_BEFORESTART_LINE4'); ?></button></p>
        <p><button class="button" name="consent" type="submit" value="0"><?php print Text::_('COM_H5P_NEWCONTENT_BEFORESTART_LINE5'); ?></button></p>
      </form>
    </div>
  </div>
</div>