<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

namespace VB\Component\H5P\Administrator\Helper;

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/h5p/h5p-php-library/h5p.classes.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-php-library/h5p-development.class.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-php-library/h5p-event-base.class.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-php-library/h5p-file-storage.interface.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-php-library/h5p-default-storage.class.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-php-library/h5p-metadata.class.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-php-library/h5p-development.class.php';

require_once JPATH_LIBRARIES . '/h5p/h5p-editor-php-library/h5peditor.class.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-editor-php-library/h5peditor-file.class.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-editor-php-library/h5peditor-storage.interface.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-editor-php-library/h5peditor-ajax.class.php';
require_once JPATH_LIBRARIES . '/h5p/h5p-editor-php-library/h5peditor-ajax.interface.php';

require_once JPATH_LIBRARIES . '/h5p/H5PFrameworkHelper.php';
require_once JPATH_LIBRARIES . '/h5p/H5PEventHelper.php';
require_once JPATH_LIBRARIES . '/h5p/H5PEditorJoomlaStorage.php';
require_once JPATH_LIBRARIES . '/h5p/H5PEditorJoomlaAjax.php';

require_once JPATH_ADMINISTRATOR . '/components/com_h5p/helpers/h5pcontentquery.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Date\Date;

PluginHelper::importPlugin('h5p');

class H5PJoomlaHelper
{

    const VERSION = '1.0.2';
    protected static $instance = null;
    protected static $interface;
    protected static $core;
    protected static $settings = null;

    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function get_settings()
    {
        return self::$settings;
    }

    public function alter_assets(&$files, &$dependencies, $embed)
    {

        $app = Factory::getApplication();

        $libraries = array();
        foreach ($dependencies as $dependency) {
            $libraries[$dependency['machineName']] = array(
                'machineName' => $dependency['machineName'],
                'majorVersion' => $dependency['majorVersion'],
                'minorVersion' => $dependency['minorVersion'],
            );
        }

        $app->triggerEvent('onh5p_alter_library_scripts', array(&$files['scripts'], $libraries, $embed));

        $app->triggerEvent('onh5p_alter_library_styles', array(&$files['styles'], $libraries, $embed));
    }

    public function get_h5p_instance($type)
    {
        if (empty(self::$interface)) {
            self::$interface = new \VB\Component\H5P\H5PFrameworkHelper();
            $language = $this->get_language();
            self::$core = new \H5PCore(self::$interface, $this->get_h5p_path(), $this->get_h5p_url(), $language, $this->getSetting('h5p_export'));
            self::$core->aggregateAssets = !(defined('H5P_DISABLE_AGGREGATION') && H5P_DISABLE_AGGREGATION === true);

            if ($this->getSetting('h5p_library_development', 0) == 1) {
                if (!is_dir($this->get_h5p_path() . '/development')) {
                    mkdir($this->get_h5p_path() . '/development');
                }
                self::$core->development_mode |= \H5PDevelopment::MODE_LIBRARY;
                self::$core->h5pD = new \H5PDevelopment(self::$interface, $this->get_h5p_path() . '/', $language);
                self::$interface->setInfoMessage('H5P library development directory is enabled. Change <a href="/administrator/index.php?option=com_h5p&view=settings">settings</a>.');
            }
        }

        switch ($type) {
            case 'validator':
                return new \H5PValidator(self::$interface, self::$core);
            case 'storage':
                return new \H5PStorage(self::$interface, self::$core);
            case 'contentvalidator':
                return new \H5PContentValidator(self::$interface, self::$core);
            case 'export':
                return new \H5PExport(self::$interface, self::$core);
            case 'interface':
                return self::$interface;
            case 'core':
                return self::$core;
        }
    }

    public function get_language()
    {
        $language = Factory::getLanguage()->getTag();

        if (!empty($language)) {
            $languageParts = explode('-', $language);
            return $languageParts[0];
        }

        return 'en';
    }

    public function add_settings()
    {
        if (self::$settings !== null) {
            $this->print_settings(self::$settings);
        }
    }

    public function print_settings(&$settings, $obj_name = 'H5PIntegration', $reqpost=0)
    {
        static $printed;
        if (!empty($printed[$obj_name])) {
            return; // Avoid re-printing settings
        }

        $json_settings = json_encode($settings);
        if ($json_settings !== false) {
            $printed[$obj_name] = true;
            if($reqpost){
                return '<script>' . $obj_name . ' = ' . $json_settings . ';</script>';
            }
            else{
                print '<script>' . $obj_name . ' = ' . $json_settings . ';</script>';
            }
        }
    }

