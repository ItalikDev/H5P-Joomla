<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

use \Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use VB\Component\H5P\Administrator\Helper\H5PEventHelper;
use Joomla\CMS\Uri\Uri;

JLoader::register('VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper', JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components/com_h5p/helpers' . DIRECTORY_SEPARATOR . 'h5phelper.php');


/**
 * Class H5pController
 *
 * @since  1.6
 */
class H5pController extends \Joomla\CMS\MVC\Controller\BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean $cachable  If true, the view output will be cached
	 * @param   mixed   $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController   This object to support chaining.
	 *
	 * @since    1.5
	 * @throws Exception
	 */
	/*public function display($cachable = false, $urlparams = false)
	{
		$app  = Factory::getApplication();
		$view = $app->input->getCmd('view', 'tsettings');
		$app->input->set('view', $view);
		

		parent::display($cachable, $urlparams);

		return $this;
	}*/

    public function h5p_setFinished()
    {
        $plugin = H5PJoomlaHelper::get_instance();
        $plugin->ajax_results();
        exit;
    }

    public function h5p_contents_user_data()
    {
        $plugin = H5PJoomlaHelper::get_instance();
        $plugin->ajax_contents_user_data();
        exit;
    }
	
	public function h5p_embed()
    {
        // Allow other sites to embed
        header_remove('X-Frame-Options');

        // Find content
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        if ($id !== null) {
            $plugin = H5PJoomlaHelper::get_instance();
            $content = $plugin->get_content($id);
            if (!is_string($content)) {
                // Everyone is allowed to embed, set through settings
                $embed_allowed = $plugin->getSetting('h5p_embed', true) && !($content['disable'] & \H5PCore::DISABLE_EMBED);
                /**
                 * Allows other plugins to change the access permission for the
                 * embedded iframe's content.
                 *
                 * @since 1.5.3
                 *
                 * @param bool $access
                 * @param int $content_id
                 * @return bool New access permission
                 */
                //$embed_allowed = apply_filters('h5p_embed_access', $embed_allowed, $id);

                if (!$embed_allowed) {
                    // Check to see if embed URL always should be available
                    $embed_allowed = (defined('H5P_EMBED_URL_ALWAYS_AVAILABLE') && H5P_EMBED_URL_ALWAYS_AVAILABLE);
                }

                if ($embed_allowed) {
                    $lang = isset($content['metadata']['defaultLanguage'])
                        ? $content['metadata']['defaultLanguage']
                        : $plugin->get_language();
                    $cache_buster = '?ver=' . H5PJoomlaHelper::VERSION;

                    // Get core settings
                    $integration = $plugin->get_core_settings();
                    // TODO: The non-content specific settings could be apart of a combined h5p-core.js file.

                    // Get core scripts
                    $scripts = array();
                    foreach (\H5PCore::$scripts as $script) {
                        $scripts[] = Uri::root() . 'libraries/h5p/h5p-php-library/' . $script . $cache_buster;
                    }

                    // Get core styles
                    $styles = array();
                    foreach (H5PCore::$styles as $style) {
                        $styles[] = Uri::root() . 'libraries/h5p/h5p-php-library/' . $style . $cache_buster;
                    }

                    // Get content settings
                    $integration['contents']['cid-' . $content['id']] = $plugin->get_content_settings($content);
                    $core = $plugin->get_h5p_instance('core');

                    // Get content assets
                    $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
                    $files = $core->getDependenciesFiles($preloaded_dependencies);

                    $plugin->alter_assets($files, $preloaded_dependencies, 'external');

                    $scripts = array_merge($scripts, $core->getAssetsUrls($files['scripts']));
                    $styles = array_merge($styles, $core->getAssetsUrls($files['styles']));
                    $additional_embed_head_tags = array();
                    /**
                     * Add support for additional head tags for embedded content.
                     * Very useful when adding xAPI events tracking code.
                     *
                     * @since 1.9.5
                     * @param array &$additional_embed_head_tags
                     */
                    $app = Factory::getApplication();
                    $app->triggerEvent('onh5p_additional_embed_head_tags', array(&$additional_embed_head_tags));
                    include_once JPATH_LIBRARIES . '/h5p/h5p-php-library/embed.php';

                    // Log embed view
                    new H5PEventHelper(
                        'content',
                        'embed',
                        $content['id'],
                        $content['title'],
                        $content['library']['name'],
                        $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']
                    );
                    exit;
                }
            }
        }

        // Simple unavailble page
        print '<body style="margin:0"><div style="background: #fafafa url(' . Uri::root() . 'administrator/libraries/h5p/h5p-php-library/images/h5p.svg' . ') no-repeat center;background-size: 50% 50%;width: 100%;height: 100%;"></div><div style="width:100%;position:absolute;top:75%;text-align:center;color:#434343;font-family: Consolas,monaco,monospace">' . Text::_('COM_H5P_CONTENTS_UNAVAILABLE') . '</div></body>';
        exit;
    }
	
}
