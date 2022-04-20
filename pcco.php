<?php

/**
 * @wordpress-plugin
 *
 * Description:             Enables iFrame-Support, Event-Snapshot and Single Sign-on for PC CADDIE://online integration.
 * Plugin Name:             PC CADDIE://online WordPress Plugin
 * Version:                 2.11.1
 *
 * Author:                  PC CADDIE://online GmbH & Co. KG
 * Author URI:              https://www.pccaddie.net/
 */

// Version History
// 2.11.1   02.08.2021      Fixed the user Gender Greeting
// 2.11.0   15.07.2021      Using the new Rest API
// 2.10.2   18.05.2020      Added additional cookie information
// 2.10.1   11.05.2020      Fixed WP_Error handling for update check
// 2.10.0   26.03.2020      Added shortcode for individual iFrame integration
// 2.9.1    12.03.2020      Fixed cookie setting issue for configuration
// 2.9.0    12.03.2020      Added redirect after login setting to configuration
// 2.8.1    28.11.2019      Fixed iframeResizer integration issue
// 2.8.0    28.11.2019      Added autologout setting to configuration
// 2.7.0    12.11.2019      Added shortcode for mailgroups
// 2.6.1    12.11.2019      Fixed logout issue for iFrame integration
// 2.6.0    25.01.2019      Updates for authentication method
// 2.5.0    20.06.2018      Added back- and frontend localization
// 2.4.0    19.06.2018      Added shortcode for course booking
// 2.3.1    01.06.2018      Fixed logout issue for mobile devices
// 2.3.0    27.03.2018      Added update function for WordPress plugin
// 2.2.1    26.03.2018      Fixed datefrom issue for tournament snapshot
// 2.2.0    16.03.2018      Added referrer function as target page after login
// 2.1.0    16.03.2018      Added maxentries to tournament snapshot configuration
// 2.0.1    17.01.2018      Fixed redirect issue on protected pages
// 2.0.0    06.12.2017      Optimization and functional extension
// 1.0.7    18.10.2017      Added hidden iFrame for logout
// 1.0.5    26.09.2017      Updates for functions
// 1.0.4    25.09.2017      Updates for functions
// 1.0.3    25.09.2017      Updates for functions
// 1.0.2    25.09.2017      Updates for layout
// 1.0.1    10.09.2017      Added new functions
// 1.0.0    28.08.2017      Basic functions for WordPress 4.8+

if (!defined('WPINC'))
{
    die();
}


// Initialize variables
$aIgnore = array('datefrom', 'dateto', 'minentries', 'maxentries', 'eventurl', 'layout', 'valid');


// Load plugin translation respectively localization
if (is_file(dirname(__file__) . '/../pccaddie-localization/pcco.php'))
{
    include_once(dirname(__file__) . '/../pccaddie-localization/pcco.php');
    $sTranslation = pcco_localization();

    switch ($sTranslation)
    {
        case 'en':

            $sLocalization = 'en_GB';
            break;

        case 'fr':

            $sLocalization = 'fr_FR';
            break;

        default:

            $sLocalization = 'de_DE';
    }
}

else
{
    $sTranslation = 'de';
    $sLocalization = 'de_DE';
}

$sBackendTranslation
    = is_file(dirname(__file__) . '/languages/' . get_locale() . '.mo')
    ? dirname(__file__) . '/languages/' . get_locale() . '.mo'
    : dirname(__file__) . '/languages/de_DE.mo';

$sFrontendTranslation
    = is_file(dirname(__file__) . '/languages/' . $sLocalization . '.mo')
    ? dirname(__file__) . '/languages/' . $sLocalization . '.mo'
    : dirname(__file__) . '/languages/de_DE.mo';

load_textdomain('pccoBackend', $sBackendTranslation);
load_textdomain('pccoFrontend', $sFrontendTranslation);


// Define BASE_URL and WordPress basedir/folder
$aDir = explode('/', dirname(__file__));
define('WP_PLUGIN_FOLDER', array_pop($aDir));
define('PCCO_BASE_URL', 'https://www.pccaddie.net');
define('WP_BASE_DIR', get_base_dir());


// Include required files
include_once(dirname(__file__) . '/includes/pcco-config.php');
include_once(dirname(__file__) . '/includes/pcco-class.php');
include_once(dirname(__file__) . '/includes/pcco-shortcode.php');
include_once(dirname(__file__) . '/includes/pcco-restrication.php');


/**
 * PCCo debug function
 */
function pcco_debug($xInput)
{
    if (is_array($xInput) || is_object($xInput))
    {
        echo '<pre>', print_r($xInput), '</pre>';
    }

    else
    {
        echo $xInput;
    }
}


/**
 * Validate configuration input
 */
function pcco_validate_settings($xInput)
{
    if (is_array($xInput) || is_object($xInput))
    {
        foreach ($xInput as $k => $v)
        {
            $xInput[$k] = pcco_validate_settings($v);
        }
    }

    elseif (!empty($xInput))
    {
        // $xInput = str_replace(array("\r\n", "\n\r", "\n", "\r"), ' ', $xInput); // removes wordwrap
        // $xInput = preg_replace('~\s\s+~', ' ', $xInput); // removes double spaces
        $xInput = str_replace(array('"', '\''), '', $xInput); // removes quotes
        $xInput = strip_tags($xInput); // removes NULL bytes, HTML and PHP
        $xInput = stripslashes($xInput); // unquotes a quoted string
        $xInput = trim($xInput); // removes leading and trailing spaces
    }

    return $xInput;
}


