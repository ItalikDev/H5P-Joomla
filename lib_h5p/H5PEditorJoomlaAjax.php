<?php

/**
 * @package    Lib_h5p
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

namespace VB\Component\H5P;

use Joomla\CMS\Factory;

class H5PEditorJoomlaAjax implements \H5PEditorAjaxInterface
{

  public function getLatestLibraryVersions()
  {
    $db = Factory::getDbo();

    // Get latest version of local libraries
    $major_versions_sql =
      "SELECT hl.name,
                MAX(hl.major_version) AS major_version
           FROM #__h5p_libraries hl
          WHERE hl.runnable = 1
       GROUP BY hl.name";

    $minor_versions_sql =
      "SELECT hl2.name,
                 hl2.major_version,
                 MAX(hl2.minor_version) AS minor_version
            FROM ({$major_versions_sql}) hl1
            JOIN #__h5p_libraries hl2
              ON hl1.name = hl2.name
             AND hl1.major_version = hl2.major_version
        GROUP BY hl2.name, hl2.major_version";

    $db->setQuery("SELECT hl4.id,
                hl4.name AS machine_name,
                hl4.title,
                hl4.major_version,
                hl4.minor_version,
                hl4.patch_version,
                hl4.restricted,
                hl4.has_icon
           FROM ({$minor_versions_sql}) hl3
           JOIN #__h5p_libraries hl4
             ON hl3.name = hl4.name
            AND hl3.major_version = hl4.major_version
            AND hl3.minor_version = hl4.minor_version");

    return $db->loadObjectList();
  }

  public function getContentTypeCache($machineName = NULL)
  {
    $db = Factory::getDbo();

    // Return info of only the content type with the given machine name
    if ($machineName) {
      $db->setQuery(sprintf(
        "SELECT id, is_recommended
           FROM #__h5p_libraries_hub_cache
          WHERE machine_name = %s",
        $db->quote($machineName)
      ));
      return $db->loadObjectList()[0];
    }

    $db->setQuery("SELECT * FROM #__h5p_libraries_hub_cache");
    return $db->loadObjectList();
  }

  public function getAuthorsRecentlyUsedLibraries()
  {
    $db = Factory::getDbo();
    $recently_used = array();

    $db->setQuery(sprintf(
      "SELECT library_name, max(created_at) AS max_created_at
         FROM #__h5p_events
        WHERE type='content' AND sub_type = 'create' AND user_id = %d
     GROUP BY library_name
     ORDER BY max_created_at DESC",
      Factory::getUser()->id
    ));

    $result = $db->loadObjectList();

    foreach ($result as $row) {
      $recently_used[] = $row->library_name;
    }

    return $recently_used;
  }

  public function validateEditorToken($token)
  {
    return \H5PCore::validToken('h5p_editor_ajax', $token);
  }

  public function getTranslations($libraries, $language_code)
  {
    global $wpdb;

    $querylibs = '';
    foreach ($libraries as $lib) {
      $querylibs .= (empty($querylibs) ? '' : ',') . '%s';
    }

    array_unshift($libraries, $language_code);

    $result = $wpdb->get_results($wpdb->prepare(
      "SELECT hll.translation, CONCAT(hl.name, ' ', hl.major_version, '.', hl.minor_version) AS lib
         FROM {$wpdb->prefix}h5p_libraries hl
         JOIN {$wpdb->prefix}h5p_libraries_languages hll ON hll.library_id = hl.id
        WHERE hll.language_code = %s
          AND CONCAT(hl.name, ' ', hl.major_version, '.', hl.minor_version) IN ({$querylibs})",
      $libraries
    ));

    $translations = array();
    foreach ($result as $row) {
      $translations[$row->lib] = $row->translation;
    }
    return $translations;
  }
}
