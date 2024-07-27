<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

//namespace VB\Component\H5P\Administrator\View\Newcontent;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use VB\Component\H5P\H5PEditorJoomlaStorage;
use VB\Component\H5P\H5PEditorJoomlaAjax;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use VB\Component\H5P\Administrator\Helper\H5PEventHelper;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_h5p
 *
 * @copyright   Copyright (C) 2021 John Smith. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */



class H5pViewNewcontent extends \Joomla\CMS\MVC\View\HtmlView
{

	public $content = NULL;
	public $upload = FALSE;
	public $display_options;
	protected static $h5peditor = NULL;
	private $insertButton = FALSE;
	public $library, $parameters, $contentExists, $examplesHint;

	function display($tpl = null)
	{
		$task = filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING);
		$plugin = H5PJoomlaHelper::get_instance();
		switch ($task) {
			case 'h5p_get-content':
				$this->ajax_get_hub_content(0, filter_input(INPUT_GET, 'hubId'));
				return;
			case 'h5p_content-hub-metadata-cache':
				$this->ajax_content_hub_metadata_cache();
				return;
			case 'h5p_libraries':
				$this->ajax_libraries();
				return;
			case 'h5p_contents_user_data':
				$plugin->ajax_contents_user_data();
				exit;
			case 'h5p_content-type-cache':
				$this->ajax_content_type_cache();
				return;
			case 'h5p_library-install':
				$this->ajax_library_install();
				return;
			case 'h5p_filter':
				$this->ajax_filter(0);
				return;
			case 'h5p_library-upload':
				$this->ajax_library_upload();
				return;
			case 'h5p_files':
				$this->ajax_files();
				return;
		}
		H5pHelper::addSubMenu('newcontent');

		$consent = filter_input(INPUT_POST, 'consent', FILTER_VALIDATE_BOOLEAN);

		if ($consent !== NULL && !$plugin->getSetting('h5p_has_request_user_consent') && $plugin->current_user_can('manage_options')) {
			$plugin->setSetting('h5p_hub_is_enabled', $consent);
			$plugin->setSetting('h5p_send_usage_statistics', $consent);
			$plugin->setSetting('h5p_has_request_user_consent', 1);
		}

		$document = Factory::getDocument();

		if (!$plugin->getSetting('h5p_has_request_user_consent') && $plugin->current_user_can('manage_options')) {
			// Get the user to enable the Hub before creating content
			$document->addStyleSheet(Uri::root() . 'media/com_h5p/css/admin.css');
			parent::display('user-consent');
			return;
		}

		// Check if we have any content or errors loading content
		$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
		if ($id) {
			$this->content = $this->getModel()->load_content($id);
			//var_dump($this->content);die;
			if (is_string($this->content)) {
				Factory::getApplication()->enqueueMessage($this->content, 'error');
				$this->content = NULL;
			}
		}

		if ($this->content !== NULL) {
			// We have existing content

			if (!$plugin->current_user_can_edit($this->content)) {
				// The user isn't allowed to edit this content
				Factory::getApplication()->enqueueMessage(Text::_('COM_H5P_NEWCONTENT_NOTALLOWEDIT', 'error'));
			}

			// Check if we're deleting content
			$delete = filter_input(INPUT_GET, 'delete');

			if ($delete) {
				$this->set_content_tags($this->content['id']);
				$storage = $plugin->get_h5p_instance('storage');
				$storage->deletePackage($this->content);

				// Log content delete
				new H5PEventHelper(
					'content',
					'delete',
					$this->content['id'],
					$this->content['title'],
					$this->content['library']['name'],
					$this->content['library']['majorVersion'] . '.' . $this->content['library']['minorVersion']
				);

				Factory::getApplication()->redirect(Route::_('/administrator/index.php?option=com_h5p&view=contents'));
				return;
			}
		}