/**
 * Get WordPress basedir
 */
function get_base_dir()
{
    $sBaseDir = '/';
    $aBaseDir = explode('/', $_SERVER['SCRIPT_NAME']);

    if (!empty($aBaseDir))
    {
        foreach ($aBaseDir as $sFolder)
        {
            if (
                  !empty($sFolder)
                && stripos($sFolder, 'index.php') === false
                && stripos($sFolder, 'wp-login.php') === false
            ) {
                $sBaseDir .= $sFolder . '/';
            }
        }
    }

    return $sBaseDir;
}


/**
 * Register PCCo styles
 */
function pcco_scripts()
{
    // Register script for the plugin
    wp_register_style('pcco-style', '/wp-content/plugins/' . WP_PLUGIN_FOLDER . '/assets/css/pcco-style.css');

    // Enqueue registered script
    wp_enqueue_style('pcco-style');
}
add_action('wp_enqueue_scripts', 'pcco_scripts');


/**
 * Create PCCo header
 */
function pcco_header()
{
    global $sTranslation;

    $classPCCo = new PCCo;
    $aOptions = get_option('pcco_options');

    $sReferer  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $sReferer .= $_SERVER['HTTP_HOST'] . WP_BASE_DIR . $aOptions['redirect' . strtoupper($sTranslation)];

    if (!empty($_SERVER['HTTP_REFERER']))
    {
        $sReferer = $_SERVER['HTTP_REFERER'];
    }

    if (intval($classPCCo->get('PC_SECURED')) === 1)
    {
        $sReferer = $classPCCo->get('PC_REFERER');
        $classPCCo->set('PC_SECURED', 0, time() + $aOptions['autologout']);
    }

    if (intval($aOptions['referer']) === 1)
    {
        $classPCCo->set('PC_REFERER', $sReferer, time() + $aOptions['autologout']);
    }

    if (!empty($aOptions['layout']))
    {
        echo '<style>' . $aOptions['layout'] . '</style>';
    }
}
add_action('wp_head', 'pcco_header');


/**
 * Create PCCo footer
 */
function pcco_footer()
{
    global $sTranslation;

    $classPCCo = new PCCo;
    $aOptions = get_option('pcco_options');

    $sIframeResizer  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $sIframeResizer .= $_SERVER['HTTP_HOST'] . WP_BASE_DIR . 'wp-content/plugins/' . WP_PLUGIN_FOLDER . '/assets/js/iframeResizer.min.js';

    // Check timestamp of expire cookie
    if (time() > ($classPCCo->get('PCCo_EXPIRE')))
    {
        echo '<iframe ';
        echo 'scrolling="no" ';
        echo 'id="pcco-logout" ';
        echo 'src="' . PCCO_BASE_URL . '/clubs/' . $aOptions['clubnumbercountry'] . '/cms.php?service=logout&lang=' . $sTranslation . '&id=' . time(). '">';
        echo '</iframe>';

        $classPCCo->set('PC_ID', null, time() - 1800);
        $classPCCo->set('PC_TITEL', null, time() - 1800);
        $classPCCo->set('PC_FIRSTNAME', null, time() - 1800);
        $classPCCo->set('PC_NAME', null, time() - 1800);
        $classPCCo->set('PC_GENDER',null, time() - 1800);
        $classPCCo->set('PC_IS_MEMBER', null, time() - 1800);
        $classPCCo->set('PC_TOKEN', null, time() - 1800);
        $classPCCo->set('PC_ZUSATZ', null, time() - 1800);
    }

    if (!empty($classPCCo->get('PC_TOKEN')))
    {
        $classPCCo->set('PCCo_EXPIRE', time() + $aOptions['autologout'], time() + $aOptions['autologout']);
    }

    echo '<script type="text/javascript" src="' . $sIframeResizer . '"></script>';

?>

<script>

    iFrameResize();

</script>

<?php

}
add_action('wp_footer', 'pcco_footer');


/**
 * Check current version number and updates
 */
function check_for_updates($oTransient)
{
    if (empty($oTransient->checked))
    {
        return $oTransient;
    }

    // Check current version available for download
    $sUrl = PCCO_BASE_URL . '/plugin/wordpress/version.txt';
    $xResponse = wp_remote_get($sUrl);

    if (is_wp_error($xResponse))
    {
        return $oTransient;
    }

    $sVersion = $xResponse['body'];

    // Check information about the installed version
    if (!function_exists('get_plugin_data'))
    {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $aPluginData = get_plugin_data(__file__, false, false);

    // Check if update is needed
    if (version_compare($sVersion, $aPluginData['Version'], '>'))
    {
        $aUpdateInfo = array
        (
            'plugin' => plugin_basename(__file__),
            'slug' => plugin_basename(__file__),
            'new_version' => $sVersion
        );

        $oTransient->response[plugin_basename(__file__)] = (object)$aUpdateInfo;
    }

    return $oTransient;
}
add_action('pre_set_site_transient_update_plugins', 'check_for_updates');


/**
 * Check current version information
 */
function check_for_information($false, $action, $oArg)
{
    if (
           isset($oArg->slug)
        && $oArg->slug === plugin_basename(__file__)
    ) {
        $xRequest = wp_remote_get(PCCO_BASE_URL . '/plugin/wordpress/version.php');

        if (
              !is_wp_error($xRequest)
            || wp_remote_retrieve_response_code($xRequest) === 200
        ) {
            return unserialize( $xRequest['body'] );
        }

        return false;
    }

    return false;
}
add_filter('plugins_api', 'check_for_information', 10, 3);
