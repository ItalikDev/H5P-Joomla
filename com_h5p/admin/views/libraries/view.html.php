<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

//namespace VB\Component\H5P\Administrator\View\Libraries;

defined('_JEXEC') or die;

use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use VB\Component\H5P\Administrator\Helper\H5PEventHelper;

class H5pViewLibraries extends \Joomla\CMS\MVC\View\HtmlView
{

	/**
	 * Отображение основного вида "Hello World" 
	 *
	 * @param   string  $tpl  Имя файла шаблона для анализа; автоматический поиск путей к шаблону.
	 * @return  void
	 */

	public $hubOn, $last_update, $settings;
	public $library = NULL;

	function display($tpl = null)
	{

		$plugin = H5PJoomlaHelper::get_instance();
		$core = $plugin->get_h5p_instance('core');

		//$input = Factory::getApplication()->input;
		$task = filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING);
		H5pHelper::addSubMenu('libraries');
		switch ($task) {
			case 'show':
				$this->display_library_details($tpl);
				return;
			case 'h5p_rebuild_cache':
				$this->ajax_rebuild_cache();
				return;
			case 'h5p_content_upgrade_progress':
				$this->ajax_upgrade_progress();
				return;
			case 'h5p_content_upgrade_library':
				$this->ajax_upgrade_library();
				return;
		}


		if (isset($_FILES['h5p_file'])) {
			// If file upload, we're uploading libraries

			if ($_FILES['h5p_file']['error'] == UPLOAD_ERR_OK) {
				// No upload errors, try to install package
				$plugin->handle_upload(NULL, filter_input(INPUT_POST, 'h5p_upgrade_only') ? TRUE : FALSE);
			} else {
				$phpFileUploadErrors = array(
					UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
					UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
					UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
					UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
					UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
					UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
					UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
				);

				$errorMessage = $phpFileUploadErrors[$_FILES['h5p_file']['error']];
				Factory::getApplication()->enqueueMessage($errorMessage, 'error');
			}
		} elseif (filter_input(INPUT_POST, 'sync_hub')) {
			// Update content type cache
			$core->updateContentTypeCache();
		}

		$interface = $plugin->get_h5p_instance('interface');

		$document = Factory::getDocument();
		$post = ($_SERVER['REQUEST_METHOD'] === 'POST');