		$action = filter_input(INPUT_POST, 'action', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(upload|create)$/'))); //$action = $input->get('action');
		if ($action) {
			//check_admin_referer('h5p_content', 'yes_sir_will_do'); // Verify form
			$core = $plugin->get_h5p_instance('core'); // Make sure core is loaded

			$result = FALSE;
			if ($action === 'create') {
				// Handle creation of new content. 
				$result = $this->handle_content_creation($this->content);
			} elseif (isset($_FILES['h5p_file']) && $_FILES['h5p_file']['error'] === 0) {
				// Create new content if none exists
				$content = ($this->content === NULL ? array('disable' => \H5PCore::DISABLE_NONE) : $this->content);
				$content['uploaded'] = true;
				$this->get_disabled_content_features($core, $content);

				// Handle file upload
				$result = $plugin->handle_upload($content);
			}

			if ($result) {
				$content['id'] = $result;
				$this->set_content_tags($content['id'], filter_input(INPUT_POST, 'tags'));
				if (empty($echo_on_success)) {
					Factory::getApplication()->redirect(Route::_('/administrator/index.php?option=com_h5p&view=contents&layout=show-content&task=show&id=' . $content['id']));
					return;
				} else {
					echo $echo_on_success;
				}
			}
		}

		$this->contentExists = ($this->content !== NULL && !is_string($this->content));
		$hubIsEnabled = $plugin->getSetting('h5p_hub_is_enabled');

		$core = $plugin->get_h5p_instance('core');

		// Prepare form
		$this->library = $this->get_input('library', $this->contentExists ? \H5PCore::libraryToString($this->content['library']) : 0);
		$this->parameters = $this->get_input('parameters', '{"params":' . ($this->contentExists ? $core->filterParameters($this->content) : '{}') . ',"metadata":' . ($this->contentExists ? json_encode((object)$this->content['metadata']) : '{}') . '}');

		// Determine upload or create
		if (!$hubIsEnabled && !$this->contentExists && !$this->getModel()->has_libraries()) {
			$this->upload = TRUE;
			$this->examplesHint = TRUE;
		} else {
			$this->upload = filter_input(INPUT_POST, 'action') === 'upload';
			$this->examplesHint = FALSE;
		}

		/*// Filter/escape parameters, double escape that is...
		$safe_text = $plugin->wp_check_invalid_utf8($parameters);
		$safe_text = $plugin->_wp_specialchars($safe_text, ENT_QUOTES, false, true);
		$parameters = apply_filters('attribute_escape', $safe_text, $parameters);
		*/

		$this->display_options = $core->getDisplayOptionsForEdit($this->contentExists ? $this->content['disable'] : NULL);

		parent::display($tpl);

		$this->add_editor_assets($this->contentExists ? $this->content['id'] : NULL);
		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/jquery.js');
		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-display-options.js');
		$document->addScript(Uri::root() . 'media/com_h5p/js/h5p-toggle.js');

		// Log editor opened
		if ($this->contentExists) {
			new H5PEventHelper(
				'content',
				'edit',
				$this->content['id'],
				$this->content['title'],
				$this->content['library']['name'],
				$this->content['library']['majorVersion'] . '.' . $this->content['library']['minorVersion']
			);
		} else {
			new H5PEventHelper('content', 'new');
		}
	}

	public function ajax_files()
	{
		$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
		$contentId = filter_input(INPUT_POST, 'contentId', FILTER_SANITIZE_NUMBER_INT);

		$editor = $this->get_h5peditor_instance();
		$editor->ajax->action(H5PEditorEndpoints::FILES, $token, $contentId);
		exit;
	}

	public function ajax_library_upload()
	{
		$token = filter_input(INPUT_GET, 'token');
		$filePath = $_FILES['h5p']['tmp_name'];
		$editor = $this->get_h5peditor_instance();
		$contentId = filter_input(INPUT_POST, 'contentId', FILTER_SANITIZE_NUMBER_INT);
		$editor->ajax->action(\H5PEditorEndpoints::LIBRARY_UPLOAD, $token, $filePath, $contentId);
		exit;
	}

	function ajax_filter($content_id)
	{
		$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
		$editor = $this->get_h5peditor_instance();
		$editor->ajax->action(H5PEditorEndpoints::FILTER, $token, $_POST['libraryParameters']);
		exit;
	}

