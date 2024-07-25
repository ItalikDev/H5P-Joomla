<?php
/**
 * @package    pkg_h5p_mathjax
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

use VB\Component\H5P\Administrator\Helper\H5PJoomlaHelper;

jimport('joomla.plugin.plugin');


class plgH5pMathjax extends JPlugin {

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		define('H5P_LIBRARY_CONFIG', array(
			"H5P.MathDisplay" => array(
			  "observers" => array(
				array("name" => "mutationObserver", "params" => array("cooldown" => 500)),
				array("name" => "domChangedListener"),
				array("name" => "interval", "params" => array("time" => 1000))
			  ),
			  "renderer" => array(
				"mathjax" => array(
				  "src" => "https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js",
				  "config" => array(
					"extensions" => array("tex2jax.js"),
					"jax" => array("input/TeX", "output/HTML-CSS"),
					"tex2jax" => array(
					  // Important, otherwise MathJax will be rendered inside CKEditor
					  "ignoreClass" => "ckeditor"
					),
					"messageStyle" => "none"
				  )
				)
			  )
			)
		  ));
	}
}