    public function get_site_url()
    {
        $url = rtrim(Uri::root(), '/');
        return $url;
    }

    public function get_core_settings()
    {
        $appName = Factory::getApplication()->getName();
        $current_user = Factory::getApplication()->getIdentity();
        $core = self::get_h5p_instance('core');
        $h5p = self::get_h5p_instance('interface');
        $settings = array(
            'baseUrl' => $this->get_site_url(),
            'url' => $this->get_h5p_url(),
            'postUserStatistics' => ($this->getSetting('h5p_track_user') === '1') && $current_user->id,
            'ajax' => array(
                'setFinished' => Uri::root() . ( $appName == 'administrator' ? 'administrator/' : '') . 'index.php?option=com_h5p&view=contents&token=' . \H5PCore::createToken('h5p_result') . '&task=h5p_setFinished',
                'contentUserData' => Uri::root(). ( $appName == 'administrator' ? 'administrator/' : '') . 'index.php?option=com_h5p&view=newcontent&token=' . \H5PCore::createToken('h5p_contentuserdata') . '&task=h5p_contents_user_data&content_id=:contentId&data_type=:dataType&sub_content_id=:subContentId',
            ),
            'saveFreq' => $this->getSetting('h5p_save_content_state') ? $this->getSetting('h5p_save_content_frequency') : false,
            'siteUrl' => $this->get_site_url(),
            'l10n' => array(
                'H5P' => $core->getLocalization(),
            ),
            'hubIsEnabled' => $this->getSetting('h5p_hub_is_enabled') == true,
            'reportingIsEnabled' => ($this->getSetting('h5p_enable_lrs_content_types') == '1') ? true : false,
            'libraryConfig' => $h5p->getLibraryConfig(),
            'crossorigin' => defined('H5P_CROSSORIGIN') ? H5P_CROSSORIGIN : null,
            'crossoriginCacheBuster' => defined('H5P_CROSSORIGIN_CACHE_BUSTER') ? H5P_CROSSORIGIN_CACHE_BUSTER : null,
            'pluginCacheBuster' => '?v=' . self::VERSION,
            'libraryUrl' => Uri::root() . 'libraries/h5p/h5p-php-library/js',
        );

        if ($current_user->id) {
            $settings['user'] = array(
                'name' => $current_user->name,
                'mail' => $current_user->email,
            );
        }

        return $settings;
    }

    public function get_h5p_path()
    {
        $upload_dir = JPATH_ROOT . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'com_h5p' . DIRECTORY_SEPARATOR . 'h5p';
        return $upload_dir;
    }

    public static function get_h5p_url($absolute = false)
    {
        static $url;
        if (!$url) {
            $url = array();
        }

        if (empty($url)) {
            // Absolute urls are used to enqueue assets.
            $url = array('abs' => Uri::root() . 'media/com_h5p/h5p');

            // Relative URLs are used to support both http and https in iframes.
            $url['rel'] = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $url['abs']);

        }