	function ajax_get_hub_content($localContentId, $hubId)
	{
		$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
		$editor = $this->get_h5peditor_instance();
		$editor->ajax->action(H5PEditorEndpoints::GET_HUB_CONTENT, $token, $hubId, $localContentId);
		exit;
	}

	function ajax_content_hub_metadata_cache()
	{
		$plugin = H5PJoomlaHelper::get_instance();
		$language = $plugin->get_language();

		$editor = $this->get_h5peditor_instance();
		$editor->ajax->action(\H5PEditorEndpoints::CONTENT_HUB_METADATA_CACHE, $language);
		exit;
	}

	private function set_content_tags($content_id, $tags = '')
	{
		$db = Factory::getDbo();
		$tag_ids = array();
		// Create array and trim input
		$tags = explode(',', $tags);

		foreach ($tags as $tag) {
			$tag = trim($tag);
			if ($tag === '') {
				continue;
			}

			// Find out if tag exists and is linked to content
			$db->setQuery(sprintf(
				"SELECT t.id, ct.content_id
				FROM #__h5p_tags t
				LEFT JOIN #__h5p_contents_tags ct ON ct.content_id = %d AND ct.tag_id = t.id
				WHERE t.name = %s",
				$content_id,
				$db->quote($tag)
			));

			$exists = $db->loadObjectList()[0];

			if (empty($exists)) {
				// Create tag
				$exists = new \stdClass();
				$exists->name = $tag;
				Factory::getDbo()->insertObject('#__h5p_tags', $exists, 'id');
				$exists->id = $db->insertid();
			}

			$tag_ids[] = $exists->id;

			if (empty($exists->content_id)) {
				// Connect to content
				$db_obejct = new \stdClass();
				$db_obejct->content_id = $content_id;
				$db_obejct->tag_id = $exists->id;
				Factory::getDbo()->insertObject("#__h5p_contents_tags", $db_obejct);
			}
		}

		// Remove tags that are not connected to content (old tags)
		$and_where = empty($tag_ids) ? '' : " AND tag_id NOT IN (" . implode(',', $tag_ids) . ")";
		$db->setQuery("DELETE FROM #__h5p_contents_tags WHERE content_id = {$content_id}{$and_where}");
		$db->execute();

		// Maintain tags table by remove unused tags
		$db->setQuery("DELETE t.* FROM #__h5p_tags t LEFT JOIN #__h5p_contents_tags ct ON t.id = ct.tag_id WHERE ct.content_id IS NULL");
		$db->execute();
	}

	private function get_input($field, $default = NULL)
	{
		// Get field
		$value = filter_input(INPUT_POST, $field);

		if ($value === NULL) {
			if ($default === NULL) {
				// No default, set error message.
				Factory::getApplication()->enqueueMessage(sprintf('Missing %s.', $field));
			}
			return $default;
		}

		return $value;
	}

	private function get_h5peditor_instance()
	{
		if (self::$h5peditor === null) {
			//$upload_dir = wp_upload_dir();
			$plugin = H5PJoomlaHelper::get_instance();
			self::$h5peditor = new \H5peditor(
				$plugin->get_h5p_instance('core'),
				new H5PEditorJoomlaStorage(),
				new H5PEditorJoomlaAjax()
			);
		}

		return self::$h5peditor;
	}

	public function add_editor_assets($id = NULL)
	{
		$plugin = H5PJoomlaHelper::get_instance();
		$plugin->add_core_assets();

		// Make sure the h5p classes are loaded
		$plugin->get_h5p_instance('core');
		$this->get_h5peditor_instance();

		// Add JavaScript settings
		$settings = $plugin->get_settings();
		$cache_buster = '?ver=' . H5PJoomlaHelper::VERSION;

		// Use jQuery and styles from core.
		$assets = array(
			'css' => $settings['core']['styles'],
			'js' => $settings['core']['scripts']
		);

		// Use relative URL to support both http and https.
		$upload_dir = Uri::root() . 'libraries/h5p/h5p-editor-php-library';
		$url = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $upload_dir) . '/';

		// Add editor styles
		foreach (\H5peditor::$styles as $style) {
			$assets['css'][] = $url . $style . $cache_buster;
		}

