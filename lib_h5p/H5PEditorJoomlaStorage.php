<?php

/**
 * @package    Lib_h5p
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */
defined('_JEXEC') or die;

namespace VB\Component\H5P;

use Joomla\CMS\Factory;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;
use Joomla\CMS\Filesystem\File;

class H5PEditorJoomlaStorage implements \H5peditorStorage
{
	public function getLanguage($machineName, $majorVersion, $minorVersion, $language)
	{
		// FIXME: Get current user language.
		$db = Factory::getDbo();
		$db->setQuery(sprintf(
			"SELECT hlt.translation
				FROM #__h5p_libraries_languages hlt
				JOIN #__h5p_libraries hl
					ON hl.id = hlt.library_id
				WHERE hl.name = %s
					AND hl.major_version = %d
					AND hl.minor_version = %d
					AND hlt.language_code = %s",
			$db->quote($machineName),
			(int) $majorVersion,
			(int) $minorVersion,
			$db->quote($language)
		));
		$lang = $db->loadResult();
		return $lang;
	}

	public function keepFile($fileId)
	{
		$db = Factory::getDbo();
		$db->setQuery(sprintf("DELETE FROM #__h5p_tmpfiles WHERE path = %s", $db->quote($fileId)))->execute();
	}

	public function getLibraries($libraries = null)
	{
		$super_user = H5PJoomlaHelper::current_user_can('manage_h5p_libraries');
		$db = Factory::getDbo();

		if ($libraries !== NULL) {
			// Get details for the specified libraries only.
			$librariesWithDetails = array();
			foreach ($libraries as $library) {
				// Look for library
				$query = sprintf(
					"SELECT title, runnable, restricted, tutorial_url, metadata_settings
					FROM #__h5p_libraries
					WHERE name = %s
					AND major_version = %d
					AND minor_version = %d
					AND semantics IS NOT NULL",
					$db->quote($library->name),
					$library->majorVersion,
					$library->minorVersion
				);
				$db->setQuery((string) $query);

				$details = $db->loadObjectList();
				if ($details) {
					// Library found, add details to list
					$library->tutorialUrl = $details[0]->tutorial_url;
					$library->title = $details[0]->title;
					$library->runnable = $details[0]->runnable;
					$library->restricted = $super_user ? FALSE : ($details[0]->restricted === '1' ? TRUE : FALSE);
					$library->metadataSettings = json_decode($details[0]->metadata_settings);
					$librariesWithDetails[] = $library;
				}
			}

			// Done, return list with library details
			return $librariesWithDetails;
		}

		// Load all libraries
		$libraries = array();
		$query = sprintf(
			"SELECT name,
					title,
					major_version AS majorVersion,
					minor_version AS minorVersion,
					tutorial_url AS tutorialUrl,
					restricted,
					metadata_settings AS metadataSettings
			FROM #__h5p_libraries
			WHERE runnable = 1
			AND semantics IS NOT NULL
			ORDER BY title"
		);
		$db->setQuery((string) $query);
		$libraries_result = $db->loadObjectList();
		foreach ($libraries_result as $library) {
			// Make sure we only display the newest version of a library.
			foreach ($libraries as $key => $existingLibrary) {
				if ($library->name === $existingLibrary->name) {

					// Found library with same name, check versions
					if (($library->majorVersion === $existingLibrary->majorVersion &&
							$library->minorVersion > $existingLibrary->minorVersion) ||
						($library->majorVersion > $existingLibrary->majorVersion)
					) {
						// This is a newer version
						$existingLibrary->isOld = TRUE;
					} else {
						// This is an older version
						$library->isOld = TRUE;
					}
				}
			}

			// Convert from string to object
			$library->metadataSettings = json_decode($library->metadataSettings);

			// Check to see if content type should be restricted
			$library->restricted = $super_user ? FALSE : ($library->restricted === '1' ? TRUE : FALSE);

			// Add new library
			$libraries[] = $library;
		}

		return $libraries;
	}

	public function alterLibraryFiles(&$files, $libraries)
	{
		$plugin = H5PJoomlaHelper::get_instance();
		$plugin->alter_assets($files, $libraries, 'editor');
	}

	public static function saveFileTemporarily($data, $move_file = false)
	{
		// Get temporary path
		$plugin = H5PJoomlaHelper::get_instance();
		$interface = $plugin->get_h5p_instance('interface');

		$path = $interface->getUploadedH5pPath();

		if ($move_file) {
			// Move so core can validate the file extension.
			rename($data, $path);
		} else {
			// Create file from data
			File::write($path, $data);
		}

		return (object) array(
			'dir' => dirname($path),
			'fileName' => basename($path)
		);
	}

	public static function markFileForCleanup($file, $content_id)
	{
		$db = Factory::getDbo();
		$plugin = H5PJoomlaHelper::get_instance();
		$path   = $plugin->get_h5p_path();

		if (empty($content_id)) {
			// Should be in editor tmp folder
			$path .= '/editor';
		} else {
			// Should be in content folder
			$path .= '/content/' . $content_id;
		}

		// Add file type to path
		$path .= '/' . $file->getType() . 's';

		// Add filename to path
		$path .= '/' . $file->getName();

		// Keep track of temporary files so they can be cleaned up later.

		$db->setQuery(sprintf(
			"INSERT INTO #__h5p_tmpfiles (path, created_at) VALUES (%s, %d)",
			$db->quote($path),
			time()
		));
		$db->execute();
	}

	public static function removeTemporarilySavedFiles($filePath)
	{

		if (is_dir($filePath)) {
			\H5PCore::deleteFileTree($filePath);
		} else {
			unlink($filePath);
		}
	}

	public function getAvailableLanguages($machineName, $majorVersion, $minorVersion)
	{
		$db = Factory::getDbo();

		$db->setQuery(sprintf(
			"SELECT hll.language_code
			 FROM #__h5p_libraries_languages hll
			 JOIN #__h5p_libraries hl
			   ON hll.library_id = hl.id
			WHERE hl.name = %s
			  AND hl.major_version = %d
			  AND hl.minor_version = %d",
			$db->quote($machineName),
			$majorVersion,
			$minorVersion
		));

		$results = $db->loadObjectList();
		$codes = array('en'); // Semantics is 'en' by default.
		foreach ($results as $result) {
			$codes[] = $result->language_code;
		}
		return $codes;
	}
}
