<?php

/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */


//namespace VB\Component\H5P\Administrator\View\Settings;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use VB\Component\H5P\Administrator\Helper\H5PEventHelper;
use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;

class H5pViewSettings extends \Joomla\CMS\MVC\View\HtmlView
{
	public $frame,
		$download,
		$embed,
		$copyright,
		$about,
		$track_user,
		$save_content_state,
		$save_content_frequency,
		$show_toggle_view_others_h5p_contents,
		$insert_method,
		$enable_lrs_content_types,
		$enable_hub,
		$send_usage_statistics,
		$dev_mode,
		$library_development,
		$save;

	public function display($tpl = null)
	{

		$task = filter_input(INPUT_GET, 'task', FILTER_SANITIZE_SPECIAL_CHARS);
		$document = Factory::getDocument();

		$plugin = H5PJoomlaHelper::get_instance();
		$core = $plugin->get_h5p_instance('core');
		switch ($task) {
			case 'content_hub_registration':
				if (!\H5PCore::validToken('content_hub_registration', filter_input(INPUT_POST, '_token'))) {
					\H5PCore::ajaxError(Text::_('COM_H5P_INVALIDTOKEN'));
					return;
				}

				$logo = isset($_FILES['logo']) ? $_FILES['logo'] : null;

				$formData = [
					'name' => filter_input(INPUT_POST, 'name'),
					'email' => filter_input(INPUT_POST, 'email'),
					'description' => filter_input(INPUT_POST, 'description'),
					'contact_person' => filter_input(INPUT_POST, 'contact_person'),
					'phone' => filter_input(INPUT_POST, 'phone'),
					'address' => filter_input(INPUT_POST, 'address'),
					'city' => filter_input(INPUT_POST, 'city'),
					'zip' => filter_input(INPUT_POST, 'zip'),
					'country' => filter_input(INPUT_POST, 'country'),
					'remove_logo' => filter_input(INPUT_POST, 'remove_logo'),
				];

				$result = $core->hubRegisterAccount($formData, $logo);

				if ($result['success'] == false) {
					$core->h5pF->setErrorMessage($result['message']);
					\H5PCore::ajaxError($result['message'], $result['error_code'], $result['status_code']);
					return;
				}

				//$core->h5pF->setInfoMessage($result['message']);
				\H5PCore::ajaxSuccess($result['message']);

				exit;
			case 'h5p_hub_registration':
				try {
					$accountInfo = $core->hubAccountInfo();
				} catch (Exception $e) {
					// Go back to H5P configuration, secret has to be removed manually
					Factory::getApplication()->redirect(Route::_('/administrator/index.php?option=com_h5p&view=settings'));
					return;
				}

				$settings = array(
					'registrationURL' => Uri::root() . 'administrator/index.php?option=com_h5p&view=settings&task=content_hub_registration&layout=registration',
					'accountSettingsUrl' => '',
					'token' => H5PCore::createToken('content_hub_registration'),
					'l10n' => $core->getLocalization(),
					'licenseAgreementTitle' => 'End User License Agreement (EULA)',
					'licenseAgreementDescription' => 'Please read the following agreement before proceeding with the ',
					'licenseAgreementMainText' => 'TODO',
					'accountInfo' => $accountInfo,
				);

				H5pHelper::addSubMenu('settings');
				parent::display($tpl);

				$plugin->print_settings($settings, 'H5PContentHubRegistration');
				$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/jquery.js');
				$document->addStyleSheet(Uri::root() . 'libraries/h5p/h5p-php-library/styles/h5p.css');
				$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-hub-registration.js');
				$document->addStyleSheet(Uri::root() . 'libraries/h5p/h5p-php-library/styles/h5p-hub-registration.css');
				$document->addScript(Uri::root() . 'media/com_h5p/js/h5p-hub-registration.js');

				return;
		}

		$this->save = filter_input(INPUT_POST, 'save_these_settings');
		if ($this->save !== null) {
			// Action bar
			$this->frame = filter_input(INPUT_POST, 'frame', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_frame', $this->frame);

			$this->download = filter_input(INPUT_POST, 'download', FILTER_VALIDATE_INT);
			$plugin->setSetting('h5p_export', $this->download);

			$this->embed = filter_input(INPUT_POST, 'embed', FILTER_VALIDATE_INT);
			$plugin->setSetting('h5p_embed', $this->embed);

			$this->copyright = filter_input(INPUT_POST, 'copyright', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_copyright', $this->copyright);

			$this->about = filter_input(INPUT_POST, 'about', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_icon', $this->about);

			$this->track_user = filter_input(INPUT_POST, 'track_user', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_track_user', $this->track_user);

			$this->save_content_state = filter_input(INPUT_POST, 'save_content_state', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_save_content_state', $this->save_content_state);

			$this->save_content_frequency = filter_input(INPUT_POST, 'save_content_frequency', FILTER_VALIDATE_INT);
			$plugin->setSetting('h5p_save_content_frequency', $this->save_content_frequency);

			$this->show_toggle_view_others_h5p_contents = filter_input(INPUT_POST, 'show_toggle_view_others_h5p_contents', FILTER_VALIDATE_INT);
			$plugin->setSetting('h5p_show_toggle_view_others_h5p_contents', $this->show_toggle_view_others_h5p_contents);

			$this->insert_method = filter_input(INPUT_POST, 'insert_method', FILTER_SANITIZE_SPECIAL_CHARS);
			$plugin->setSetting('h5p_insert_method', $this->insert_method);

			$this->enable_lrs_content_types = filter_input(INPUT_POST, 'enable_lrs_content_types', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_enable_lrs_content_types', $this->enable_lrs_content_types);

			$this->enable_hub = filter_input(INPUT_POST, 'enable_hub', FILTER_VALIDATE_BOOLEAN);
			$is_hub_enabled = $plugin->getSetting('h5p_hub_is_enabled', false) ? true : null;

			if ($this->enable_hub != $is_hub_enabled) {
				// Changed, update core
				$core->fetchLibrariesMetadata($this->enable_hub == null);
			}
			$plugin->setSetting('h5p_hub_is_enabled', $this->enable_hub);

			$this->send_usage_statistics = filter_input(INPUT_POST, 'send_usage_statistics', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_send_usage_statistics', $this->send_usage_statistics);

			$this->library_development = filter_input(INPUT_POST, 'library_development', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_library_development', $this->library_development);

			$this->dev_mode = filter_input(INPUT_POST, 'dev_mode', FILTER_VALIDATE_BOOLEAN);
			$plugin->setSetting('h5p_dev_mode', $this->dev_mode);
		} else {
			$this->frame = $plugin->getSetting('h5p_frame', true);
			$this->download = $plugin->getSetting('h5p_export', true);
			$this->embed = $plugin->getSetting('h5p_embed', true);
			$this->copyright = $plugin->getSetting('h5p_copyright', true);
			$this->about = $plugin->getSetting('h5p_icon', true);
			$this->track_user = $plugin->getSetting('h5p_track_user', true);
			$this->save_content_state = $plugin->getSetting('h5p_save_content_state', false);
			$this->save_content_frequency = $plugin->getSetting('h5p_save_content_frequency', 30);
			$this->show_toggle_view_others_h5p_contents = $plugin->getSetting('h5p_show_toggle_view_others_h5p_contents', 0);
			$this->insert_method = $plugin->getSetting('h5p_insert_method', 'id');
			$this->enable_lrs_content_types = $plugin->getSetting('h5p_enable_lrs_content_types', false);
			$this->enable_hub = $plugin->getSetting('h5p_hub_is_enabled', false);
			$this->send_usage_statistics = $plugin->getSetting('h5p_send_usage_statistics', true);
			$this->library_development = $plugin->getSetting('h5p_library_development', false);
			$this->dev_mode = $plugin->getSetting('h5p_dev_mode', false);
		}

		// Attach disable hub configuration

		// Get error messages
		$errors = $core->checkSetupErrorMessage()->errors;
		$disableHubData = array(
			'errors' => $errors,
			'header' => $core->h5pF->t('Confirmation action'),
			'confirmationDialogMsg' => $core->h5pF->t('Do you still want to enable the hub ?'),
			'cancelLabel' => $core->h5pF->t('Cancel'),
			'confirmLabel' => $core->h5pF->t('Confirm'),
		);
		$plugin->print_settings($disableHubData, 'H5PDisableHubData');

		$document->addStyleSheet(Uri::root() . 'media/com_h5p/css/admin.css');

		H5pHelper::addSubMenu('settings');
		parent::display($tpl);

		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/jquery.js');
		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-event-dispatcher.js');
		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-confirmation-dialog.js');
		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/settings/h5p-disable-hub.js');
		$document->addScript(Uri::root() . 'libraries/h5p/h5p-php-library/js/h5p-display-options.js');
		$document->addStyleSheet(Uri::root() . 'libraries/h5p/h5p-php-library/styles/h5p-confirmation-dialog.css');
		$document->addStyleSheet(Uri::root() . 'libraries/h5p/h5p-php-library/styles/h5p.css');
		$document->addStyleSheet(Uri::root() . 'libraries/h5p/h5p-php-library/styles/h5p-core-button.css');

		new H5PEventHelper('settings');
	}
}
