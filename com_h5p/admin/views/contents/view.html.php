<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

//namespace VB\Component\H5P\Administrator\View\Contents;

defined('_JEXEC') or die;

//use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use VB\Component\H5P\Administrator\Helper\H5PEventHelper;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_h5p
 *
 * @copyright   Copyright (C) 2021 John Smith. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

class H5pViewContents extends \Joomla\CMS\MVC\View\HtmlView
{

    public $content = null, $embed_code = '';

    /**
     * Отображение основного вида "Hello World"
     *
     * @param   string  $tpl  Имя файла шаблона для анализа; автоматический поиск путей к шаблону.
     * @return  void
     */
    public function display($tpl = null)
    {
        $plugin = H5PJoomlaHelper::get_instance();

        $this->setModel(JModelLegacy::getInstance('Newcontent', 'h5pModel'));

        $task = filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING);
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        switch ($task) {
            case null:
                $headers = array(
                    (object) array(
                        'text' => Text::_('COM_H5P_CONTENTS_TITLE'),
                        'sortable' => true,
                    ),
                    (object) array(
                        'text' => Text::_('COM_H5P_CONTENTS_CONTENTTYPE'),
                        'sortable' => true,
                        'facet' => true,
                    ),
                    (object) array(
                        'text' => Text::_('COM_H5P_CONTENTS_AUTHOR'),
                        'sortable' => true,
                        'facet' => true,
                    ),
                    (object) array(
                        'text' => Text::_('COM_H5P_CONTENTS_TAGS'),
                        'sortable' => false,
                        'facet' => true,
                    ),
                    (object) array(
                        'text' => Text::_('COM_H5P_CONTENTS_LASTMODIFIED'),
                        'sortable' => true,
                    ),
                    (object) array(
                        'text' => Text::_('ID'),
                        'sortable' => true,
                    ),
                );
                if ($plugin->getSetting('h5p_track_user')) {
                    $headers[] = (object) array(
                        'class' => 'h5p-results-link',
                    );
                }
                $headers[] = (object) array(
                    'class' => 'h5p-edit-link',
                );

                $plugin->print_data_view_settings(
                    'h5p-contents',
                    Uri::root() . 'administrator/index.php?option=com_h5p&view=contents&task=h5p_contents',
                    $headers,
                    array(true),
                    Text::_("COM_H5P_CONTENTS_NOH5PCONTENTS"),
                    (object) array(
                        'by' => 4,
                        'dir' => 0,
                    )
                );
                H5pHelper::addSubMenu('contents');
                parent::display($tpl);

                return;

            case 'h5p_insert_content':
                $this->ajax_contents(TRUE);
                return;
            case 'h5p_inserted':
                $this->ajax_inserted();
                return;
            case 'h5p_contents':
                $this->ajax_contents();
                return;
            case 'h5p_setFinished':
                $plugin->ajax_results();
                exit;
            case 'h5p_content_results':
                $this->ajax_content_results();
                return;
            case 'h5p_embed':
            case 'nopriv_h5p_embed':
                $this->embed();
                return;

            case 'show':
                if ($id) {
                    $this->content = $this->getModel('Newcontent')->load_content($id);
                    if (is_string($this->content)) {
                        Factory::getApplication()->enqueueMessage($this->content, 'error');
                        $this->content = null;
                    }
                }

                // Access restriction
                if ($plugin->current_user_can_view($this->content) == false) {
                    Factory::getApplication()->enqueueMessage('You are not allowed to view this content.', 'error');
                    return;
                }

                // Admin preview of H5P content.
                if (is_string($this->content)) {
                    Factory::getApplication()->enqueueMessage(($this->content), 'error');
                } else {
                    $this->embed_code = $plugin->add_assets($this->content);
                    H5pHelper::addSubMenu('contents');
                    parent::display($tpl);
                    $plugin->add_settings();

                    // Log view
                    new H5PEventHelper(
                        'content',
                        null,
                        $this->content['id'],
                        $this->content['title'],
                        $this->content['library']['name'],
                        $this->content['library']['majorVersion'] . '.' . $this->content['library']['minorVersion']
                    );
                }
                return;

            case 'results':
                if ($id) {
                    $this->content = $this->getModel('Newcontent')->load_content($id);
                    if (is_string($this->content)) {
                        Factory::getApplication()->enqueueMessage($this->content, 'error');
                        $this->content = null;
                    }
                }
                // View content results
                if (is_string($this->content)) {
                    Factory::getApplication()->enqueueMessage($this->content, 'error');
                } else {
                    // Print HTML
                    H5pHelper::addSubMenu('contents');
                    parent::display($tpl);
                    $plugin->print_data_view_settings(
                        'h5p-content-results',
                        Uri::root() . 'administrator/index.php?option=com_h5p&view=contents&task=h5p_content_results&id=' . $this->content['id'],
                        array(
                            (object) array(
                                'text' => Text::_('COM_H5P_CONTENTS_USER'),
                                'sortable' => true,
                            ),
                            (object) array(
                                'text' => Text::_('COM_H5P_CONTENTS_SCORE'),
                                'sortable' => true,
                            ),
                            (object) array(
                                'text' => Text::_('COM_H5P_CONTENTS_MAXSCORE'),
                                'sortable' => true,
                            ),
                            (object) array(
                                'text' => Text::_('COM_H5P_CONTENTS_OPENED'),
                                'sortable' => true,
                            ),
                            (object) array(
                                'text' => Text::_('COM_H5P_CONTENTS_FINISHED'),
                                'sortable' => true,
                            ),
                            Text::_('COM_H5P_CONTENTS_TIMESPENT'),
                        ),
                        array(true),
                        Text::_("COM_H5P_CONTENTS_NOTLOG"),
                        (object) array(
                            'by' => 4,
                            'dir' => 0,
                        )
                    );

                    // Log content result view
                    new H5PEventHelper(
                        'results',
                        'content',
                        $this->content['id'],
                        $this->content['title'],
                        $this->content['library']['name'],
                        $this->content['library']['majorVersion'] . '.' . $this->content['library']['minorVersion']
                    );
                }
                return;
        }
    }

    public function ajax_inserted()
    {
        $db = Factory::getDbo();

        $content_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$content_id) {
            exit;
        }

        // Get content info for log
        $content = $db->setQuery( sprintf("
            SELECT c.title, l.name, l.major_version, l.minor_version
              FROM #__h5p_contents c
              JOIN #__h5p_libraries l ON l.id = c.library_id
             WHERE c.id = %d
            ", $content_id))->loadObjectList()[0];

        // Log view
        new H5PEventHelper(
            'content',
            'shortcode insert',
            $content_id,
            $content->title,
            $content->name,
            $content->major_version . '.' . $content->minor_version
        );
    }

    public function ajax_content_results()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$id) {
            exit; // Missing id
        }

        $plugin = H5PJoomlaHelper::get_instance();
        $content = $plugin->get_content($id);
        if (is_string($content) || !$plugin->current_user_can_edit($content)) {
            exit; // Error loading content or no access
        }

        $plugin->print_results($id);
    }

    public function ajax_contents($insert = false)
    {
        $db = Factory::getDbo();

        // Load input vars.
        $plugin = H5PJoomlaHelper::get_instance();
        list($offset, $limit, $sort_by, $sort_dir, $filters, $facets) = $plugin->get_data_view_input();

        // Different fields for insert
        if ($insert) {
            $fields = array('title', 'content_type', 'user_name', 'tags', 'updated_at', 'id', 'user_id', 'content_type_id', 'slug');
        } else {
            $fields = array('title', 'content_type', 'user_name', 'tags', 'updated_at', 'id', 'user_id', 'content_type_id');
        }

        // Add filters to data query
        $conditions = array();
        if (isset($filters[0])) {
            $conditions[] = array('title', $filters[0], 'LIKE');
        }

        // Limit query to content types that user is allowed to view
        if ($plugin->current_user_can('view_others_h5p_contents') == false) {
            array_push($conditions, array('user_id', Factory::getApplication()->getIdentity()->id, '='));
        }

        if ($facets !== null) {
            $facetmap = array(
                'content_type' => 'content_type_id',
                'user_name' => 'user_id',
                'tags' => 'tags',
            );
            foreach ($facets as $field => $value) {
                if (isset($facetmap[$fields[$field]])) {
                    $conditions[] = array($facetmap[$fields[$field]], $value, '=');
                }
            }
        }

        // Create new content query
        $content_query = new \H5PContentQuery($fields, $offset, $limit, $fields[$sort_by], $sort_dir, $conditions);
        $results = $content_query->get_rows();

        // Make data more readable for humans
        $rows = array();
        foreach ($results as $result) {
            $rows[] = ($insert ? $this->get_contents_insert_row($result) : $this->get_contents_row($result));
        }

        // Print results
        header('Cache-Control: no-cache');
        header('Content-type: application/json');
        print json_encode(array(
            'num' => $content_query->get_total(),
            'rows' => $rows,
        ));
        exit;
    }

    private function get_contents_insert_row($result)
    {
        return array(
            Text::_($result->title),
            array(
                'id' => $result->content_type_id,
                'title' => Text::_($result->content_type),
            ),
            array(
                'id' => $result->user_id,
                'title' => Text::_($result->user_name),
            ),
            $this->format_tags($result->tags),
            $this->format_time($result->updated_at),
            '<button class="button h5p-insert" data-id="' . $result->id . '" data-slug="' . $result->slug . '">' . Text::_('COM_H5P_INSERT') . '</button>',
        );
    }

    private function get_contents_row($result)
    {
        $plugin = H5PJoomlaHelper::get_instance();
        $row = array(
            '<a href="' . Uri::root() . 'administrator/index.php?option=com_h5p&view=contents&task=show&layout=show-content&id=' . $result->id . '">' . Text::_($result->title) . '</a>',
            array(
                'id' => $result->content_type_id,
                'title' => Text::_($result->content_type),
            ),
            array(
                'id' => $result->user_id,
                'title' => Text::_($result->user_name),
            ),
            $this->format_tags($result->tags),
            $this->format_time($result->updated_at),
            $result->id,
        );

        $content = array('user_id' => $result->user_id);

        // Add user results link
        if ($plugin->getSetting('h5p_track_user')) {
            if ($plugin->current_user_can_view_content_results($content)) {
                $row[] = '<a href="' . Uri::root() . 'administrator/index.php?option=com_h5p&view=contents&task=results&layout=content-results&id=' . $result->id . '">' . Text::_('COM_H5P_RESULTS') . '</a>';
            } else {
                $row[] = '';
            }
        }

        // Add edit link
        if ($plugin->current_user_can_edit($content)) {
            $row[] = '<a href="' . Uri::root() . 'administrator/index.php?option=com_h5p&view=newcontent&id=' . $result->id . '">' . Text::_('COM_H5P_EDIT') . '</a>';
        } else {
            $row[] = '';
        }

        return $row;
    }

    private function format_tags($tags)
    {
        // Tags come in CSV format, create Array instead
        $result = array();
        $csvtags = explode(';', $tags);
        foreach ($csvtags as $csvtag) {
            if ($csvtag !== '') {
                $tag = explode(',', $csvtag);
                $result[] = array(
                    'id' => $tag[0],
                    'title' => Text::_($tag[1]),
                );
            }
        }
        return $result;
    }

    private function format_time($timestamp)
    {
        // Get timezone offset
        $timezone = Factory::getApplication()->getIdentity()->getTimezone();

        // Format time
        $timed = (new Date($timestamp))->setTimezone($timezone);
        $current_time = new Date('now');
        $timediff = date_diff($timed, $current_time);
        $human_time = sprintf(Text::_('%s ago'), $timediff->h != 0 ? $timediff->format(Text::_('%h hours')) :
                                                 ($timediff->i != 0 ? $timediff->format(Text::_('%i minutes')) : $timediff->format(Text::_('%s seconds')))
                                                );

        if ($current_time > (new Date($timestamp))->modify('+1 day') ) {
            // Over a day old, swap human time for formatted time
            $formatted_time = $human_time;
            $human_time = $timed->format(Text::_('DATE_FORMAT_FILTER_DATETIME'),true);
        } else {
            $formatted_time = $timed->format(Text::_('DATE_FORMAT_FILTER_DATETIME'),true);
        }

        $iso_time = $timed->toISO8601();
        return "<time datetime=\"{$iso_time}\" title=\"{$formatted_time}\">{$human_time}</time>";
    }

    /**
     * Print page for embed iframe
     *
     * @since 1.3.0
     */
    public function embed()
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
