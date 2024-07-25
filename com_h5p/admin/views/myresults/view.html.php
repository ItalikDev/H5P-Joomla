<?php
 
 /**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

//namespace VB\Component\H5P\Administrator\View\Myresults;
 
defined('_JEXEC') or die;
 
use VB\Component\H5P\Administrator\Helper\H5PEventHelper;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
 
class H5pViewMyresults extends \Joomla\CMS\MVC\View\HtmlView {
    
    /**
     * Отображение основного вида "Hello World" 
     *
     * @param   string  $tpl  Имя файла шаблона для анализа; автоматический поиск путей к шаблону.
     * @return  void
     */
    function display($tpl = null) {
		
		H5pHelper::addSubMenu('myresults');
		parent::display($tpl);
	
		$plugin = H5PJoomlaHelper::get_instance();
		$plugin->print_data_view_settings(
			'h5p-my-results',
			Uri::root().'administrator/index.php?option=com_h5p&task=h5p_my_results',
			array(
				(object) array(
					'text' => Text::_('COM_H5P_MYRESULTS_CONTENT'),
					'sortable' => TRUE
				),
				(object) array(
					'text' => Text::_('COM_H5P_MYRESULTS_SCORE'),
					'sortable' => TRUE
				),
				(object) array(
					'text' => Text::_('COM_H5P_MYRESULTS_MAXSCORE'),
					'sortable' => TRUE
				),
				(object) array(
					'text' => Text::_('COM_H5P_MYRESULTS_OPENED'),
					'sortable' => TRUE
				),
				(object) array(
					'text' => Text::_('COM_H5P_MYRESULTS_FINISHED'),
					'sortable' => TRUE
				),
				Text::_('COM_H5P_MYRESULTS_TIMESPENT')
			),
			array(true),
			Text::_("COM_H5P_MYRESULTS_NORESULT"),
			(object) array(
				'by' => 4,
				'dir' => 0
			)
		);

    // Log visit to this page
    new H5PEventHelper('results');

    }
 
 
}
