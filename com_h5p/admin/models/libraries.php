<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */
 
//namespace VB\Component\H5P\Administrator\Model;
 
defined('_JEXEC') or die;
 
use Joomla\CMS\MVC\Model\ItemModel;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class H5pModelLibraries extends \Joomla\CMS\MVC\Model\ListModel {
 
    /**
     * Возвращает сообщение для отображения
     * @param integer $pk Первичный ключ "элемента сообщения", в настоящее время не используется
     * @return object Message Объект сообщения
     */
	public function getItem($pk= null): object {
        $item = new \stdClass();
        $item->message = Text::_('Model libraries');
        return $item;
    }
	
      
	public function get_library($id = NULL) {

		if ($id === NULL) {
			$id = Factory::getApplication()->input->get('id');
		}

		$db = Factory::getDbo();
		$query = sprintf(
        "SELECT id, title, name, major_version, minor_version, patch_version, runnable, fullscreen
          FROM #__h5p_libraries
          WHERE id = %d",
        $id
		);
		
		$db->setQuery((string) $query);
		$library = $db->loadObject();
		
		if (!$library) {
			Factory::getApplication()->enqueueMessage(Text::_(sprintf('COM_H5P_LABRARIES_NOTFINDLIBRARYID', $id)));
		}

		return $library;
	}
	
	public function rebuild_cache() {
		$db = Factory::getDbo();
		$plugin = H5PJoomlaHelper::get_instance();
		$core = $plugin->get_h5p_instance('core');

		// Do as many as we can in five seconds.
		$start = microtime(TRUE);

		$db->setQuery ( "SELECT id FROM #__h5p_contents WHERE filtered = ''");
		
		$contents = $db->loadObjectList();
		
		$done = 0;
		foreach($contents as $content) {
		  $content = $core->loadContent($content->id);
		  $core->filterParameters($content);
		  $done++;

		  if ((microtime(TRUE) - $start) > 5) {
			break;
		  }
		}

		print (count($contents) - $done);
	}
	
}
