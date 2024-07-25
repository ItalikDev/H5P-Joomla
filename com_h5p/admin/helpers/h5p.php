<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
class H5pHelper extends JHelperContent
{
    public static function addSubmenu($vName)
    {
        JHtmlSidebar::addEntry(
            Text::_("COM_H5P_MENU_CONTENTS"),
            'index.php?option=com_h5p&view=contents',
            $vName == 'contents'
        );
		
        JHtmlSidebar::addEntry(
            Text::_("COM_H5P_MENU_NEWCONTENT"),
            'index.php?option=com_h5p&view=newcontent',
            $vName == 'newcontent'
        );
		
		JHtmlSidebar::addEntry(
            Text::_("COM_H5P_MENU_LIBRARIES"),
            'index.php?option=com_h5p&view=libraries',
            $vName == 'libraries'
        );
		
		JHtmlSidebar::addEntry(
            Text::_("COM_H5P_MENU_MYRESULTS"),
            'index.php?option=com_h5p&view=myresults',
            $vName == 'myresults'
        );
		
		JHtmlSidebar::addEntry(
            Text::_("COM_H5P_MENU_SETTINGS"),
            'index.php?option=com_h5p&view=settings',
            $vName == 'settings'
        );
    }
}
