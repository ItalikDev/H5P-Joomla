<?php

/**
 * @package    Lib_h5p
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

namespace VB\Component\H5P;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use VB\Component\H5P\Administrator\Helper\H5PEventHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Log\Log;


class H5PFrameworkHelper implements \H5PFrameworkInterface
{
	protected $libraryCache;

	public function setErrorMessage($message, $code = NULL)
	{
		if (H5PJoomlaHelper::current_user_can('edit_h5p_contents')) {
			Factory::getApplication()->enqueueMessage($message, 'error');
		}
	}

	public function setInfoMessage($message)
	{
		if (H5PJoomlaHelper::current_user_can('edit_h5p_contents')) {
			Factory::getApplication()->enqueueMessage($message, 'notice');
		}
	}

	public function getUploadedH5pFolderPath()
	{
		// This is set by the controller receiving the .
		static $dir;

		if (is_null($dir)) {
			$plugin = H5PJoomlaHelper::get_instance();
			$core = $plugin->get_h5p_instance('core');
			$dir = $core->fs->getTmpPath();
		}

		return $dir;
	}

	public function getH5pPath()
	{
		$plugin = H5PJoomlaHelper::get_instance();
		return $plugin->get_h5p_path();
	}

	public function getUploadedH5pPath()
	{
		static $path;

		if (is_null($path)) {
			$plugin = H5PJoomlaHelper::get_instance();
			$core = $plugin->get_h5p_instance('core');
			$path = $core->fs->getTmpPath() . '.h5p';
		}

		return $path;
	}

	public function getWhitelist($isLibrary, $defaultContentWhitelist, $defaultLibraryWhitelist)
	{
		// TODO: Make whitelist configurable in admin section!
		$whitelist = $defaultContentWhitelist;
		if ($isLibrary) {
			$whitelist .= ' ' . $defaultLibraryWhitelist;
		}
		return $whitelist;
	}

	public function mayUpdateLibraries()
	{
		return H5PJoomlaHelper::current_user_can('manage_h5p_libraries');
	}

	public function getLibraryId($name, $majorVersion = NULL, $minorVersion = NULL)
	{
		$db = Factory::getDbo();

		// Look for specific library
		$sql_where = 'WHERE name = %s';

		if ($majorVersion !== NULL) {
			// Look for major version
			$sql_where .= ' AND major_version = %d';
			if ($minorVersion !== NULL) {
				// Look for minor version
				$sql_where .= ' AND minor_version = %d';
			}
		}

		// Get the lastest version which matches the input parameters
		$db->setQuery(
			sprintf(
				"SELECT id
			FROM #__h5p_libraries " .
					$sql_where
					. " ORDER BY major_version DESC,
					 minor_version DESC,
					 patch_version DESC
			LIMIT 1",
				$db->quote($name),
				$majorVersion,
				$minorVersion
			)
		);

		$id = $db->loadResult();

		return $id === NULL ? FALSE : $id;
	}

	public function isPatchedLibrary($library)
	{
		$db = Factory::getDbo();

		if (defined('H5P_DEV') && H5P_DEV) {
			// Makes sure libraries are updated, patch version does not matter.
			return TRUE;
		}

		$operator = $this->isInDevMode() ? '<=' : '<';

		$db->setQuery(sprintf(
			"SELECT id
			FROM #__h5p_libraries
			WHERE name = %s
			AND major_version = %d
			AND minor_version = %d
			AND patch_version {$operator} %d",
			$db->quote($library['machineName']),
			$library['majorVersion'],
			$library['minorVersion'],
			$library['patchVersion']
		));

		return $db->loadResult() !== NULL;
	}

	private function pathsToCsv($libraryData, $key)
	{
		if (isset($libraryData[$key])) {
			$paths = array();
			foreach ($libraryData[$key] as $file) {
				$paths[] = $file['path'];
			}
			return implode(', ', $paths);
		}
		return '';
	}

	public function saveLibraryData(&$libraryData, $new = TRUE)
	{
		$preloadedJs = $this->pathsToCsv($libraryData, 'preloadedJs');
		$preloadedCss =  $this->pathsToCsv($libraryData, 'preloadedCss');
		$dropLibraryCss = '';
		if (isset($libraryData['dropLibraryCss'])) {
			$libs = array();
			foreach ($libraryData['dropLibraryCss'] as $lib) {
				$libs[] = $lib['machineName'];
			}
			$dropLibraryCss = implode(', ', $libs);
		}
		$embedTypes = '';
		if (isset($libraryData['embedTypes'])) {
			$embedTypes = implode(', ', $libraryData['embedTypes']);
		}
		if (!isset($libraryData['semantics'])) {
			$libraryData['semantics'] = '';
		}
		if (!isset($libraryData['fullscreen'])) {
			$libraryData['fullscreen'] = 0;
		}

		if (!isset($libraryData['hasIcon'])) {
			$libraryData['hasIcon'] = 0;
		}

		$db_object = new \stdClass;
		$db_object->title = $libraryData['title'];
		$db_object->patch_version = $libraryData['patchVersion'];
		$db_object->runnable = $libraryData['runnable'];
		$db_object->fullscreen = $libraryData['fullscreen'];
		$db_object->embed_types = $embedTypes;
		$db_object->preloaded_js = $preloadedJs;
		$db_object->preloaded_css = $preloadedCss;
		$db_object->drop_library_css = $dropLibraryCss;
		$db_object->semantics = $libraryData['semantics'];
		$db_object->has_icon = $libraryData['hasIcon'] ? 1 : 0;
		$db_object->metadata_settings = $libraryData['metadataSettings'];
		$db_object->add_to = isset($libraryData['addTo']) ? json_encode($libraryData['addTo']) : NULL;
		$db_object->patch_version_in_folder_name = 0;

		$db = Factory::getDbo();
		if ($new) {
			$db_object->name = $libraryData['machineName'];
			$db_object->major_version = $libraryData['majorVersion'];
			$db_object->minor_version = $libraryData['minorVersion'];
			$db_object->patch_version = $libraryData['patchVersion'];
			$db_object->tutorial_url = '';                    // NOT NULL, has to be there

			$db_object->id = 0;

			$db->insertObject('#__h5p_libraries', $db_object, 'id');
			$libraryData['libraryId'] = $db->insertid(); //$db_object->id;
		} else {
			$db_object->id = $libraryData['libraryId'];
			$db->updateObject('#__h5p_libraries', $db_object, 'id', TRUE);
			$this->deleteLibraryDependencies($libraryData['libraryId']);
		}

		// Log library successfully installed/upgraded
		new H5PEventHelper(
			'library',
			($new ? 'create' : 'update'),
			NULL,
			NULL,
			$libraryData['machineName'],
			$libraryData['majorVersion'] . '.' . $libraryData['minorVersion']
		);

		// Update languages
		$q = $db->getQuery(true);
		$q->delete('#__h5p_libraries_languages');
		$q->where('library_id = ' . $libraryData['libraryId']);
		$db->setQuery($q);
		$db->execute(); // Execute

		if (isset($libraryData['language'])) {
			foreach ($libraryData['language'] as $languageCode => $translation) {
				$langobject = new \stdClass;
				$langobject->library_id = $libraryData['libraryId'];
				$langobject->language_code = $languageCode;
				$langobject->translation = $translation;
				$db->insertObject('#__h5p_libraries_languages', $langobject);
			}
		}
	}

	public function saveLibraryDependencies($id, $dependencies, $dependency_type)
	{
		$db = Factory::getDbo();
		$q = $db->getQuery(true);
		foreach ($dependencies as $dependency) {
			$db->setQuery(sprintf(
				"INSERT INTO #__h5p_libraries_libraries	(library_id, required_library_id, dependency_type)
				SELECT %d, hl.id, %s
				FROM #__h5p_libraries hl
				WHERE name = '%s'
				AND major_version = %d
				AND minor_version = %d
				ON DUPLICATE KEY UPDATE dependency_type = %s",
				$id,
				$db->quote($dependency_type),
				$dependency['machineName'],
				$dependency['majorVersion'],
				$dependency['minorVersion'],
				$db->quote($dependency_type)
			));

			$db->execute();
		}
	}

	public function deleteContentData($contentId)
	{
		$db = Factory::getDbo();
		// Remove content data and library usage
		$db->setQuery("DELETE FROM #__h5p_contents WHERE id = " . $contentId);
		$db->execute();

		// Remove user scores/results
		$db->setQuery("DELETE FROM #__h5p_results WHERE content_id = " . $contentId);
		$db->execute();

		// Remove contents user/usage data
		$db->setQuery("DELETE FROM #__h5p_contents_user_data WHERE content_id = " . $contentId);
		$db->execute();
	}

	public function copyLibraryUsage($contentId, $copyFromId, $contentMainId = NULL)
	{
		$db = Factory::getDbo();
		$db->setQuery(sprintf(
			"INSERT INTO #__h5p_contents_libraries (content_id, library_id, dependency_type, weight, drop_css)
				SELECT %d, hcl.library_id, hcl.dependency_type, hcl.weight, hcl.drop_css
				FROM #__h5p_contents_libraries hcl
				WHERE hcl.content_id = %d",
			$contentId,
			$copyFromId
		));
		$db->execute();
	}

	public function deleteLibraryUsage($contentId)
	{
		$db = Factory::getDbo();
		$db->setQuery(sprintf("DELETE FROM #__h5p_contents_libraries WHERE content_id = %d", $contentId));
		$db->execute();
	}

	public function saveLibraryUsage($contentId, $librariesInUse)
	{
		$db = Factory::getDbo();
		$dropLibraryCssList = array();

		foreach ($librariesInUse as $dependency) {
			if (!empty($dependency['library']['dropLibraryCss'])) {
				$dropLibraryCssList = array_merge($dropLibraryCssList, explode(', ', $dependency['library']['dropLibraryCss']));
			}
		}

		foreach ($librariesInUse as $dependency) {
			$dropCss = in_array($dependency['library']['machineName'], $dropLibraryCssList) ? 1 : 0;
			$db->setQuery(sprintf(
				"INSERT INTO #__h5p_contents_libraries
				(content_id, library_id, dependency_type, drop_css, weight)
				VALUES (%d, %d, %s, %d, %d)",
				$contentId,
				$dependency['library']['libraryId'],
				$db->quote($dependency['type']),
				$dropCss,
				$dependency['weight']
			));
			$res = $db->execute();
		}
	}

	public function loadLibrary($name, $majorVersion, $minorVersion)
	{
		$db = Factory::getDbo();
		$q = $db->getQuery(true);
		$q->select(array(
			'id as libraryId',
			'name as machineName',
			'title',
			'major_version as majorVersion',
			'minor_version as minorVersion',
			'patch_version as patchVersion',
			'embed_types as embedTypes',
			'preloaded_js as preloadedJs',
			'preloaded_css as preloadedCss',
			'drop_library_css as dropLibraryCss',
			'fullscreen',
			'runnable',
			'semantics',
			'has_icon as hasIcon',
			'patch_version_in_folder_name'
		))
			->from('#__h5p_libraries')
			->where(array(
				'name = ' . $db->quote($name),
				'major_version = ' . $majorVersion,
				'minor_version = ' . $minorVersion
			));
		$db->setQuery($q);

		$library = $db->loadAssoc();

		if ($library == null) {
			return null;
		}

		$db->setQuery(sprintf(
			"SELECT hl.name as machineName,
				hl.major_version as majorVersion,
				hl.minor_version as minorVersion,
				hll.dependency_type as dependencyType
			FROM #__h5p_libraries_libraries hll
			JOIN #__h5p_libraries hl
				ON hll.required_library_id = hl.id
			WHERE hll.library_id = %d",
			$library['libraryId']
		));

		$res = $db->loadAssocList();
		foreach ($res as $dependency) {
			$library[$dependency['dependencyType'] . 'Dependencies'][] = array(
				'machineName' => $dependency['machineName'],
				'majorVersion' => $dependency['majorVersion'],
				'minorVersion' => $dependency['minorVersion'],
			);
		}

		if ($this->isInDevMode()) {
			$semantics = $this->getSemanticsFromFile($library['machineName'], $library['majorVersion'], $library['minorVersion']);
			if ($semantics) {
				$library['semantics'] = $semantics;
			}
		}
		return $library;
	}


	private function getSemanticsFromFile($name, $majorVersion, $minorVersion)
	{
		$semanticsPath = $this->getH5pPath() . '/libraries/' . $name . '-' . $majorVersion . '.' . $minorVersion . '/semantics.json';
		if (file_exists($semanticsPath)) {
			$semantics = file_get_contents($semanticsPath);
			if (!json_decode($semantics, TRUE)) {
				$this->setErrorMessage($this->t('Invalid json in semantics for %library', array('%library' => $name)));
			}
			return $semantics;
		}
		return FALSE;
	}

	public function deleteLibraryDependencies($libraryId)
	{
		$db = Factory::getDbo();
		$q = $db->getQuery(true);
		$q->delete('#__h5p_libraries_libraries');
		$q->where('library_id = ' . $libraryId);
		$db->setQuery($q);
		$db->execute();
	}

	function h5p_multipart_enc_text($name, $value)
	{
		return "Content-Disposition: form-data; name=\"$name\"\r\n\r\n$value\r\n"; // TODO: Should we be using rawurlencode ?
	}

	function h5p_multipart_enc_file($name, $filepath, $filename, $mimetype = 'application/octet-stream')
	{
		if (substr($filepath, 0, 1) === '@') {
			$filepath = substr($filepath, 1);
		}
		$data = "Content-Disposition: form-data; name=\"$name\"; filename=\"$filename\"\r\n"; // "file" key.
		$data .= "Content-Transfer-Encoding: binary\r\n";
		$data .= "Content-Type: $mimetype\r\n\r\n";

		// Add the encoded file
		$data .= file_get_contents($filepath) . "\r\n";
		return $data;
	}

	public function fetchExternalData($url, $data = null, $blocking = true, $stream = null, $allData = false, $headers = [], $files = [], $method = 'POST')
	{

		// Make sure the target does not exist.
		if ($stream) {
			File::delete($stream);
		}
		$options = array(
			'headers' => $headers,
			'data' => null
		);

		$options['method'] = '';

		if (!empty($files)) {
			// We have to use multipart form-data encoding with boundary since the
			// old drupal http client does not natively support posting files
			$boundary = md5(uniqid('', true));
			$options['method'] = $method;
			$encoded_data = '';
			foreach ($data as $key => $value) {
				if (empty($value)) {
					continue;
				}

				if (is_array($value)) {
					foreach ($value as $val) {
						$encoded_data .= "--$boundary\r\n";
						$encoded_data .= $this->h5p_multipart_enc_text($key . '[]', $val);
					}
				} else {
					$encoded_data .= "--$boundary\r\n";
					$encoded_data .= $this->h5p_multipart_enc_text($key, $value);
				}
			}

			// TODO: Should we check $_FILES[]['size'] (+ combiend size) before trying to post something we know is too large?
			foreach ($files as $name => $file) {
				if ($file === NULL) {
					continue;
				} elseif (is_array($file['name'])) {
					// Array of files uploaded (multiple)
					for ($i = 0; $i < count($file['name']); $i++) {
						$encoded_data .= "--$boundary\r\n";
						$encoded_data .= $this->h5p_multipart_enc_file($name . '[]', $file['tmp_name'][$i], $file['name'][$i], $file['type'][$i]);
					}
				} else {
					// Single file
					$encoded_data .= "--$boundary\r\n";
					$encoded_data .= $this->h5p_multipart_enc_file($name, $file['tmp_name'], $file['name'], $file['type']);
				}
			}

			$encoded_data .= "--$boundary--";
			$options['data'] = $encoded_data;
			$options['headers']['Content-Type'] = "multipart/form-data; boundary=$boundary";
		} elseif (!empty($data)) {
			$options['method'] = $method;
			$options['headers'] = array_merge(array(
				'Content-Type' => 'application/x-www-form-urlencoded'
			), $options['headers']);
			$options['data'] = $data;
		}

		if ($options['method'] == 'POST') {
			// Post
			$response = HttpFactory::getHttp()->post(
				$url,
				$options['data'],
				$options['headers']
			);
		} else {

			$response = HttpFactory::getHttp()->get(
				$url,
				$options['headers']
			);
		}

		if ($stream && empty($response->error)) {
			// Create file from data
			H5PeditorJoomlaStorage::saveFileTemporarily($response->body);
			return TRUE;
		}

		if ($allData) {
			return [
				'status' => intval($response->code),
				'data' => $response->body,
				'headers' => $response->headers,
			];
		} elseif (isset($response->error)) {
			$this->setErrorMessage(Text::_($response->error), 'failed-fetching-external-data');
			return NULL;
		}


		return $response->body;
	}


	public function getPlatformInfo()
	{
		return array(
			'name' => 'Joomla',
			'version' => JVERSION,
			'h5pVersion' => H5PJoomlaHelper::VERSION
		);
	}

	public function setLibraryTutorialUrl($library_name, $url)
	{
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'h5p_libraries',
			array('tutorial_url' => $url),
			array('name' => $library_name),
			array('%s'),
			array('%s')
		);
	}

	public function getLibraryFileUrl($libraryFolderName, $fileName)
	{
		return Uri::root() . 'media/com_h5p/h5p/libraries/' . $libraryFolderName . '/' . $fileName;
	}

	public function loadAddons()
	{
		$db = Factory::getDbo();

		return $db->setQuery(
			"SELECT l1.id as libraryId, l1.name as machineName,
              l1.major_version as majorVersion, l1.minor_version as minorVersion,
              l1.patch_version as patchVersion, l1.add_to as addTo,
              l1.preloaded_js as preloadedJs, l1.preloaded_css as preloadedCss,
			  l1.patch_version_in_folder_name
        	FROM #__h5p_libraries AS l1
        	LEFT JOIN #__h5p_libraries AS l2
          	ON l1.name = l2.name AND
            	(l1.major_version < l2.major_version OR
              	(l1.major_version = l2.major_version AND
               		l1.minor_version < l2.minor_version
				))
        	WHERE l1.add_to IS NOT NULL AND l2.name IS NULL"
		)->loadAssocList();
	}

	public function getMessages($type)
	{
		if (empty($this->messages[$type])) {
			return NULL;
		}
		$messages = $this->messages[$type];
		$this->messages[$type] = array();
		return $messages;
	}

	public function getLibraryConfig($libraries = NULL)
	{
		return defined('H5P_LIBRARY_CONFIG') ? H5P_LIBRARY_CONFIG : NULL;
	}

	public function loadLibraries()
	{
		$db = Factory::getDbo();
		$query = "SELECT id, name, title, major_version, minor_version, patch_version, runnable, restricted 
          FROM #__h5p_libraries 
          ORDER BY title ASC, major_version ASC, minor_version ASC";
		$db->setQuery((string) $query);
		$results = $db->loadObjectList();

		$libraries = array();
		foreach ($results as $library) {
			$libraries[$library->name][] = $library;
		}

		return $libraries;
	}


	public function isInDevMode()
	{
		return (bool) H5PJoomlaHelper::getSetting('h5p_dev_mode', '0');
	}

	public function insertContent($content, $contentMainId = NULL)
	{

		return $this->updateContent($content);
	}

	function array_to_obj($array, &$obj)
	{
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$obj->$key = new \stdClass();
				$this->array_to_obj($value, $obj->$key);
			} else {
				$obj->$key = $value;
			}
		}
		return $obj;
	}

	public function updateContent($content, $contentMainId = NULL)
	{
		$db = Factory::getDbo();

		$metadata = (array)$content['metadata'];
		$table = '#__h5p_contents';

		$format = array();
		$data = array_merge(\H5PMetadata::toDBArray($metadata, true, true, $format), array(
			'updated_at' => (new Date('now'))->toSQL(),
			'parameters' => $content['params'],
			'embed_type' => 'div', // TODO: Determine from library?
			'library_id' => $content['library']['libraryId'],
			'filtered' => '',
			'slug' => '',
			'disable' => $content['disable']
		));

		$db_object = new \stdClass();
		$this->array_to_obj($data, $db_object);

		if (!isset($content['id'])) {
			// Insert new content
			$db_object->created_at = $data['updated_at'];
			$db_object->user_id = Factory::getApplication()->getIdentity()->id;

			$res = $db->insertObject('#__h5p_contents', $db_object, 'id');
			$content['id'] = $db->insertid(); //$db_object->id;
			$event_type = 'create';
		} else {
			// Update existing content
			$db_object->id = $content['id'];
			$db->updateObject('#__h5p_contents', $db_object, 'id');
			$event_type = 'update';
		}

		// Log content create/update/upload
		if (!empty($content['uploaded'])) {
			$event_type .= ' upload';
		}
		new H5PEventHelper(
			'content',
			$event_type,
			$content['id'],
			$metadata['title'],
			$content['library']['machineName'],
			$content['library']['majorVersion'] . '.' . $content['library']['minorVersion']
		);

		return $content['id'];
	}

	public function updateContentFields($id, $fields)
	{
		$db = Factory::getDbo();
		$db_object = new \stdClass();
		$this->array_to_obj($fields, $db_object);
		$db_object->id = $id;
		$db->updateObject('#__h5p_contents', $db_object, 'id', TRUE);
	}

	function current_time()
	{
		$type = 'Y-m-d H:i:s';

		$timezone = new \DateTimeZone('UTC');
		$datetime = new \DateTime('now', $timezone);

		return $datetime->format($type);
	}

	public function getOption($name, $default = FALSE)
	{
		if ($name === 'site_uuid') {
			$name = 'h5p_site_uuid'; // Make up for old core bug
		}
		return H5PJoomlaHelper::getSetting('h5p_' . $name, $default);
	}

	public function setOption($name, $value)
	{
		if ($name === 'site_uuid') {
			$name = 'h5p_site_uuid'; // Make up for old core bug
		}
		$var = $this->getOption($name);
		$name = 'h5p_' . $name; // Always prefix to avoid conflicts

		H5PJoomlaHelper::setSetting($name, $value);
	}

	public function getLibraryUsage($id, $skipContent = FALSE)
	{
		$db = Factory::getDbo();
		$query = sprintf("SELECT COUNT(distinct c.id)
			  FROM #__h5p_libraries l
			  JOIN #__h5p_contents_libraries cl ON l.id = cl.library_id
			  JOIN #__h5p_contents c ON cl.content_id = c.id
			  WHERE l.id = %d", $id);
		$db->setQuery((string) $query);
		$result1 = $db->loadResult();

		$query = sprintf("SELECT COUNT(*)
			  FROM #__h5p_libraries_libraries
			  WHERE required_library_id = %d", $id);
		$db->setQuery((string) $query);
		$result2 = $db->loadResult();

		return array(
			'content' => $skipContent ? -1 : intval($result1),
			'libraries' => intval($result2)
		);
	}

	public function resetContentUserData($contentId)
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__h5p_contents_user_data'))
			->set(array(
				$db->quoteName('updated_at') . ' = ' . $db->quote((new Date('now'))->toSQL()),
				$db->quoteName('data') . " = 'RESET'"
			))
			->where(array(
				$db->quoteName('content_id') . " = " . $contentId,
				$db->quoteName('invalidate') . " = 1"
			));

		$db->setQuery($query);

		$db->execute();
	}

	public function loadLibrarySemantics($name, $majorVersion, $minorVersion)
	{
		$db = Factory::getDbo();;

		if ($this->isInDevMode()) {
			$semantics = $this->getSemanticsFromFile($name, $majorVersion, $minorVersion);
		} else {
			$db->setQuery(
				sprintf(
					"SELECT semantics
				FROM #__h5p_libraries
				WHERE name = %s
				AND major_version = %d
				AND minor_version = %d",
					$db->quote($name),
					$majorVersion,
					$minorVersion
				)
			);
			$semantics = $db->LoadResult();
		}
		return ($semantics === FALSE ? NULL : $semantics);
	}

	public function alterLibrarySemantics(&$semantics, $name, $majorVersion, $minorVersion)
	{
		$app = Factory::getApplication();
		$app->triggerEvent('onh5p_alter_library_semantics', array(&$semantics, $name, $majorVersion, $minorVersion));
	}


	public function deleteLibrary($library)
	{

		// Delete library files
		\H5PCore::deleteFileTree($this->getH5pPath() . '/libraries/' . $library->name . '-' . $library->major_version . '.' . $library->minor_version);

		// Remove library data from database
		$db = Factory::getDbo();
		$query = "DELETE FROM #__h5p_libraries_libraries WHERE library_id = " . $library->id;
		$db->setQuery((string) $query);
		$db->execute();
		$query = "DELETE FROM #__h5p_libraries_languages WHERE library_id = " . $library->id;
		$db->setQuery((string) $query);
		$db->execute();
		$query = "DELETE FROM #__h5p_libraries WHERE id = " . $library->id;
		$db->setQuery((string) $query);
		$db->execute();
	}

	public function loadContent($id)
	{
		$db = Factory::getDbo();

		$db->setQuery(
			sprintf(
				"SELECT hc.id
				  , hc.title
				  , hc.parameters AS params
				  , hc.filtered
				  , hc.slug AS slug
				  , hc.user_id
				  , hc.embed_type AS embedType
				  , hc.disable
				  , hl.id AS libraryId
				  , hl.name AS libraryName
				  , hl.major_version AS libraryMajorVersion
				  , hl.minor_version AS libraryMinorVersion
				  , hl.embed_types AS libraryEmbedTypes
				  , hl.fullscreen AS libraryFullscreen
				  , hc.authors AS authors
				  , hc.source AS source
				  , hc.year_from AS yearFrom
				  , hc.year_to AS yearTo
				  , hc.license AS license
				  , hc.license_version AS licenseVersion
				  , hc.license_extras AS licenseExtras
				  , hc.author_comments AS authorComments
				  , hc.changes AS changes
				  , hc.default_language AS defaultLanguage
				  , hc.a11y_title AS a11yTitle
			FROM #__h5p_contents hc
			JOIN #__h5p_libraries hl ON hl.id = hc.library_id
			WHERE hc.id = %d",
				$id
			)
		);

		$content = $db->loadAssocList()[0];

		if ($content !== NULL) {
			$content['metadata'] = array();
			$metadata_structure = array('title', 'authors', 'source', 'yearFrom', 'yearTo', 'license', 'licenseVersion', 'licenseExtras', 'authorComments', 'changes', 'defaultLanguage', 'a11yTitle');
			foreach ($metadata_structure as $property) {
				if (!empty($content[$property])) {
					if ($property === 'authors' || $property === 'changes') {
						$content['metadata'][$property] = json_decode($content[$property]);
					} else {
						$content['metadata'][$property] = $content[$property];
					}
					if ($property !== 'title') {
						unset($content[$property]); // Unset all except title
					}
				}
			}
		}

		return $content;
	}

	public function loadContentDependencies($id, $type = NULL)
	{
		$db = Factory::getDbo();
		$query =
			"SELECT hl.id
				  , hl.name AS machineName
				  , hl.major_version AS majorVersion
				  , hl.minor_version AS minorVersion
				  , hl.patch_version AS patchVersion
				  , hl.preloaded_css AS preloadedCss
				  , hl.preloaded_js AS preloadedJs
				  , hl.patch_version_in_folder_name
				  , hcl.drop_css AS dropCss
				  , hcl.dependency_type AS dependencyType
			FROM #__h5p_contents_libraries hcl
			JOIN #__h5p_libraries hl ON hcl.library_id = hl.id
			WHERE hcl.content_id = %d";
		$queryArgs = array($id);

		if ($type !== NULL) {
			$query .= " AND hcl.dependency_type = '%s'";
			$queryArgs[] = $type;
		}

		$query .= " ORDER BY hcl.weight";
		$db->setQuery(call_user_func_array('sprintf', array_merge(array($query), $queryArgs)));


		return $db->loadAssocList();
	}

	public function clearFilteredParameters($library_ids)
	{
		$db = Factory::getDbo();

		$db->setQuery(sprintf(
			"UPDATE #__h5p_contents
			SET filtered = NULL
			WHERE library_id IN (%s)",
			$db->quote(implode(',', $library_ids))
		));
		$db->execute();
	}

	public function getNumNotFiltered()
	{
		$db = Factory::getDbo();
		$query = "SELECT COUNT(id) FROM #__h5p_contents WHERE filtered = ''";
		$db->setQuery((string) $query);
		$result = $db->loadResult();
		return (int) $result;
	}

	public function getNumContent($library_id, $skip = NULL)
	{
		$skip_query = empty($skip) ? '' : " AND id NOT IN ($skip)";
		$db = Factory::getDbo();
		$query = sprintf(
			"SELECT COUNT(id)
			 FROM #__h5p_contents
			WHERE library_id = %d " . $skip_query,
			$library_id
		);
		$db->setQuery((string) $query);
		$result = $db->loadResult();

		return (int)$result;
	}

	public function isContentSlugAvailable($slug)
	{
		$db = Factory::getDbo();
		$db->setQuery(sprintf("SELECT slug FROM #__h5p_contents WHERE slug = %s", $db->quote($slug)));
		return !$db->loadResult();
	}

	public function getLibraryStats($type)
	{
		$db = Factory::getDbo();
		$count = array();

		$db->setQuery(sprintf("
			SELECT library_name AS name,
				library_version AS version,
				num
			FROM #__h5p_counters
			WHERE type = %s
			", $db->quote($type)));
		$results = $db->loadObjectList();

		// Extract results
		foreach ($results as $library) {
			$count[$library->name . ' ' . $library->version] = $library->num;
		}

		return $count;
	}

	public function getNumAuthors()
	{
		$db = Factory::getDbo();
		$db->setQuery("
			SELECT COUNT(DISTINCT user_id)
			FROM #__h5p_contents
			");
		return $db->loadResult();
	}

	public function saveCachedAssets($key, $libraries)
	{
		$db = Factory::getDbo();
		foreach ($libraries as $library) {
			// TODO: Avoid errors if they already exists...
			$db_object = new \stdClass;
			$db_object->library_id = isset($library['id']) ? $library['id'] : $library['libraryId'];
			$db_object->hash = $key;
			$db->insertObject("#__h5p_libraries_cachedassets", $db_object);
		}
	}

	public function deleteCachedAssets($library_id)
	{
		$db = Factory::getDbo();

		// Get all the keys so we can remove the files

		$db->setQuery(sprintf("
        SELECT hash
          FROM #__h5p_libraries_cachedassets
         WHERE library_id = %d
        ", $library_id));

		$results = $db->loadObjectList();

		// Remove all invalid keys
		$hashes = array();
		foreach ($results as $key) {
			$hashes[] = $key->hash;

			$db->setQuery("DELETE FROM #__h5p_libraries_cachedassets WHERE hash = " . $db->quote($key->hash));
			$db->execute();
		}

		return $hashes;
	}

	public function getLibraryContentCount()
	{
		$db = Factory::getDbo();

		$count = array();

		// Find number of content per library
		$db->setQuery("
				SELECT l.name, l.major_version, l.minor_version, COUNT(*) AS count
				FROM #__h5p_contents c, #__h5p_libraries l
				WHERE c.library_id = l.id
				GROUP BY l.name, l.major_version, l.minor_version
				");
		$results = $db->loadObjectList();

		// Extract results
		foreach ($results as $library) {
			$count[$library->name . ' ' . $library->major_version . '.' . $library->minor_version] = $library->count;
		}

		return $count;
	}

	public function afterExportCreated($content, $filename)
	{
		//		    delete_transient('dirsize_cache');
	}

	private static function currentUserCanEdit($contentUserId)
	{
		if (H5PJoomlaHelper::current_user_can('edit_others_h5p_contents')) {
			return TRUE;
		}

		$user_id = Factory::getApplication()->getIdentity() != null ? Factory::getApplication()->getIdentity()->id : 0;
		return $user_id == $contentUserId;
	}

	public function hasPermission($permission, $contentUserId = NULL)
	{
		switch ($permission) {
			case \H5PPermission::DOWNLOAD_H5P:
			case \H5PPermission::EMBED_H5P:
			case \H5PPermission::COPY_H5P:
				return self::currentUserCanEdit($contentUserId);

			case \H5PPermission::CREATE_RESTRICTED:
			case \H5PPermission::UPDATE_LIBRARIES:
				return H5PJoomlaHelper::current_user_can('manage_h5p_libraries');

			case \H5PPermission::INSTALL_RECOMMENDED:
				return H5PJoomlaHelper::current_user_can('install_recommended_h5p_libraries');
		}
		return FALSE;
	}

	public function replaceContentTypeCache($contentTypeCache)
	{
		$db = Factory::getDbo();
		// Replace existing content type cache
		$db->setQuery("TRUNCATE TABLE #__h5p_libraries_hub_cache");
		$db->execute();

		foreach ($contentTypeCache->contentTypes as $ct) {
			// Insert into db
			$db_object = new \stdClass;

			$db_object->machine_name      = $ct->id;
			$db_object->major_version     = $ct->version->major;
			$db_object->minor_version     = $ct->version->minor;
			$db_object->patch_version     = $ct->version->patch;
			$db_object->h5p_major_version = $ct->coreApiVersionNeeded->major;
			$db_object->h5p_minor_version = $ct->coreApiVersionNeeded->minor;
			$db_object->title             = $ct->title;
			$db_object->summary           = $ct->summary;
			$db_object->description       = $ct->description;
			$db_object->icon              = $ct->icon;
			$db_object->created_at        = self::dateTimeToTime($ct->createdAt);
			$db_object->updated_at        = self::dateTimeToTime($ct->updatedAt);
			$db_object->is_recommended    = $ct->isRecommended === TRUE ? 1 : 0;
			$db_object->popularity        = $ct->popularity;
			$db_object->screenshots       = json_encode($ct->screenshots);
			$db_object->license           = json_encode(isset($ct->license) ? $ct->license : array());
			$db_object->example           = $ct->example;
			$db_object->tutorial          = isset($ct->tutorial) ? $ct->tutorial : '';
			$db_object->keywords          = json_encode(isset($ct->keywords) ? $ct->keywords : array());
			$db_object->categories        = json_encode(isset($ct->categories) ? $ct->categories : array());
			$db_object->owner             = $ct->owner;

			$db->insertObject('#__h5p_libraries_hub_cache', $db_object);
		}
	}

	public static function dateTimeToTime($datetime)
	{
		$dt = new \DateTime($datetime);
		return $dt->getTimestamp();
	}

	public function libraryHasUpgrade($library)
	{
		$db = Factory::getDbo();

		$query = sprintf(
			"SELECT id
			FROM #__h5p_libraries
			WHERE name = %s
			AND (major_version > %d
			OR (major_version = %d AND minor_version > %d))
			LIMIT 1",
			$db->quote($library['machineName']),
			$library['majorVersion'],
			$library['majorVersion'],
			$library['minorVersion']
		);
		$db->setQuery($query);
		return $db->loadResult() !== NULL;
	}

	public function getAdminUrl()
	{
		return Uri::root() . 'administrator/index.php?option=com_h5p&view=libraries';
	}

	// Magic stuff not used, we do not support library development mode.
	public function unlockDependencyStorage()
	{
		$db = Factory::getDbo();
		$db->setQuery('UNLOCK TABLES')->execute();
	}

	public function lockDependencyStorage()
	{
		$db = Factory::getDbo();
		$db->setQuery('LOCK TABLES #__h5p_libraries_libraries write, #__h5p_libraries as hl read')->execute();
	}

	public function replaceContentHubMetadataCache($metadata, $lang = 'en')
	{
		$db = Factory::getDbo();

		$result = $db->setQuery(sprintf("
						SELECT count(language) 
						FROM #__h5p_content_hub_metadata_cache
						WHERE language = %s", $db->quote($lang)))
			->loadResult();

		if ($result != 0) {
			$db->setQuery(sprintf(
				"
			UPDATE #__h5p_content_hub_metadata_cache SET language = %s
			WHERE language = %s",
				$db->quote($lang),
				$db->quote($lang)
			));
		} else {
			$db->setQuery(sprintf(
				"
			INSERT INTO #__h5p_content_hub_metadata_cache(language, json) VALUES ( %s, %s)",
				$db->quote($lang),
				$db->quote($metadata)
			));
		}


		$db->execute();
	}

	public function getContentHubMetadataCache($lang = 'en')
	{
		$db = Factory::getDbo();
		$db->setQuery(sprintf(
			"
			SELECT json
			FROM #__h5p_content_hub_metadata_cache
			WHERE language = %s",
			$db->quote($lang)
		));

		$results = $db->loadAssoc();

		$ret = null;
		if ($results) {
			$ret = $results['json'];
		}

		return $ret;
	}

	public function getContentHubMetadataChecked($lang = 'en')
	{
		$db = Factory::getDbo();
		$db->setQuery(sprintf(
			"
			SELECT last_checked
			FROM #__h5p_content_hub_metadata_cache
			WHERE language = %s",
			$db->quote($lang)
		));

		$results = $db->loadResult();

		if (!empty($results)) {
			$time = new \DateTime();
			$time->setTimestamp($results);
			$results = $time->format("D, d M Y H:i:s \G\M\T");
		}

		return $results;
	}

	public function setContentHubMetadataChecked($time, $lang = 'en')
	{
		$db = Factory::getDbo();

		$db->setQuery(sprintf(
			"
			UPDATE #__h5p_content_hub_metadata_cache
			SET last_checked = %d
			WHERE language = %s",
			$time,
			$db->quote($lang)
		));

		$db->execute();

		return true;
	}

	public function t($message, $replacements = array())
	{
		// Insert !var as is, escape @var and emphasis %var.
		foreach ($replacements as $key => $replacement) {
			if ($key[0] === '@') {
				$replacements[$key] = Text::_($replacement);
			} elseif ($key[0] === '%') {
				$replacements[$key] = '<em>' . Text::_($replacement) . '</em>';
			}
		}
		$message = preg_replace('/(!|@|%)[a-z0-9-]+/i', '%s', $message);

		// Assumes that replacement vars are in the correct order.
		return vsprintf($this->translate($message), $replacements);
	}

	private function translate($text)
	{
		return $text;
		$transl = null;
		$dict = array('Fullscreen' => 'Во весь экран');
		$transl = $dict[$text];
		return $transl ? $transl : $text;
	}
}