		// Add editor JavaScript
		foreach (\H5peditor::$scripts as $script) {
			// We do not want the creator of the iframe inside the iframe
			if ($script !== 'scripts/h5peditor-editor.js') {
				$assets['js'][] = $url . $script . $cache_buster;
			}
		}

		$document = Factory::getDocument();

		// Add JavaScript with library framework integration (editor part)
		$document->addScript(Uri::root() . 'libraries/h5p/h5p-editor-php-library/scripts/h5peditor-editor.js');
		$document->addScript(Uri::root() . 'media/com_h5p/js/h5p-editor.js');
		$document->addStyleSheet(Uri::root() . '/media/jui/css/icomoon.css');
		// Add translation
		$language = $plugin->get_language();
		$language_script = 'h5p-editor-php-library/language/' . $language . '.js';
		if (!file_exists(JPATH_LIBRARIES . '/h5p/' . $language_script)) {
			$language_script = 'h5p-editor-php-library/language/en.js';
		}
		$document->addScript(Uri::root() . 'libraries/h5p/' . $language_script);

		// Add JavaScript settings
		$content_validator = $plugin->get_h5p_instance('contentvalidator');
		$siteUuid = $plugin->getSetting('h5p_h5p_site_uuid', null);
		$secret   = $plugin->getSetting('h5p_hub_secret', null);
		$enableContentHub = !empty($siteUuid) && !empty($secret);
		$settings['editor'] = array(
			'filesPath' => $plugin->get_h5p_url() . '/editor',
			'fileIcon' => array(
				'path' => Uri::root() . 'libraries/h5p/h5p-editor-php-library/images/binary-file.png',
				'width' => 50,
				'height' => 50,
			),
			'ajaxPath' => Uri::root() . 'administrator/index.php?option=com_h5p' . '&view=newcontent&token=' . \H5PCore::createToken('h5p_editor_ajax') . '&task=h5p_',
			'libraryUrl' => Uri::root() . 'libraries/h5p/h5p-editor-php-library/',
			'copyrightSemantics' => $content_validator->getCopyrightSemantics(),
			'metadataSemantics' => $content_validator->getMetadataSemantics(),
			'assets' => $assets,
			'deleteMessage' => Text::_('COM_H5P_NEWCONTENT_QDELETE'),
			'apiVersion' => \H5PCore::$coreApi,
			'language' => $language,
			'hub' => array(
				'contentSearchUrl' => H5PHubEndpoints::createURL(H5PHubEndpoints::CONTENT) . '/search',
			),
			'enableContentHub' => $enableContentHub,


		);

		if ($id !== NULL) {
			$settings['editor']['nodeVersionId'] = $id;
		}

