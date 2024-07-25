<?php

/**
 * @package    Pkg_content_h5p
 * @author     Vitalii Butsykin <v.butsykin@italik.dev>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

defined('_JEXEC') or die('Restricted access');

use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use Joomla\CMS\Factory;

jimport('joomla.plugin.plugin');

JLoader::register('VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper', JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components/com_h5p/helpers' . DIRECTORY_SEPARATOR . 'h5phelper.php');

class plgContenth5p extends JPlugin
{

	private $embed;

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->embed = 0;
	}


	public function onContentPrepare($context, &$article, &$params, $limitstart)
	{

		$hits = preg_match_all('#[h5p id="[0-9]+"]#', $article->text, $matches);

		if ($hits) {
			$html = "";
			$app = Factory::getApplication();
			$input = $app->input;
			if ($input->getMethod() == "POST") {

				$html = '	
				<link href="/media/com_h5p/css/admin.css" rel="stylesheet" />
				<link href="/libraries/h5p/h5p-php-library/styles/h5p.css?ver=1.0.0" rel="stylesheet" />
				<link href="/libraries/h5p/h5p-php-library/styles/h5p-confirmation-dialog.css?ver=1.0.0" rel="stylesheet" />
				<link href="/libraries/h5p/h5p-php-library/styles/h5p-core-button.css?ver=1.0.0" rel="stylesheet" />
				<link href="/libraries/h5p/h5p-php-library/styles/h5p-tooltip.css?ver=1.0.0" rel="stylesheet" />
				
				<script src="/libraries/h5p/h5p-php-library/js/jquery.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/h5p-event-dispatcher.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/h5p-x-api-event.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/h5p-x-api.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/h5p-content-type.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/h5p-confirmation-dialog.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/h5p-action-bar.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/request-queue.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/h5p-tooltip.js?ver=1.0.0"></script>
				<script src="/libraries/h5p/h5p-php-library/js/h5p.js?ver=1.0.0"></script>
				';
			}

			foreach ($matches[0] as $em) {
				preg_match_all('#[0-9]+#', $em, $id);
				$h5p_plugin = H5PJoomlaHelper::get_instance();
				$html .= $h5p_plugin->shortcode($id[0][1]) . '
			';
				if ($input->getMethod() == "POST") {
					$html .= "{emailcloak=off}" . $h5p_plugin->print_settings($h5p_plugin->get_settings(), 'H5PIntegration', 1);
				} else {
					$h5p_plugin->add_settings();
				}
				$article->text = str_replace($em, $html, $article->text);
				$this->embed = 1;
			}
		}
	}

	public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
	{
		if ($this->embed) {
			//$html .= $h5p_plugin->print_settings($h5p_plugin->get_settings(), 'H5PIntegration', 1);
			//(new H5PJoomlaHelper)->add_settings();

		}

		return;
	}
}
