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
		<div id="h5p-hub-registration"><?php Text::_('Waiting for javascript...') ?></div>
	</div>
</div>