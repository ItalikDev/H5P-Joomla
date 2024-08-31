<?php
/**
 * @package    pkg_h5p_mathjax
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use Joomla\CMS\Uri\Uri;

jimport('joomla.plugin.plugin');


class plgH5pMods extends JPlugin {

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	public function onh5p_alter_library_styles (&$styles, $libraries, $embed_type) {
		//$plugin = H5PJoomlaHelper::get_instance();
		$styles[] = (object) array(

			'path' => URI::root().'plugins/h5p/mods/styles/general.css'//,
			//'version' => H5PJoomlaHelper::VERSION
		  );
		$styles[] = (object) array(
			'path' => 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap'//,
			//'version' => H5PJoomlaHelper::VERSION
		  );

	}
}