		if ($task === 'delete') {
			$this->library = $this->getModel()->get_library();
			if ($this->library) {

				if (!$post) {

					$document->addStyleSheet(Uri::root() . 'media/com_h5p/css/admin.css');
					parent::display($tpl);
					return;
				}

				if ($post) {

					//Check if this library can be deleted
					$usage = $interface->getLibraryUsage(filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT));
					if ($usage['content'] !== 0 || $usage['libraries'] !== 0) {
						Factory::getApplication()->enqueueMessage(Text::_('COM_H5P_LIBRARIES_LIBRARYUSED'), 'error');
						return; // Nope
					}
					$interface->deleteLibrary($this->library);
					Factory::getApplication()->redirect(Route::_('/administrator/index.php?option=com_h5p&view=libraries'));
					return;
				}
			}
		}

		if ($task == 'upgrade') {
			$this->library = $this->get_library();
			if ($this->library) {
				$this->settings = $this->display_content_upgrades($this->library);
			}
			$document->addStyleSheet(Uri::root() . 'media/com_h5p/css/admin.css');
			parent::display($tpl);

			if (isset($this->settings)) {
				$plugin->print_settings($this->settings, 'H5PAdminIntegration');
			}
			return;
		}

		$not_cached = $interface->getNumNotFiltered();
		$libraries = $interface->loadLibraries();

		$settings = array(
			'containerSelector' => '#h5p-admin-container',
			'extraTableClasses' => 'wp-list-table widefat fixed',
			'l10n' => array(
				'NA' => Text::_('COM_H5P_LIBRARIES_NA'),
				'viewLibrary' => Text::_('COM_H5P_LIBRARIES_DETAIL'),
				'deleteLibrary' => Text::_('COM_H5P_LIBRARIES_DELETE'),
				'upgradeLibrary' => Text::_('COM_H5P_LIBRARIES_UPGRRADECONTENT')
			)
		);

		// Add settings for each library
		$i = 0;

		foreach ($libraries as $versions) {
			foreach ($versions as $library) {
				$usage = $interface->getLibraryUsage($library->id, $not_cached ? TRUE : FALSE);
				if ($library->runnable) {
					$upgrades = $core->getUpgrades($library, $versions);
					$upgradeUrl = empty($upgrades) ? FALSE : Uri::root() . 'administrator/index.php?option=com_h5p&view=libraries&task=upgrade&layout=library-content-upgrade&id=' . $library->id . '&destination=' . Uri::root() . 'administrator/index.php?option=com_h5p&view=libraries';

					$restricted = ($library->restricted ? TRUE : FALSE);
					$restricted_url = Uri::root() . 'administrator/index.php?option=com_h5p&task=ajax_restrict_access' .
						'&id=' . $library->id .
						'&restrict=' . ($library->restricted  ? 0 : 1) .
						'&token_id=' . $i .
						'&token=' . \H5PCore::createToken('h5p_library_' . $i);
				} else {
					$upgradeUrl = NULL;
					$restricted = NULL;
					$restricted_url = NULL;
				}

				$contents_count = $interface->getNumContent($library->id);
				$settings['libraryList']['listData'][] = array(
					'title' => $library->title . ' (' . \H5PCore::libraryVersion($library) . ')',
					'restricted' => $restricted,
					'restrictedUrl' => $restricted_url,
					'numContent' => $contents_count == 0 ? '' : $contents_count,
					'numContentDependencies' => $usage['content'] < 1 ? '' : $usage['content'],
					'numLibraryDependencies' => $usage['libraries'] === 0 ? '' : $usage['libraries'],
					'upgradeUrl' => $upgradeUrl,
					'detailsUrl' => Uri::root() . 'administrator/index.php?option=com_h5p&view=libraries&task=show&layout=library-details&id=' . $library->id,
					'deleteUrl' => Uri::root() . 'administrator/index.php?option=com_h5p&view=libraries&task=delete&layout=library-delete&id=' . $library->id
				);

				$i++;
			}
		}

		// Translations
		$settings['libraryList']['listHeaders'] = array(
			Text::_('COM_H5P_LIBRARIES_TABLE_TITLE'),
			Text::_('COM_H5P_LIBRARIES_TABLE_RESTRICTED'),
			array(
				'text' => Text::_('COM_H5P_LIBRARIES_TABLE_CONTENTS'),
				'class' => 'h5p-admin-center'
			),
			array(
				'text' => Text::_('COM_H5P_LIBRARIES_TABLE_CONTENTSUSING'),
				'class' => 'h5p-admin-center'
			),
			array(
				'text' => Text::_('COM_H5P_LIBRARIES_TABLE_LIBRARIESUSING'),
				'class' => 'h5p-admin-center'
			),
			Text::_('COM_H5P_LIBRARIES_TABLE_ACTIONS')
		);

		// Make it possible to rebuild all caches.
		if ($not_cached) {
			$settings['libraryList']['notCached'] = $this->get_not_cached_settings($not_cached);
		}

		$this->hubOn = $plugin->getSetting('h5p_hub_is_enabled');
		$this->last_update = $plugin->getSetting('h5p_content_type_cache_updated_at');

		$document->addStyleSheet(Uri::root() . 'media/com_h5p/css/admin.css');



		parent::display($tpl);

		$plugin->print_settings($settings, 'H5PAdminIntegration');

		$this->add_admin_assets();

		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-library-list.js');
	}

	private function get_library($id = NULL)
	{
		$db = Factory::getDbo();

		if ($this->library !== NULL) {
			return $this->library; // Return the current loaded library.
		}

		if ($id === NULL) {
			$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
		}

		// Try to find content with $id.
		$this->library = $db->setQuery(
			"SELECT id, title, name, major_version, minor_version, patch_version, runnable, fullscreen
          FROM #__h5p_libraries
          WHERE id = " . $id
		)->loadObjectList()[0];


		if (!$this->library) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_H5P_LIBRARIES_CANNOTFINDLIB') . $id . '.', 'error');
		}

		return $this->library;
	}

	private function add_admin_assets()
	{
		$document = Factory::getDocument();
		foreach (\H5PCore::$adminScripts as $script) {
			$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/' . $script);
		}
		$document->addStyleSheet(Uri::root() . 'libraries/h5p/h5p-php-library/styles/h5p.css');
		$document->addStyleSheet(Uri::root() . 'libraries/h5p/h5p-php-library/styles/h5p-admin.css');
	}

	private function get_not_cached_settings($num)
	{
		return array(
			'num' => $num,
			'url' => Uri::root() . 'administrator/index.php?option=com_h5p&view=libraries&task=h5p_rebuild_cache',
			'message' => Text::_('COM_H5P_LIBRARIES_CACHE_REBUILDINFO_1'),
			'progress' => sprintf(Text::_("COM_H5P_LIBRARIES_CACHE_REBUILDINFO_2"), $num),
			'button' => Text::_('COM_H5P_LIBRARIES_CACHE_REBUILD')
		);
	}

	private function display_library_details($tpl)
	{

		$db = Factory::getDbo();

		$this->library = $this->getModel()->get_library();

		if (!$this->library) {
			Factory::getApplication()->redirect(Route::_('administrator/index.php?option=com_h5p&view=libraries'));
			return;
		}

		// Add settings and translations
		$plugin = H5PJoomlaHelper::get_instance();
		$interface = $plugin->get_h5p_instance('interface');

		$settings = array(
			'containerSelector' => '#h5p-admin-container',
		);

		// Build the translations needed
		$settings['libraryInfo']['translations'] = array(
			'noContent' => Text::_('COM_H5P_LIBRARIES_NOCONTENT'),
			'contentHeader' => Text::_('COM_H5P_LIBRARIES_CONTENTHEADER'),
			'pageSizeSelectorLabel' => Text::_('COM_H5P_LIBRARIES_PAGESIZE'),
			'filterPlaceholder' => Text::_('COM_H5P_LIBRARIES_FILTER'),
			'pageXOfY' => Text::_('COM_H5P_LIBRARIES_PAGEXOFY'),
		);

		$notCached = $interface->getNumNotFiltered();
		if ($notCached) {
			$settings['libraryInfo']['notCached'] = $this->get_not_cached_settings($notCached);
		} else {
			// List content which uses this library
			$query = sprintf(
				"SELECT DISTINCT hc.id, hc.title
				FROM #__h5p_contents_libraries hcl
				JOIN #__h5p_contents hc ON hcl.content_id = hc.id
				WHERE hcl.library_id = %d
				ORDER BY hc.title",
				$this->library->id
			);
			$db->setQuery((string) $query);
			$contents = $db->loadObjectList();
			foreach ($contents as $content) {
				$settings['libraryInfo']['content'][] = array(
					'title' => $content->title,
					'url' => Uri::root() . 'administrator/index.php?option=com_h5p&view=contents&task=show&layout=show-content&id=' . $content->id,
				);
			}
		}

		// Build library info
		$settings['libraryInfo']['info'] = array(
			Text::_('COM_H5P_LIBRARIES_INFO_VERSION') => \H5PCore::libraryVersion($this->library),
			Text::_('COM_H5P_LIBRARIES_INFO_FULLSCREEN') => $this->library->fullscreen ? Text::_('JYES') : Text::_('JNO'),
			Text::_('COM_H5P_LIBRARIES_INFO_CONTENTLIBRARY') => $this->library->runnable ? Text::_('JYES') : Text::_('JNO'),
			Text::_('COM_H5P_LIBRARIES_INFO_USEDBY') => (isset($contents) ? sprintf(Text::_('COM_H5P_LIBRARIES_INFO_USEDBY_A'), count($contents)) : Text::_('COM_H5P_LIBRARIES_NA')),
		);

		$this->add_admin_assets();
		$document = Factory::getDocument();

		$document->addStyleSheet(Uri::root() . 'media/com_h5p/css/admin.css');

		parent::display($tpl);

		$plugin->print_settings($settings, 'H5PAdminIntegration');

		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-library-details.js');
	}


	public function ajax_rebuild_cache()
	{

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			exit; // POST is required
		}

		$this->getModel()->rebuild_cache();

		exit;
	}

	private function display_content_upgrades($library)
	{
		$upgrades = null;
		$db = Factory::getDbo();

		$plugin = H5PJoomlaHelper::get_instance();
		$interface = $plugin->get_h5p_instance('interface');
		$core = $plugin->get_h5p_instance('core');

		$versions = $db->setQuery(sprintf(
			"SELECT hl2.id, hl2.name, hl2.title, hl2.major_version, hl2.minor_version, hl2.patch_version
			FROM #__h5p_libraries hl1
			JOIN #__h5p_libraries hl2
            ON hl2.name = hl1.name
			WHERE hl1.id = %d
			ORDER BY hl2.title ASC, hl2.major_version ASC, hl2.minor_version ASC",
			$library->id
		))->loadObjectList();

		foreach ($versions as $version) {
			if ($version->id === $library->id) {
				$upgrades = $core->getUpgrades($version, $versions);
				break;
			}
		}

		if (count($versions) < 2) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_H5P_LIBRARIES_NOUPGRADE'), 'error');
			return NULL;
		}

		// Get num of contents that can be upgraded
		$contents = $interface->getNumContent($library->id);
		if (!$contents) {
			Factory::getApplication()->enqueueMessage(Text::_("COM_H5P_LIBRARIES_NOCONTENTUPGRADE"), 'error');
			return NULL;
		}

		$contents_plural = sprintf(Text::_('COM_H5P_LIBRARIES_PLURAL'), $contents);

		// Add JavaScript settings
		$return = filter_input(INPUT_GET, 'destination');
		$settings = array(
			'containerSelector' => '#h5p-admin-container',
			'libraryInfo' => array(
				'message' => sprintf(Text::_('COM_H5P_LIBRARIES_LI_MESSAGE'), $contents_plural),
				'inProgress' => Text::_('COM_H5P_LIBRARIES_LI_INPROGRESS'),
				'error' => Text::_('COM_H5P_LIBRARIES_LI_ERROR'),
				'errorData' => Text::_('COM_H5P_LIBRARIES_LI_ERRORDATA'),
				'errorContent' => Text::_('COM_H5P_LIBRARIES_LI_ERRORCONTENT'),
				'errorScript' => Text::_('COM_H5P_LIBRARIES_LI_ERRORSCRIPT'),
				'errorParamsBroken' => Text::_('COM_H5P_LIBRARIES_LI_ERRORPARAMSBROKEN'),
				'errorLibrary' => Text::_('COM_H5P_LIBRARIES_LI_ERRORLIBRARY'),
				'errorTooHighVersion' => Text::_('COM_H5P_LIBRARIES_LI_ERRORTOOHIGHVERSION'),
				'errorNotSupported' => Text::_('COM_H5P_LIBRARIES_LI_ERRORNOTSUPPORTED'),
				'done' => sprintf(Text::_('COM_H5P_LIBRARIES_LI_DONE'), $contents_plural) . ($return ? '<br/><a href="' . $return . '">' . Text::_('COM_H5P_RETURN') . '</a>' : ''),
				'library' => array(
					'name' => $library->name,
					'version' => $library->major_version . '.' . $library->minor_version,
				),
				'libraryBaseUrl' => Uri::root() . 'administrator/index.php?option=com_h5p&view=libraries&task=h5p_content_upgrade_library&library=',
				'scriptBaseUrl' => Uri::root() . 'libraries/h5p/h5p-php-library/js',
				'buster' => '?ver=' . H5PJoomlaHelper::VERSION,
				'versions' => $upgrades,
				'contents' => $contents,
				'buttonLabel' => Text::_('Upgrade'),
				'infoUrl' => Uri::root() . 'administrator/index.php?option=com_h5p&view=libraries&task=h5p_content_upgrade_progress&id=' . $library->id,
				'total' => $contents,
				'token' => \H5PCore::createToken('h5p_content_upgrade')
			)
		);

		$this->add_admin_assets();
		$document = Factory::getDocument();

		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-version.js');
		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-content-upgrade.js');
		return $settings;
	}

	public function ajax_upgrade_progress()
	{
		$db = Factory::getDbo();
		header('Cache-Control: no-cache');

		if (!\H5PCore::validToken('h5p_content_upgrade', filter_input(INPUT_POST, 'token'))) {
			print Text::_('COM_H5P_INVALIDTOKEN');
			exit;
		}

		$library_id = filter_input(INPUT_GET, 'id');
		if (!$library_id) {
			print Text::_('COM_H5P_LIBRARIES_MISSINGLIBRARY');
			exit;
		}

		// Get the library we're upgrading to
		$to_library = $db->setQuery(
			"SELECT id, name, major_version, minor_version
			FROM #__h5p_libraries
			WHERE id = " . filter_input(INPUT_POST, 'libraryId')
		)->loadObjectList()[0];

		if (!$to_library) {
			print Text::_('COM_H5P_LIBRARIES_INVALIDLIBRARY');
			exit;
		}

		// Prepare response
		$out = new stdClass();
		$out->params = array();
		$out->token = \H5PCore::createToken('h5p_content_upgrade');

		// Get updated params
		$params = filter_input(INPUT_POST, 'params');
		if ($params !== NULL) {
			// Update params.
			$params = json_decode($params);
			foreach ($params as $id => $param) {
				$upgraded = json_decode($param);
				$metadata = isset($upgraded->metadata) ? $upgraded->metadata : array();

				$format = array();
				$fields = array_merge(\H5PMetadata::toDBArray($metadata, false, false), array(
					'updated_at' => (new Joomla\CMS\Date\Date())->toSQL(),
					'parameters' => json_encode($upgraded->params),
					'library_id' => $to_library->id,
					'filtered' => ''
				));

				$db_object = new \stdClass;
				$this->array_to_obj($fields, $db_object);
				$db_object->id = $id;
				$db->updateObject('#__h5p_contents', $db_object, 'id');

				// Log content upgrade successful
				new H5PEventHelper(
					'content',
					'upgrade',
					$id,
					$db->setQuery("SELECT title FROM #__h5p_contents WHERE id = " . $id)->loadResult(),
					$to_library->name,
					$to_library->major_version . '.' . $to_library->minor_version
				);
			}
		}

		// Determine if any content has been skipped during the process
		$skipped = filter_input(INPUT_POST, 'skipped');
		if ($skipped !== NULL) {
			$out->skipped = json_decode($skipped);

			// Clean up input, only numbers
			foreach ($out->skipped as $i => $id) {
				$out->skipped[$i] = intval($id);
			}
			$skipped = implode(',', $out->skipped);
		} else {
			$out->skipped = array();
		}

		// Prepare our interface
		$plugin = H5PJoomlaHelper::get_instance();
		$interface = $plugin->get_h5p_instance('interface');

		// Get number of contents for this library
		$out->left = $interface->getNumContent($library_id, $skipped);

		if ($out->left) {
			$skip_query = empty($skipped) ? '' : " AND id NOT IN ($skipped)";

			// Find the 40 first contents using library and add to params
			$contents = $db->setQuery(sprintf(
				"SELECT id, parameters AS params, title, authors, source, license,
									license_version, license_extras, year_from, year_to, changes,
									author_comments, default_language, a11y_title
									FROM #__h5p_contents
									WHERE library_id = %d
									{$skip_query}
									LIMIT 40",
				$library_id
			))->loadObjectList();

			foreach ($contents as $content) {
				$out->params[$content->id] =
					'{"params":' . $content->params .
					',"metadata":' . \H5PMetadata::toJSON($content) . '}';
			}
		}

		header('Content-type: application/json');
		print json_encode($out);
		exit;
	}

	function array_to_obj($array, &$obj)
	{
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$obj->$key = new stdClass();
				$this->array_to_obj($value, $obj->$key);
			} else {
				$obj->$key = $value;
			}
		}
		return $obj;
	}

	public function ajax_upgrade_library()
	{
		header('Cache-Control: no-cache');

		$library_string = filter_input(INPUT_GET, 'library');
		if (!$library_string) {
			print Text::_('COM_H5P_LIBRARIES_MISSINGLIBRARY');
			exit;
		}

		$library_parts = explode('/', $library_string);
		if (count($library_parts) !== 4) {
			print Text::_('COM_H5P_LIBRARIES_INVALIDLIBRARY');
			exit;
		}

		$library = (object) array(
			'name' => $library_parts[1],
			'version' => (object) array(
				'major' => $library_parts[2],
				'minor' => $library_parts[3]
			)
		);

		$plugin = H5PJoomlaHelper::get_instance();
		$core = $plugin->get_h5p_instance('core');

		$library->semantics = $core->loadLibrarySemantics($library->name, $library->version->major, $library->version->minor);

		// TODO: Library development mode
		if ($core->development_mode & \H5PDevelopment::MODE_LIBRARY) {
			$dev_lib = $core->h5pD->getLibrary($library->name, $library->version->major, $library->version->minor);
		}

		if (isset($dev_lib)) {
			$upgrades_script_path = $upgrades_script_url = $dev_lib['path'] . '/upgrades.js';
		} else {

			$library_folder = \H5PCore::libraryToFolderName( $core->loadLibrary($library->name, $library->version->major, $library->version->minor));
			$suffix = "/libraries/{$library_folder}/upgrades.js";
			//$suffix = '/libraries/' . $library->name . '-' . $library->version->major . '.' . $library->version->minor . '/upgrades.js';
			$upgrades_script_path = $plugin->get_h5p_path() . $suffix;
			$upgrades_script_url = $plugin->get_h5p_url() . $suffix;
		}

		if (file_exists($upgrades_script_path)) {
			$library->upgradesScript = $upgrades_script_url;
		}

		header('Content-type: application/json');
		print json_encode($library);
		exit;
	}
}
