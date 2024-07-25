<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */
 
//namespace VB\Component\H5P\Administrator\Model;
 
defined('_JEXEC') or die;
 
//use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;


class H5pModelNewcontent extends \Joomla\CMS\MVC\Model\ListModel {
 
    /**
     * Возвращает сообщение для отображения
     * @param integer $pk Первичный ключ "элемента сообщения", в настоящее время не используется
     * @return object Message Объект сообщения
     */

	public function getItem($pk= null): object {
        $item = new \stdClass();
        $item->message = Text::_('Model new content');
        return $item;
    }
	
	public function has_libraries() {
		$db = Factory::getDbo();
		$query = sprintf("SELECT id FROM #__h5p_libraries WHERE runnable = 1 LIMIT 1");
		$db->setQuery((string) $query);
		$res = $db->loadObject();
		return $res !== NULL;
	}
	
	public function load_content($id) {
		$db = Factory::getDbo();
		$plugin = H5PJoomlaHelper::get_instance();

		$content = $plugin->get_content($id);
		if (!is_string($content)) {
			$db->setQuery(sprintf(
				"SELECT t.name
				FROM #__h5p_contents_tags ct
				JOIN #__h5p_tags t ON ct.tag_id = t.id
				WHERE ct.content_id = %d",
				$id
			));
		
			$tags = $db->loadObjectList();
		
			$content['tags'] = '';
			foreach ($tags as $tag) {
				$content['tags'] .= ($content['tags'] !== '' ? ', ' : '') . $tag->name;
			}
		}
	
		return $content;
	}
	
}