		$plugin->print_settings($settings);
	}

	public function ajax_libraries()
	{
		$editor = $this->get_h5peditor_instance();
		//$editor->ajax->action(\H5PEditorEndpoints::LIBRARIES);exit;
		// Get input
		$name = filter_input(INPUT_GET, 'machineName', FILTER_SANITIZE_STRING);
		$major_version = filter_input(INPUT_GET, 'majorVersion', FILTER_SANITIZE_NUMBER_INT);
		$minor_version = filter_input(INPUT_GET, 'minorVersion', FILTER_SANITIZE_NUMBER_INT);

		// Retrieve single library if name is specified
		if ($name) {
			$plugin = H5PJoomlaHelper::get_instance();
			$plugin->get_h5p_instance('core');
			$editor->ajax->action(
				\H5PEditorEndpoints::SINGLE_LIBRARY,
				$name,
				$major_version,
				$minor_version,
				$plugin->get_language(),
				'',
				$plugin->get_h5p_url(),
				filter_input(INPUT_GET, 'default-language')
			);

			// Log library load
			new H5PEventHelper(
				'library',
				NULL,
				NULL,
				NULL,
				$name,
				$major_version . '.' . $minor_version
			);
		} else {
			// Otherwise retrieve all libraries
			$editor->ajax->action(\H5PEditorEndpoints::LIBRARIES);
		}
		exit;
	}

	/**
	 * Create new content.
	 *
	 * @since 1.1.0
	 * @param array $content
	 * @return mixed
	 */
	private function handle_content_creation($content)
	{
		$plugin = H5PJoomlaHelper::get_instance();
		$core = $plugin->get_h5p_instance('core');

		// Keep track of the old library and params
		$oldLibrary = NULL;
		$oldParams = NULL;
		if ($content !== NULL) {
			$oldLibrary = $content['library'];
			$oldParams = json_decode($content['params']);
		} else {
			$content = array(
				'disable' => \H5PCore::DISABLE_NONE
			);
		}

		// Get library
		$content['library'] = $core->libraryFromString($this->get_input('library'));

		if (!$content['library']) {
			$core->h5pF->setErrorMessage(Text::_('COM_H5P_NEWCONTENT_INVALIDLIBRARY'));
			return FALSE;
		}
		if ($core->h5pF->libraryHasUpgrade($content['library'])) {
			// We do not allow storing old content due to security concerns
			$core->h5pF->setErrorMessage(Text::_('COM_H5P_NEWCONTENT_UNABLESAVECONTENT'));
			return FALSE;
		}

		// Check if library exists.

		$content['library']['libraryId'] = $core->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'], $content['library']['minorVersion']);
		if (!$content['library']['libraryId']) {
			$core->h5pF->setErrorMessage(Text::_('COM_H5P_NEWCONTENT_NOSUCHLIBRARY'));
			return FALSE;
		}

		// Check parameters
		$content['params'] = $this->get_input('parameters');


		if ($content['params'] === NULL) {
			return FALSE;
		}
		$params = json_decode($content['params']);

		if ($params === NULL) {
			$core->h5pF->setErrorMessage(Text::_('COM_H5P_NEWCONTENT_INVALIDPARAMETERS'));
			return FALSE;
		}

		$content['params'] = json_encode($params->params);
		$content['metadata'] = $params->metadata;

		// Trim title and check length
		$trimmed_title = empty($content['metadata']->title) ? '' : trim($content['metadata']->title);
		if ($trimmed_title === '') {
			Factory::getApplication()->enqueueMessage(sprintf(Text::_('COM_H5P_NEWCONTENT_MISSING'), 'title'), 'error');
			return FALSE;
		}

		if (strlen($trimmed_title) > 255) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_H5P_NEWCONTENT_LONGTITLE'), 'error');
			return FALSE;
		}

		// Set disabled features
		$this->get_disabled_content_features($core, $content);

		try {
			// Save new content
			$content['id'] = $core->saveContent($content);
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage(Text::_($e->getMessage(), 'error'));
			return;
		}

		// Move images and find all content dependencies
		$editor = $this->get_h5peditor_instance();
		$editor->processParameters($content['id'], $content['library'], $params->params, $oldLibrary, $oldParams);
		return $content['id'];
	}

	private function get_disabled_content_features($core, &$content)
	{
		$set = array(
			\H5PCore::DISPLAY_OPTION_FRAME => filter_input(INPUT_POST, 'frame', FILTER_VALIDATE_BOOLEAN),
			\H5PCore::DISPLAY_OPTION_DOWNLOAD => filter_input(INPUT_POST, 'download', FILTER_VALIDATE_BOOLEAN),
			\H5PCore::DISPLAY_OPTION_EMBED => filter_input(INPUT_POST, 'embed', FILTER_VALIDATE_BOOLEAN),
			\H5PCore::DISPLAY_OPTION_COPYRIGHT => filter_input(INPUT_POST, 'copyright', FILTER_VALIDATE_BOOLEAN),
		);
		$content['disable'] = $core->getStorableDisplayOptions($set, $content['disable']);
	}

	public function ajax_content_type_cache()
	{
		$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
		$editor = $this->get_h5peditor_instance();
		$editor->ajax->action(\H5PEditorEndpoints::CONTENT_TYPE_CACHE, $token);
		exit;
	}

	public function ajax_library_install()
	{
		$token = filter_input(INPUT_GET, 'token');
		$name = filter_input(INPUT_GET, 'id');

		$editor = $this->get_h5peditor_instance();
		$editor->ajax->action(\H5PEditorEndpoints::LIBRARY_INSTALL, $token, $name);
		exit;
	}
}
