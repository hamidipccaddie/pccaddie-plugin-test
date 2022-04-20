<?php

// Enable output buffering
// "Headers already sent"
ob_start();


/**
 * PCCo basic class
 */
class PCCo
{


    /**
     * Constructor function
     */
    public function __construct()
    {
        // Validate user inputs for $_REQUEST['pcco']
        if (isset($_REQUEST['pcco']))
        {
            $_REQUEST['pcco'] = pcco_validate_settings($_REQUEST['pcco']);
        }
    }


    /**
     * PCCo login form
     */
    public function pcco_login_form()
    {
        // Check information about the installed version
        if (!function_exists('get_plugin_data'))
        {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $aPluginData = get_plugin_data(__dir__ . '/../pcco.php', false, false);
        $aOptions = get_option('pcco_options');
        global $sTranslation;

        return '
            <div class="pcco-container pcco-plugin-v' . str_replace('.', '-', $aPluginData['Version']) . ' container-fluid">
                <h1 class="pcco-headline">' . __('Anmeldung', 'pccoFrontend'). '</h1>

                <!-- | PC CADDIE://online WordPress Plugin v' . $aPluginData['Version'] . ' | -->

                <form action="" method="post">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="pcco[user]">' . __('Benutzername', 'pccoFrontend') . '</label>
                                <input type="text" class="form-control pcco-input" name="pcco[user]" value="' . ($_REQUEST['pcco']['user'] ?? '') . '" placeholder="' . __('E-Mail', 'pccoFrontend') . '">
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="pcco[pass]">' . __('Passwort', 'pccoFrontend') . '</label>
                                <input type="password" class="form-control pcco-input" name="pcco[pass]" placeholder="' . __('Passwort', 'pccoFrontend') . '">
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <button type="submit" class="btn btn-default pcco-button">' . __('Anmelden', 'pccoFrontend') . '</button>
                        </div>

                        <div class="col-sm-12">
                            <p class="pcco-access">' . __('Zugangsdaten vergessen', 'pccoFrontend') . '</p>

                            <iframe
                                id="pcco-access"
                                src="' . PCCO_BASE_URL . '/clubs/' . $aOptions['clubnumbercountry'] . '/cms.php?cat=access&lang=' . $sTranslation . '"
                                scrolling="no">
                            </iframe>
                        </div>

                        <script>
                            jQuery(document).ready(function()
                            {
                                var iFrame = jQuery("#pcco-access");
                                var button = jQuery(".pcco-access");

                                button.click(function()
                                {
                                    if (iFrame.css("display") === "none")
                                    {
                                        iFrame.css("display", "block");
                                    }

                                    else
                                    {
                                        iFrame.css("display", "none");
                                    }
                                });
                            });
                        </script>
                    </div>
                </form>
            </div>
        ';
    }


    /**
     * PCCo logout form
     */
    public function pcco_logout_form()
    {
        $sGender
            = $this->get('PC_GENDER') === 'male'
            ? __('Herr', 'pccoFrontend')
            : __('Frau', 'pccoFrontend');

        $sName
            = __('Hallo', 'pccoFrontend') . ' '
            . $sGender . ' '
            . $this->get('PC_TITEL') . ' '
            . $this->get('PC_FIRSTNAME') . ' '
            . $this->get('PC_NAME');

        return '
            <div class="pcco-container container-fluid">
                <h1 class="pcco-headline">'. $sName . '</h1>

                <form action="" method="post">
                    <div class="row">
                        <div class="col-sm-12">
                            <button type="submit" name="pcco[logout]" class="btn btn-default pcco-button">' . __('Logout', 'pccoFrontend') . '</button>
                        </div>
                    </div>
                </form>
            </div>
        ';
    }


    /**
     * Check login values
     */
    public function pcco_content_prepare()
    {
        $sEcho = '';
        global $sTranslation;

        // Login attempt
        if (isset($_REQUEST['pcco']['user']) && isset($_REQUEST['pcco']['pass']))
        {
            $aOptions = get_option('pcco_options');

            $sUser = $_REQUEST['pcco']['user'];
            $sPassword = $_REQUEST['pcco']['pass'];
            $sXmlUser = $aOptions['pccouser'];
            $sXmlPassword = $aOptions['pccopassword'];
            $sClubNumberCountry = $aOptions['clubnumbercountry'];

            // Rest API Url
            $sUrl = PCCO_BASE_URL . '/interface/rest.php';

            //Post data
            //Rest API otions
            $aApiOptions= array('action' => 'user_authenticate',
                             'club' => $sClubNumberCountry,
                             'check_local' => 1,
                             'get_auth_code' => 'fixated');

            //Club and User Credentials
            $aCredentials = array(
                'user'          => $sXmlUser,
                'password'      => $sXmlPassword,
                'user_login'    => $sUser,
                'user_password' => $sPassword
            );

            $aPostData=array_merge($aApiOptions,$aCredentials);

            //rest API result
            $aData = $this->httpPost($sUrl, $aPostData);

            // No user found for this input
            if (
                $aData['status'] !== 'OK' ||
                $aData['msg'] !== 'Success.' ||
                $aData['data']['user_item']['clubmember'] === false
            ) {
                $sEcho .= '
                    <div class="pcco-container container-fluid">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="pcco-alert">
                                    ' . __('Eingabe fehlerhaft', 'pccoFrontend') . '
                                </div>
                            </div>
                        </div>
                    </div>
                ';
            }

            // User found and session setting
            else
            {
                $this->set('PCCo_EXPIRE', time() + $aOptions['autologout'], time() + $aOptions['autologout']);
                $this->set('PC_ID', $aData['data']['user_item']['id'], time() + $aOptions['autologout']);
                $this->set('PC_TITEL', $aData['data']['user_item']['titel'], time() + $aOptions['autologout']);
                $this->set('PC_FIRSTNAME', $aData['data']['user_item']['forename'], time() + $aOptions['autologout']);
                $this->set('PC_NAME', $aData['data']['user_item']['surname'], time() + $aOptions['autologout']);
                $this->set('PC_GENDER', $aData['data']['user_item']['gender'], time() + $aOptions['autologout']);
                $this->set('PC_IS_MEMBER', $aData['data']['user_item']['clubmember'], time() + $aOptions['autologout']);
                $this->set('PC_TOKEN', $aData['data']['user_item']['auth_code'], time() + $aOptions['autologout']);
                $this->set('PC_ZUSATZ', $aData['data']['user_item']['info'] ?? 'guest', time() + $aOptions['autologout']);

                if (intval($aOptions['referer']) !== 1)
                {
                    $sRedirect  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $sRedirect .= $_SERVER['HTTP_HOST'] . WP_BASE_DIR . $aOptions['afterlogin' . strtoupper($sTranslation)];
                }

                else
                {
                    $sRedirect = $this->get('PC_REFERER');
                }

                header('Location: ' . $sRedirect);
                exit();
            }
        }

        // Logout attempt
        elseif (isset($_REQUEST['pcco']['logout']))
        {
            $aOptions = get_option('pcco_options');

            if (isset($_REQUEST['pccmobileclubID']))
            {
                $sClubNumberCountry = $_REQUEST['pccmobileclubID'];
            }

            elseif (!empty($aOptions['clubnumbercountry']))
            {
                $sClubNumberCountry = $aOptions['clubnumbercountry'];
            }

            else
            {
                $sClubNumberCountry = '';
            }

            $sUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?service=logout';
            $this->set('PC_LOGOUT', $sUrl, 0);

            $this->set('PCCo_EXPIRE', null, time() - 1800);
            $this->set('PC_ID', null, time() - 1800);
            $this->set('PC_TITEL', null, time() - 1800);
            $this->set('PC_FIRSTNAME', null, time() - 1800);
            $this->set('PC_NAME', null, time() - 1800);
            $this->set('PC_GENDER',null, time() - 1800);
            $this->set('PC_IS_MEMBER', null, time() - 1800);
            $this->set('PC_TOKEN', null, time() - 1800);
            $this->set('PC_ZUSATZ', null, time() - 1800);
            $this->set('PC_SECURED', null, time() - 1800);

            $sRedirect  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $sRedirect .= $_SERVER['HTTP_HOST'] . WP_BASE_DIR . $aOptions['redirect' . strtoupper($sTranslation)];

            header('Location: ' . $sRedirect);
            exit();
        }

        // Check user login
        if ($this->pcco_login_check() === true)
        {
            $sEcho .= $this->pcco_logout_form();
        }

        else
        {
            $sEcho .= $this->pcco_login_form();

            if (!empty($this->get('PC_LOGOUT')))
            {
                $sEcho .= '
                    <iframe
                        scrolling="no"
                        id="pcco-logout"
                        src="' . $this->get('PC_LOGOUT') . '&lang=' . $sTranslation . '&id=' . time() . '">
                    </iframe>
                ';

                $this->set('PC_LOGOUT', null, time() - 1800);
            }
        }

        return $sEcho;
    }


    /**
     * Checks correct plugin configuration
     */
    function check_plugin_config()
    {
        $aOptions = get_option('pcco_options');
        global $aIgnore;

        if (!empty($aOptions))
        {
            foreach ($aOptions as $sFieldName => $sFieldValue)
            {
                if (!in_array($sFieldName, $aIgnore) && empty($sFieldValue))
                {
                    return '
                        <div class="pcco-container container-fluid">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="pcco-alert">
                                        <p>
                                            ' . __('Konfiguration fehlerhaft', 'pccoFrontend') . '
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                }
            }
        }

        return '';
    }


    /**
     * Check login status
     */
    public function pcco_login_check()
    {
        return
            !empty($this->get('PC_FIRSTNAME')) &&
            !empty($this->get('PC_NAME')) &&
            !empty($this->get('PC_GENDER')) &&
            !empty($this->get('PC_TOKEN'));
    }


    /**
     * Set cookie value
     */
    public function set($sName, $sValue='', $iExpire=0)
    {
        return setcookie($sName, $sValue, $iExpire, '/');
    }


    /**
     * Get cookie value
     */
    public function get($sName)
    {
        return !empty($_COOKIE[$sName]) ? $_COOKIE[$sName] : false;
    }


    /**
     * Display PCCo iFrame
     */
    public function pcco_iframe($sType, $sIframe = '')
    {
        $sEventUrl  = !empty($_REQUEST['pcco']['eventid']) ? '&id=' . $_REQUEST['pcco']['eventid'] : '';
        $sEventUrl .= !empty($_REQUEST['pcco']['sub']) ? '&sub=' . $_REQUEST['pcco']['sub'] : '';

        $aOptions = get_option('pcco_options');
        global $sTranslation;

        if (isset($_REQUEST['pcco']['cnc']))
        {
            $sClubNumberCountry = $_REQUEST['pcco']['cnc'];
        }

        elseif (!empty($aOptions['clubnumbercountry']))
        {
            $sClubNumberCountry = $aOptions['clubnumbercountry'];
        }

        else
        {
            $sClubNumberCountry = '';
        }

        switch ($sType)
        {
            case 'teetimes':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=tt_timetable_course&auth=' . $this->get('PC_TOKEN');
                break;

            case 'trainer':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=tt_timetable_trainer&auth=' . $this->get('PC_TOKEN');
                break;

            case 'trainerweek':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=tt_timetable_trainer_daylist&auth=' . $this->get('PC_TOKEN');
                break;

            case 'calendar':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=ts_calendar&auth=' . $this->get('PC_TOKEN') . $sEventUrl;
                break;

            case 'startlist':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=ts_startlist&auth=' . $this->get('PC_TOKEN') . $sEventUrl;
                break;

            case 'resultlist':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=ts_resultlist&auth=' . $this->get('PC_TOKEN') . $sEventUrl;
                break;

            case 'course':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=ts_calendar_course&auth=' . $this->get('PC_TOKEN');
                break;

            case 'hcplist':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/hcp.php?auth=' . $this->get('PC_TOKEN');
                break;

            case 'memberlist':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/crm.php?action=memberlist&auth=' . $this->get('PC_TOKEN');
                break;

            case 'memberdata':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/crm.php?action=edit&auth=' . $this->get('PC_TOKEN');
                break;

            case 'mailgroup':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=mailgroup&auth=' . $this->get('PC_TOKEN');
                break;

            case 'entry':
                $sIframeUrl = PCCO_BASE_URL . '/clubs/' . $sClubNumberCountry . '/cms.php?cat=ts_calendar&auth=' . $this->get('PC_TOKEN') . '&sub=register' . $sEventUrl;
                break;

            case 'userdefined':
            default:

                if ($sIframe === 'notValid')
                {
                    return '
                        <div class="pcco-container container-fluid">
                            <div class="row">
                                <div class="col-sm-12">
                                    ' . __('Parameter fehlerhaft', 'pccoFrontend') . '
                                </div>
                            </div>
                        </div>
                    ';
                }

                $sIframeUrl = $sIframe .'auth='.$this->get('PC_TOKEN');
                break;
        }

        return '
            <div class="pcco-container container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        <iframe
                            scrolling="no"
                            id="pcco-frame"
                            src="' . $sIframeUrl . '&lang=' . $sTranslation . '">
                        </iframe>
                    </div>
                </div>
            </div>
        ';
    }

    /* *
     * get our Rest API data with cURL
     */
    public function httpPost(string $sUrl, array $aPostData)
    {
        //cURL initializing
        $ch = curl_init($sUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // set the post fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $aPostData);

        // execute!
        $sResponse = curl_exec($ch);

        // close the connection, release resources used
        curl_close($ch);

        // decode our result to json
        return json_decode($sResponse,true);
    }
}
