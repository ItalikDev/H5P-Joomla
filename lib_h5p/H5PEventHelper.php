<?php

/**
 * @package    Lib_h5p
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

namespace VB\Component\H5P\Administrator\Helper;

use Joomla\CMS\Factory;

class H5PEventHelper extends \H5PEventBase
{
  private $user;

  /**
   * Adds event type, h5p library and timestamp to event before saving it.
   *
   * @param string $type
   *  Name of event to log
   * @param string $library
   *  Name of H5P library affacted
   */

  function __construct($type, $sub_type = NULL, $content_id = NULL, $content_title = NULL, $library_name = NULL, $library_version = NULL)
  {

    // Track the user who initiated the event as well
    $current_user = Factory::getUser(); //Factory::getApplication()->getIdentity();
    $this->user = $current_user != null ? $current_user->id : 0;
    parent::__construct($type, $sub_type, $content_id, $content_title, $library_name, $library_version);
  }

  /**
   * Store the event.
   */
  protected function save()
  {
    // Insert user into DB
    $data = $this->getDataArray();
    // $format = $this->getFormatArray();


    $db_object = new \stdClass;
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $db_object->$key = new \stdClass();
        $this->array_to_obj($value, $db_object->$key);
      } else {
        $db_object->$key = $value;
      }
    }

    // Add user
    $db_object->user_id = $this->user;
    $db = Factory::getDbo();

    $db->insertObject('#__h5p_events', $db_object, 'user_id');
    $this->id = $db_object->user_id;
    return $this->id;
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

  /**
   * Count number of events.
   */
  protected function saveStats()
  {

    $type = $this->type . ' ' . $this->sub_type;
    $db = Factory::getDbo();

    $query = sprintf(
      "SELECT num
           FROM #__h5p_counters
          WHERE type = '%s'
            AND library_name = '%s'
            AND library_version = '%s'
        ",
      $type,
      $this->library_name,
      $this->library_version
    );
    $db->setQuery($query);
    $current_num  = $db->loadResult();

    $db_object = new \stdClass;

    if ($current_num === NULL) {
      // Insert
      $db_object = new \stdClass;
      $db_object->type = $type;
      $db_object->library_name = $this->library_name;
      $db_object->library_version = $this->library_version;
      $db_object->num = 1;
      $db->insertObject('#__h5p_counters', $db_object);
    } else {
      // Update num+1
      $query = sprintf("UPDATE #__h5p_counters
              SET num = num + 1
            WHERE type = '%s'
              AND library_name = '%s'
              AND library_version = '%s'
      		", $type, $this->library_name, $this->library_version);
      $db->setQuery($query);
      $db->execute();
    }
  }
}