        return $absolute ? $url['abs'] : $url['rel'];
    }

    public static function getSetting($setting_name, $default = false)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__h5p_settings');
        $db->setQuery((string) $query);
        $items = $db->loadObjectList('setting_name');
        $ret = null;
        if (isset($items[$setting_name])) {
            $ret = $items[$setting_name]->setting_value;
        } else {
            //if (empty($ret)) {
            $ret = $default;
        }

        return $ret;
    }

    public static function setSetting($setting_name, $value)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__h5p_settings');
        $db->setQuery((string) $query);
        $items = $db->loadObjectList('setting_name');
        $ret = $items[$setting_name]->setting_value;
        if (!isset($ret)) {
            $query = "INSERT INTO #__h5p_settings (setting_name, setting_value) VALUES ('" . $setting_name . "','" . $value . "')";
        } else {
            $query = "UPDATE #__h5p_settings SET setting_value = '" . $value . "' WHERE setting_name = '" . $setting_name . "'";
        }

        $db->setQuery((string) $query);
        $db->execute();
    }

    public static function handle_upload($content = null, $only_upgrade = null)
    {
        $plugin = self::get_instance();
        $interface = $plugin->get_h5p_instance('interface');
        $validator = $plugin->get_h5p_instance('validator');

        if (self::current_user_can('disable_h5p_security')) {
            $core = $plugin->get_h5p_instance('core');

            // Make it possible to disable file extension check
            $core->disableFileCheck = (Factory::getApplication()->input->get('h5p_disable_file_check') ? true : false);
        }

        rename($_FILES['h5p_file']['tmp_name'], $interface->getUploadedH5pPath());

        $skipContent = ($content === null);

        if ($validator->isValidPackage($skipContent, $only_upgrade)) {
            $tmpDir = $interface->getUploadedH5pFolderPath();

            if (!$skipContent) {
                foreach ($validator->h5pC->mainJsonData['preloadedDependencies'] as $dep) {
                    if ($dep['machineName'] === $validator->h5pC->mainJsonData['mainLibrary']) {
                        if ($validator->h5pF->libraryHasUpgrade($dep)) {
                            // We do not allow storing old content due to security concerns
                            $interface->setErrorMessage(Text::_('COM_H5P_ERROR_OLDVERSION'));
                            \H5PCore::deleteFileTree($tmpDir);
                            return false;
                        }
                    }
                }

                if (empty($content['metadata']) || empty($content['metadata']['title'])) {
                    // Fix for legacy content upload to work.
                    // Fetch title from h5p.json or use a default string if not available
                    $content['metadata']['title'] = empty($validator->h5pC->mainJsonData['title']) ? 'Uploaded Content' : $validator->h5pC->mainJsonData['title'];
                }
            }

            if (function_exists('check_upload_size')) {
                // Check file sizes before continuing!
                $error = self::check_upload_sizes($tmpDir);
                if ($error !== null) {
                    // Didn't meet space requirements, cleanup tmp dir.
                    $interface->setErrorMessage($error);
                    \H5PCore::deleteFileTree($tmpDir);
                    return false;
                }
            }

            // No file size check errors

            if (isset($content['id'])) {
                $interface->deleteLibraryUsage($content['id']);
            }
            $storage = $plugin->get_h5p_instance('storage');
            $storage->savePackage($content, null, $skipContent);

            // Clear cached value for dirsize.
            //delete_transient('dirsize_cache');

            return $storage->contentId;
        }

        // The uploaded file was not a valid H5P package
        @unlink($interface->getUploadedH5pPath());
        return false;
    }

    public function add_core_assets()
    {
        if (self::$settings !== null) {
            return; // Already added
        }

        self::$settings = $this->get_core_settings();
        self::$settings['core'] = array(
            'styles' => array(),
            'scripts' => array(),
        );
        self::$settings['loadedJs'] = array();
        self::$settings['loadedCss'] = array();
        $cache_buster = '?ver=' . self::VERSION;

        // Use relative URL to support both http and https.
        $lib_url = Uri::root() . 'libraries/h5p/h5p-php-library' . '/';
        $rel_path = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $lib_url);

        $document = Factory::getDocument();
        $document->addStyleSheet(Uri::root() . 'media/com_h5p/css/admin.css');
        // Add core stylesheets
        foreach (\H5PCore::$styles as $style) {
            self::$settings['core']['styles'][] = $rel_path . $style . $cache_buster;
            $document->addStyleSheet($lib_url . $style . $cache_buster);
        }

        // Add core JavaScript
        foreach (\H5PCore::$scripts as $script) {
            self::$settings['core']['scripts'][] = $rel_path . $script . $cache_buster;
            $document->addScript($lib_url . $script . $cache_buster);
        }
    }

    public function add_assets($content, $no_cache = false)
    {
        // Add core assets
        $this->add_core_assets();

        // Detemine embed type
        $embed = \H5PCore::determineEmbedType($content['embedType'], $content['library']['embedTypes']);

        // Make sure content isn't added twice
        $cid = 'cid-' . $content['id'];
        if (!isset(self::$settings['contents'][$cid])) {
            self::$settings['contents'][$cid] = $this->get_content_settings($content);
            $core = $this->get_h5p_instance('core');

            // Get assets for this content
            $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
            $files = $core->getDependenciesFiles($preloaded_dependencies);
            $this->alter_assets($files, $preloaded_dependencies, $embed);

            if ($embed === 'div') {
                $this->enqueue_assets($files);
            } elseif ($embed === 'iframe') {
                self::$settings['contents'][$cid]['scripts'] = $core->getAssetsUrls($files['scripts']);
                self::$settings['contents'][$cid]['styles'] = $core->getAssetsUrls($files['styles']);
            }
        }

        if ($embed === 'div') {
            $h5p_content_wrapper = '<div class="h5p-content" data-content-id="' . $content['id'] . '"></div>';
        } else {
            $title = isset($content['metadata']['a11yTitle'])
            ? $content['metadata']['a11yTitle']
            : (isset($content['metadata']['title'])
                ? $content['metadata']['title']
                : ''
            );
            $h5p_content_wrapper = '<div class="h5p-iframe-wrapper"><iframe id="h5p-iframe-' . $content['id'] . '" class="h5p-iframe" data-content-id="' . $content['id'] . '" style="height:1px" src="about:blank" frameBorder="0" scrolling="no" title="' . $title . '"></iframe></div>';
        }

        //return apply_filters('print_h5p_content', $h5p_content_wrapper, $content);
        return $h5p_content_wrapper;
    }

    public function get_content_settings($content)
    {
        $db = Factory::getDbo();
        $core = $this->get_h5p_instance('core');

        $safe_parameters = $core->filterParameters($content);

        // Getting author's user id
        $author_id = (int) (is_array($content) ? $content['user_id'] : $content->user_id);

        $metadata = $content['metadata'];
        $title = isset($metadata['a11yTitle'])
        ? $metadata['a11yTitle']
        : (isset($metadata['title'])
            ? $metadata['title']
            : ''
        );

        // Add JavaScript settings for this content
        $settings = array(
            'library' => \H5PCore::libraryToString($content['library']),
            'jsonContent' => $safe_parameters,
            'fullScreen' => $content['library']['fullscreen'],
            'exportUrl' => self::getSetting('h5p_export') ? $this->get_h5p_url() . '/exports/' . ($content['slug'] ? $content['slug'] . '-' : '') . $content['id'] . '.h5p' : '',
            'embedCode' => '<iframe src="' . Uri::root() . 'index.php?option=com_h5p&view=contents&task=h5p_embed&id=' . $content['id'] . '" width=":w" height=":h" frameborder="0" allowfullscreen="allowfullscreen" title="' . $title . '"></iframe>',
            'resizeCode' => '<script src="' . Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-resizer.js' . '" charset="UTF-8"></script>',
            'url' => Uri::root() . 'index.php?option=com_h5p&view=contents&task=h5p_embed&id=' . $content['id'],
            'title' => $content['title'],
            'displayOptions' => $core->getDisplayOptionsForView($content['disable'], $author_id),
            'metadata' => $metadata,
            'contentUserData' => array(
                0 => array(
                    'state' => '{}',
                ),
            ),
        );

        // Get preloaded user data for the current user
        $current_user = Factory::getApplication()->getIdentity();
        if (self::getSetting('h5p_save_content_state', false) && $current_user->id) {
            $db->setQuery(sprintf(
                "SELECT hcud.sub_content_id,
					hcud.data_id,
					hcud.data
			  FROM #__h5p_contents_user_data hcud
			  WHERE user_id = %d
			  AND content_id = %d
			  AND preload = 1",
                $current_user->id,
                $content['id']
            ));

            $results = $db->loadObjectList();
            if ($results) {
                foreach ($results as $result) {
                    $settings['contentUserData'][$result->sub_content_id][$result->data_id] = $result->data;
                }
            }
        }

        return $settings;
    }

    public function get_content($id)
    {
        if ($id === false || $id === null) {
            return Text::_('COM_H5P_ERROR_MISSINGH5P');
        }

        // Try to find content with $id.
        $core = $this->get_h5p_instance('core');
        $content = $core->loadContent($id);

        if (!$content) {
            return sprintf(Text::_('COM_H5P_ERROR_CANNOTCONTENTID'), $id);
        }

        $content['language'] = $this->get_language();
        return $content;
    }

    public static function current_user_can($access)
    {
        $current_user = Factory::getApplication()->getIdentity();
        if('h5p' . $access == 'h5p.disable_h5p_security' && $current_user->authorise('core.admin')){
            return true;
        }
        return $current_user->authorise('h5p.' . $access, 'com_h5p');
    }

    public static function current_user_can_edit($content)
    {
        // If you can't edit content, you neither can edit others contents
        if (!self::current_user_can('edit_h5p_contents')) {
            return false;
        }
        if (self::current_user_can('edit_others_h5p_contents')) {
            return true;
        }
        $author_id = (int) (is_array($content) ? $content['user_id'] : $content->user_id);
        return Factory::getApplication()->getIdentity()->id == $author_id;
    }

    public static function current_user_can_view_content_results($content)
    {
        if (!self::getSetting('h5p_track_user')) {
            return false;
        }

        return self::current_user_can_edit($content);
    }

    public static function current_user_can_view($content)
    {
        // If you can't view content, you neither can view others contents
        if (!self::current_user_can('view_h5p_contents')) {
            return false;
        }

        // If user is allowed to view others' contents, can also see content in general
        if (self::current_user_can('view_others_h5p_contents')) {
            return true;
        }

        // Does content belong to current user?
        $author_id = (int) (is_array($content) ? $content['user_id'] : $content->user_id);
        return Factory::getApplication()->getIdentity()->id == $author_id;
    }

    public function print_data_view_settings($name, $source, $headers, $filters, $empty, $order)
    {
        // Add JS settings
        $data_views = array();
        $data_views[$name] = array(
            'source' => $source,
            'headers' => $headers,
            'filters' => $filters,
            'order' => $order,
            'l10n' => array(
                'loading' => Text::_('COM_H5P_LOADINGDATA'),
                'ajaxFailed' => Text::_('COM_H5P_FAILDLOADDATA'),
                'noData' => Text::_("COM_H5P_NODATA"),
                'currentPage' => Text::_('COM_H5P_CURRENTPAGE'),
                'nextPage' => Text::_('COM_H5P_NEXTPAGE'),
                'previousPage' => Text::_('COM_H5P_PREVIOUSPAGE'),
                'search' => Text::_('COM_H5P_SEARCH'),
                'remove' => Text::_('COM_H5P_REMOVE'),
                'empty' => $empty,
                'showOwnContentOnly' => Text::_('COM_H5P_SHOWOWNCONTENTONLY'),
            ),
        );

        $plugin = H5PJoomlaHelper::get_instance();
        $settings = array('dataViews' => $data_views);

        // Add toggler for hiding others' content only if user can view others content types
        $canToggleViewOthersH5PContents = $plugin->current_user_can('view_others_h5p_contents') ?
        $plugin->getSetting('h5p_show_toggle_view_others_h5p_contents') :
        0;

        // Add user object to H5PIntegration
        $user = Factory::getApplication()->getIdentity();
        if ($user->id !== 0) {
            $user = array(
                'user' => array(
                    'id' => $user->id,
                    'name' => $user->name,
                    'canToggleViewOthersH5PContents' => $canToggleViewOthersH5PContents,
                ),
            );
            $settings = array_merge($settings, $user);
        }

        $document = Factory::getDocument();
        $document->addStyleSheet(Uri::root() . 'media/com_h5p/css/admin.css');

        $plugin->print_settings($settings);

        // Add JS
        $document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/jquery.js');
        $document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-event-dispatcher.js');
        $document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-utils.js');
        $document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-data-view.js');
        $document->addScript(Uri::root() . 'media/com_h5p/js/h5p-data-views.js');
        $document->addStyleSheet(Uri::root() . 'libraries/h5p/h5p-php-library/styles/h5p-admin.css');
    }

    public function get_data_view_input()
    {
        $offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
        $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
        $sortBy = filter_input(INPUT_GET, 'sortBy', FILTER_SANITIZE_NUMBER_INT);
        $sortDir = filter_input(INPUT_GET, 'sortDir', FILTER_SANITIZE_NUMBER_INT);
        $filters = filter_input(INPUT_GET, 'filters', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $facets = filter_input(INPUT_GET, 'facets', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        $limit = (!$limit ? 20 : (int) $limit);
        if ($limit > 100) {
            $limit = 100; // Prevent wrong usage.
        }

        // Use default if not set or invalid
        return array(
            (!$offset ? 0 : (int) $offset),
            $limit,
            (!$sortBy ? 0 : (int) $sortBy),
            (!$sortDir ? 0 : (int) $sortDir),
            $filters,
            $facets,
        );
    }

    public function print_results($content_id = null, $user_id = null)
    {
        // Load input vars.
        list($offset, $limit, $sortBy, $sortDir, $filters) = $this->get_data_view_input();

        // Get results
        $results = $this->get_results($content_id, $user_id, $offset, $limit, $sortBy, $sortDir, $filters);

//        $datetimeformat = "Y-m-d H:i:s"; // get datetime format
        $timezone = Factory::getUser($user_id)->getTimezone();
        
        $offset = 3 * 3600;

        // Make data more readable for humans
        $rows = array();
        foreach ($results as $result) {
            if ($result->time === '0') {
                $result->time = $result->finished - $result->opened;
            }
            $seconds = ($result->time % 60);
            $time = floor($result->time / 60) . ':' . ($seconds < 10 ? '0' : '') . $seconds;

            $rows[] = array(
                htmlspecialchars(Text::_($content_id === null ? $result->content_title : $result->user_name), ENT_HTML5),
                (int) $result->score,
                (int) $result->max_score,
                Factory::getDate($result->opened)->setTimezone( $timezone)->format(Text::_('DATE_FORMAT_FILTER_DATETIME'),true),
                Factory::getDate($result->finished)->setTimezone( $timezone)->format(Text::_('DATE_FORMAT_FILTER_DATETIME'),true),
                $time,
            );
        }

        // Print results
        header('Cache-Control: no-cache');
        header('Content-type: application/json');
        print json_encode(array(
            'num' => $this->get_results_num($content_id, $user_id, $filters),
            'rows' => $rows,
        ));
        exit;
    }

    public function get_order_by($field, $direction, $fields)
    {
        // Make sure selected sortable field is valid
        if (!isset($fields[$field])) {
            $field = 0; // Fall back to default
        }

        // Find selected sortable field
        $field = $fields[$field];

        if (is_object($field)) {
            // Some fields are reverse sorted by default, e.g. text fields.
            if (!empty($field->reverse)) {
                $direction = !$direction;
            }

            $field = $field->name;
        }

        return 'ORDER BY ' . $field . ' ' . ($direction ? 'ASC' : 'DESC');
    }

    public function get_results($content_id = null, $user_id = null, $offset = 0, $limit = 20, $sort_by = 0, $sort_dir = 0, $filters = array())
    {
        $db = Factory::getDbo();

        $extra_fields = '';
        $joins = '';
        $query_args = array();

        $append_user_name = false;

        // Add extra fields and joins for the different result lists
        if ($content_id === null) {
            $extra_fields .= " hr.content_id, hc.title AS content_title,";
            $joins .= " LEFT JOIN #__h5p_contents hc ON hr.content_id = hc.id";
        }
        if ($user_id === null) {
            $extra_fields .= " hr.user_id,";
            $append_user_name = true;
        }

        // Add filters
        $where = $this->get_results_query_where($query_args, $content_id, $user_id, $filters);

        // Order results by the select column and direction
        $order_by = $this->get_order_by($sort_by, $sort_dir, array(
            (object) array(
                'name' => ($content_id === null ? 'hc.title' : 'u.user_login'),
                'reverse' => true,
            ),
            'hr.score',
            'hr.max_score',
            'hr.opened',
            'hr.finished',
        ));

        $query_args[] = $offset;
        $query_args[] = $limit;

        $db->setQuery(call_user_func_array(
            'sprintf',
            array_merge(
                array("SELECT hr.id,
							{$extra_fields}
							hr.score,
							hr.max_score,
							hr.opened,
							hr.finished,
							hr.time
							FROM #__h5p_results hr
							{$joins}
							{$where}
							{$order_by}
							LIMIT %d, %d"),
                $query_args
            )
        ));

        $results = $db->loadObjectList();

        if ($append_user_name && $results) {
            $results = $this->append_user_name($results);
        }

        return $results;
    }

    protected function append_user_name($results)
    {
        // Collect all user IDs to process in a single query.
        $user_ids = [];
        foreach ($results as $result) {
            if (!isset($result->user_id)) {
                continue;
            }

            $user_ids[] = $result->user_id;
        }

        $db = Factory::getDbo();

        $db->setQuery("SELECT id, name FROM #__users WHERE id IN (" . implode(',', array_unique($user_ids)) . ")");

        $wp_users = $db->loadAssocList('id');

        // If no users are found, there's nothing to do.
        if (!$wp_users) {
            return $results;
        }

        // We can fetch items from the now primed cache.
        foreach ($results as &$result) {
            if (!isset($result->user_id)) {
                continue;
            }

            $result->user_name = $wp_users[$result->user_id]['name'];
        }

        return $results;
    }

    public function get_results_num($content_id = null, $user_id = null, $filters = array())
    {
        $db = Factory::getDbo();
        $query_args = array();
        $add_cond = $this->get_results_query_where($query_args, $content_id, $user_id);
        if (!$query_args) {
            $db->setQuery(
                call_user_func_array(
                    'sprintf',
                    [
                        "SELECT COUNT(id) FROM #__h5p_results hr" . $add_cond,
                        $query_args,
                    ]
                )
            );
        } else {
            $db->setQuery("SELECT COUNT(id) FROM #__h5p_results hr");
        }

        return (int) $db->loadResult();
    }

    private function get_results_query_where(&$query_args, $content_id = null, $user_id = null, $filters = array())
    {
        if ($content_id !== null) {
            $where = ' WHERE hr.content_id = %d';
            $query_args[] = $content_id;
        }
        if ($user_id !== null) {
            $where = (isset($where) ? $where . ' AND' : ' WHERE') . ' hr.user_id = %d';
            $query_args[] = $user_id;
        }
        if (isset($where) && isset($filters[0])) {
            $where .= ' AND ' . ($content_id === null ? 'hc.title' : 'u.user_login') . " LIKE '%%%s%%'";
            $query_args[] = $filters[0];
        }
        return (isset($where) ? $where : '');
    }

    public function shortcode($id = null)
    {

        $id = isset($id) ? intval($id) : null;
        $content = $this->get_content($id);
        if (is_string($content)) {
            // Return error message if the user has the correct cap
            return self::current_user_can('edit_h5p_contents') ? $content : null;
        }

        // Log view
        new H5PEventHelper(
            'content',
            'shortcode',
            $content['id'],
            $content['title'],
            $content['library']['name'],
            $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']
        );

        return $this->add_assets($content);
    }

    public function enqueue_assets(&$assets)
    {
        $document = Factory::getDocument();
        $rel_url = $this->get_h5p_url();
        $abs_url = $this->get_h5p_url(true);
        $cache_buster = '?ver=' . self::VERSION;

        // Enqueue JavaScripts
        foreach ($assets['scripts'] as $script) {
            if (preg_match('/^https?:\/\//i', $script->path)) {
                // Absolute path
                $url = $script->path;
                $enq = $script->path;
            } else {
                // Relative path
                $url = $rel_url . $script->path;
                $enq = $abs_url . $script->path;
            }

            // Make sure each file is only loaded once
            if (!in_array($url, self::$settings['loadedJs'])) {
                self::$settings['loadedJs'][] = $url;

                $document->addScript($enq . $cache_buster);
            }
        }

        // Enqueue stylesheets
        foreach ($assets['styles'] as $style) {
            if (preg_match('/^https?:\/\//i', $style->path)) {
                // Absolute path
                $url = $style->path;
                $enq = $style->path;
            } else {
                // Relative path
                $url = $rel_url . $style->path;
                $enq = $abs_url . $style->path;
            }

            // Make sure each file is only loaded once
            if (!in_array($url, self::$settings['loadedCss'])) {
                self::$settings['loadedCss'][] = $url;
                $document->addStyleSheet($enq);
            }
        }
    }

    public function ajax_results()
    {

        $content_id = filter_input(INPUT_POST, 'contentId', FILTER_VALIDATE_INT);
        if (!$content_id) {
            \H5PCore::ajaxError(Text::_('COM_H5P_CONTENTS_INVALIDCONTENT'));
            exit;
        }
        if (!\H5PCore::validToken('h5p_result', filter_input(INPUT_GET, 'token'))) {
            \H5PCore::ajaxError(Text::_('COM_H5P_INVALIDTOKEN'));
            exit;
        }

        $user_id = Factory::getApplication()->getIdentity()->id;
        $db = Factory::getDbo();
        $db->setQuery(sprintf(
            "SELECT id
			FROM #__h5p_results
			WHERE user_id = %d
			AND content_id = %d",
            $user_id,
            $content_id
        ));
        $result_id = $db->loadResult();

        $table = '#__h5p_results';
        $data = new \stdClass();
        $data->score = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT);
        $data->max_score = filter_input(INPUT_POST, 'maxScore', FILTER_VALIDATE_INT);
        $data->opened = filter_input(INPUT_POST, 'opened', FILTER_VALIDATE_INT);
        $data->finished = filter_input(INPUT_POST, 'finished', FILTER_VALIDATE_INT);
        $data->time = filter_input(INPUT_POST, 'time', FILTER_VALIDATE_INT);
        if ($data->time === null) {
            $data->time = 0;
        }

        $app = Factory::getApplication();
        $app->triggerEvent('onh5p_alter_user_result', array(&$data, $result_id, $content_id, $user_id));
        if (!$result_id) {
            // Insert new results
            $data->user_id = $user_id;
            $data->content_id = $content_id;
            $db->insertObject($table, $data);
        } else {
            // Update existing results
            $data->id = $result_id;

            $db->updateObject($table, $data, 'id', true);
        }

        // Get content info for log
        $db->setQuery(sprintf("
			SELECT c.title, l.name, l.major_version, l.minor_version
			  FROM #__h5p_contents c
			  JOIN #__h5p_libraries l ON l.id = c.library_id
			 WHERE c.id = %d
			", $content_id));
        $content = $db->loadObjectList($content_id);
        // Log view
        new H5PEventHelper(
            'results',
            'set',
            $content_id,
            $content->title,
            $content->name,
            $content->major_version . '.' . $content->minor_version
        );

        // Success
        \H5PCore::ajaxSuccess();
        exit;
    }

    public function ajax_contents_user_data() {

		$content_id = filter_input(INPUT_GET, 'content_id');
		$data_id = filter_input(INPUT_GET, 'data_type');
		$sub_content_id = filter_input(INPUT_GET, 'sub_content_id');
		$current_user = Factory::getApplication()->getIdentity();

		$plugin = self::get_instance();
		
		if ($content_id === NULL ||
			$data_id === NULL ||
			$sub_content_id === NULL ||
			!$current_user->id) {
		  return; // Missing parameters
		}

		$response = (object) array(
		  'success' => TRUE
		);

		$data = filter_input(INPUT_POST, 'data');
		$preload = filter_input(INPUT_POST, 'preload');
		$invalidate = filter_input(INPUT_POST, 'invalidate');
		
		$db = Factory::getDbo();
		
		if ($data !== NULL && $preload !== NULL && $invalidate !== NULL) {
			if (!\H5PCore::validToken('h5p_contentuserdata', filter_input(INPUT_GET, 'token'))) {
				\H5PCore::ajaxError(Text::_('COM_H5P_INVALIDTOKEN'));
				return;
			}
			if ($data === '0') {
				// Remove data
				$query = $db->getQuery(true);
				$conditions = array(
					$db->quoteName('content_id') . " = " . $content_id,
					$db->quoteName('data_id') . " = " . $data_id,
					$db->quoteName('user_id') . " = " . $current_user->id,
					$db->quoteName('sub_content_id') . " = " . $sub_content_id
				);
				
				$query->delete($db->quoteName('#__h5p_contents_user_data'));
				$query->where($conditions);
				$db->setQuery($query);
				$db->execute();
			}
			else {
				// Wash values to ensure 0 or 1.
				$preload = ($preload === '0' ? 0 : 1);
				$invalidate = ($invalidate === '0' ? 0 : 1);
			
				// Determine if we should update or insert
				$db->setQuery (sprintf(
					"SELECT content_id
					FROM #__h5p_contents_user_data
					WHERE content_id = %d
					AND user_id = %d
					AND data_id = %s
					AND sub_content_id = %d",
					$content_id, $current_user->id, $db->quote($data_id), $sub_content_id));
				
				$update = $db->loadObjectList(); //[0];	

				$db_object = new \stdClass;
				$db_object->user_id = $current_user->id;
				$db_object->content_id = $content_id;
				$db_object->sub_content_id = $sub_content_id;
				$db_object->data_id = $data_id;
				$db_object->data = $data;
				$db_object->preload = $preload;
				$db_object->invalidate = $invalidate;
				$db_object->updated_at = (new Date('now'))->toSQL();

				if ($update == NULL) {
					// Insert new data
					$db->insertObject('#__h5p_contents_user_data', $db_object);
				}
				else {
					$db->updateObject('#__h5p_contents_user_data', $db_object, ['user_id', 'content_id', 'data_id', 'sub_content_id']);
				}
			}

			// Inserted, updated or deleted
			\H5PCore::ajaxSuccess();
			exit;
		}
		else {
			// Fetch data
			$db->setQuery(sprintf(
				"SELECT hcud.data
			 FROM #__h5p_contents_user_data hcud
			 WHERE user_id = %d
			   AND content_id = %d
			   AND data_id = %s
			   AND sub_content_id = %d",
				$current_user->id, $content_id, $db->quote($data_id), $sub_content_id ));
			
			$response->data = $db->loadResult();
			if ($response->data === NULL) {
				unset($response->data);
			}
		}

		header('Cache-Control: no-cache');
		header('Content-type: application/json; charset=utf-8');
		print json_encode($response);
		exit;
	}
}
