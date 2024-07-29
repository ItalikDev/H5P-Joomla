<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;

//JLoader::register('H5pHelper', JPATH_ADMINISTRATOR . '/components/com_h5p/helpers/h5p.php');

class H5pController extends \Joomla\CMS\MVC\Controller\BaseController

{
    /**
     * The default view for the display method.
     *
     * @var string
     * @since 12.2
     */

    public function display($cachable = false, $urlparams = false)
    {
        $view = Factory::getApplication()->input->getCmd('view', 'contents');

        Factory::getApplication()->input->set('view', $view);

        parent::display($cachable, $urlparams);

        return $this;
    }

    public function ajax_restrict_access()
    {
        H5PJoomlaHelper::get_instance();

        $library_id = filter_input(INPUT_GET, 'id');
        $restricted = filter_input(INPUT_GET, 'restrict');
        $restrict = ($restricted === '1');

        $token_id = filter_input(INPUT_GET, 'token_id');
        if (!\H5PCore::validToken( 'h5p_library_' . $token_id, filter_input(INPUT_GET, 'token')) || (!$restrict && $restricted !== '0')) {
            exit;
          }
        $db = Factory::getDbo();
        $query = "UPDATE #__h5p_libraries SET restricted = " . $restricted . " WHERE id = " . $library_id;
        $db->setQuery((string) $query);
        $db->execute();

        header('Content-type: application/json');
        echo json_encode(
            array(
                'url' => Uri::root() . '/administrator/index.php?option=com_h5p&task=ajax_restrict_access' .
                    '&id=' . $library_id .
                    '&restrict=' . ($restrict ? 0 : 1) .
                    '&token_id=' . $token_id .
                    '&token=' . \H5PCore::createToken('h5p_library_' . $token_id),
            )
        );
        exit;
    }

    public function h5p_my_results()
    { 
        $user = Factory::getApplication()->getIdentity();
        $plugin = H5PJoomlaHelper::get_instance();
        $plugin->print_results(null, $user->id);
    }

    private function remove_old_tmp_files()
    {
        $db = Factory::getDbo();
        $plugin = H5PJoomlaHelper::get_instance();

        $older_than = time() - 86400;
        $num = 0; // Number of files deleted

        // Locate files not saved in over a day
        $files = $db->setQuery(
            sprintf(
                "SELECT path
               FROM #__h5p_tmpfiles
              WHERE created_at < %d",
                $older_than
            )
        )->loadObjectList();

        // Delete files from file system
        foreach ($files as $file) {
            if (@unlink($file->path)) {
                $num++;
            }
        }

        // Remove from tmpfiles table
        $db->setQuery(sprintf(
            "DELETE FROM #__h5p_tmpfiles
              WHERE created_at < %d",
            $older_than
        ))->execute();

        // Old way of cleaning up tmp files. Needed as a transitional fase and it doesn't really harm to have it here any way.
        $h5p_path = $plugin->get_h5p_path();
        $editor_path = $h5p_path . DIRECTORY_SEPARATOR . 'editor';
        if (is_dir($h5p_path) && is_dir($editor_path)) {
            $dirs = glob($editor_path . DIRECTORY_SEPARATOR . '*');
            if (!empty($dirs)) {
                foreach ($dirs as $dir) {
                    if (!is_dir($dir)) {
                        continue;
                    }

                    $files = glob($dir . DIRECTORY_SEPARATOR . '*');
                    if (empty($files)) {
                        continue;
                    }

                    foreach ($files as $file) {
                        if (filemtime($file) < $older_than) {
                            // Not modified in over a day
                            if (unlink($file)) {
                                $num++;
                            }
                        }
                    }
                }
            }
        }
    }

    public function get_library_updates()
    {
        $plugin = H5PJoomlaHelper::get_instance();
        if ($plugin->getSetting('h5p_hub_is_enabled', TRUE) || $plugin->getSetting('h5p_send_usage_statistics', TRUE)) {
            $core = $plugin->get_h5p_instance('core');
            $core->fetchLibrariesMetadata();
        }
    }

    public function remove_old_log_events()
    {
        $db = Factory::getDbo();
        H5PJoomlaHelper::get_instance();

        $older_than = (time() - \H5PEventBase::$log_time);

        $db->setQuery( sprintf("
            DELETE FROM #__h5p_events
                      WHERE created_at < %d
            ", $older_than));
    }

    public function cron()
    {
        $this->remove_old_tmp_files();
        $this->get_library_updates();
        $this->remove_old_log_events();
    }
}
