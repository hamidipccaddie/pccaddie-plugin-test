<?php

/**
 * Create PCCo event snapshot
 */
function pcco_snapshot()
{
    // Check information about the installed version
    if (!function_exists('get_plugin_data'))
    {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $aPluginData = get_plugin_data(__dir__ . '/../pcco.php', false, false);

    // Get options
    $aOptions = get_option('pcco_options');
    global $sTranslation;

    // Validate options
    $aOptions['maxentries'] = !empty($aOptions['maxentries']) ? $aOptions['maxentries'] : 0;
    $aOptions['minentries'] = !empty($aOptions['minentries']) ? $aOptions['minentries'] : 0;
    $aOptions['datefrom'] = !empty($aOptions['datefrom']) ? $aOptions['datefrom'] : 0;
    $aOptions['dateto'] = !empty($aOptions['dateto']) ? $aOptions['dateto'] : 0;

    // Create url
    $dStartDate = date('Y-m-d', strtotime($aOptions['datefrom'] . ' days'));
    $dEndDate = date('Y-m-d', strtotime($aOptions['dateto'] . ' days'));

    $sUrl = PCCO_BASE_URL . '/interface/platzbelegung.php' .

        '?user=' . urlencode($aOptions['pccouser']) .
        '&password=' . urlencode($aOptions['pccopassword']) .
        '&club=' . urlencode($aOptions['clubnumbercountry']) .
        '&datumvon=' . urlencode($dStartDate) .
        '&datumbis=' . urlencode($dEndDate) .
        '&showjson=1'.

        (!empty($aOptions['minentries']) ? '&minentries=' . urlencode($aOptions['minentries']) : '');

    $sJson = file_get_contents($sUrl);
    $aData = json_decode($sJson, true);

    if (!empty($aData['ANTWORT']))
    {
        unset($aData['ANTWORT']);
    }

    if (!empty($aData['REQUEST']))
    {
        unset($aData['REQUEST']);
    }

    if (empty($aData))
    {
        return '
            <!-- | PC CADDIE://online WordPress Plugin v' . $aPluginData['Version'] . ' | -->

            <div class="pcco-container pcco-plugin-v' . str_replace('.', '-', $aPluginData['Version']) . ' container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="pcco-alert">
                            ' . __('Kein Termin', 'pccoFrontend') . '
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    $iEventCount = 0;
    $sEcho =
        '<!-- | PC CADDIE://online WordPress Plugin v' . $aPluginData['Version'] . ' | -->' .

        '<div class="pcco-container pcco-plugin-v' . str_replace('.', '-', $aPluginData['Version']) . ' container-fluid">' .
            '<div class="pcco-snapshot-container">';

    foreach ($aData as $aEvent)
    {
        if (is_array($aEvent))
        {
            $currentMoment = date('Y-m-d H:i:s');
            $aExplodedDATUM = explode('-', $aEvent['DATUM']);
            $aExplodedDATUM_ENDE = explode('-', $aEvent['DATUM_ENDE']);
            $sEventUrl = $aOptions['eventurl' . strtoupper($sTranslation)] . (strpos($aOptions['eventurl' . strtoupper($sTranslation)], '?') === false ? '?' : '');

            if     ($currentMoment >= $aEvent['MELDUNG_AB'] && $currentMoment <= $aEvent['MELDESCHLUSS'])   { $sEventUrl .= '&pcco[sub]=register'; }
            elseif (!empty($aEvent['STARTLISTE']))                                                          { $sEventUrl .= '&pcco[sub]=startlist'; }
            elseif (!empty($aEvent['ERGEBNISLISTE']))                                                       { $sEventUrl .= '&pcco[sub]=resultlist'; }
            else                                                                                            { $sEventUrl .= '&pcco[sub]=details'; }

            $sEcho .=
                '<div class="pcco-snapshot">' .

                    '<div class="pcco-snapshot-appear">' .
                        '<p class="pcco-snapshot-appear-date">' .
                            '<span class="pcco-day">' . $aExplodedDATUM[2] . '.</span>' .
                            '<span class="pcco-month">' . $aExplodedDATUM[1] . '.</span>' .
                            '<span class="pcco-year">' . $aExplodedDATUM[0] . '</span>' .
                        '</p>' .

                        '<p class="pcco-snapshot-appear-endtime">' .
                            '<span class="pcco-day">' . $aExplodedDATUM_ENDE[2] . '.</span>' .
                            '<span class="pcco-month">' . $aExplodedDATUM_ENDE[1] . '.</span>' .
                            '<span class="pcco-year">' . $aExplodedDATUM_ENDE[0] . '</span>' .
                        '</p>' .

                        '<p class="pcco-snapshot-appear-name">' . $aEvent['TURNIERNAME'] . '</p>' .
                        (!empty($aEvent['STARTINFO']) ? '<p class="pcco-snapshot-appear-info">' . $aEvent['STARTINFO'] . '</p>' : '') .
                        (!empty($aEvent['TIME_END']) ? '<p class="pcco-snapshot-appear-end">Beginn: ' . $aEvent['TIME_END'] . ' ' . __('Uhr', 'pccoFrontend') : '') .
                        (!empty($aEvent['TIME_START']) ? '<p class="pcco-snapshot-appear-start">Ende: ' . $aEvent['TIME_START'] . ' ' . __('Uhr', 'pccoFrontend') : '');

                        if (!empty($aEvent['MELDUNG_AB']) && strtotime($aEvent['MELDUNG_AB']) <= time())
                        {
                            $sEcho .= '<p class="pcco-snapshot-appear-entrystart">'
                                   . __('Anmeldung ab', 'pccoFrontend') . ': '
                                   . date('d.m.Y H:i', strtotime($aEvent['MELDUNG_AB'])) . ' '
                                   . __('Uhr', 'pccoFrontend') . '</p>';
                        }

                        if (!empty($aEvent['MELDESCHLUSS']))
                        {
                            $sEcho .= '<p class="pcco-snapshot-appear-entryend">'
                                   . __('Anmeldung bis', 'pccoFrontend') . ': '
                                   . date('d.m.Y H:i', strtotime($aEvent['MELDESCHLUSS'])) . ' '
                                   . __('Uhr', 'pccoFrontend') . '</p>';
                        }

            $sEcho .=

                        '<p class="pcco-snapshot-appear-link">' .
                            '<a href="' . WP_BASE_DIR . $sEventUrl . '&pcco[eventid]=' . $aEvent['TURNIERID'] . '&pcco[cnc]=' . $aOptions['clubnumbercountry'] . '">' .
                                __('Turnierlink', 'pccoFrontend') .
                            '</a>' .
                        '</p>' .
                    '</div>' .

                    '<div class="pcco-snapshot-default">' .
                        '<p class="pcco-snapshot-default-date">' .
                            '<span class="pcco-day">' . $aExplodedDATUM[2] . '.</span>' .
                            '<span class="pcco-month">' . $aExplodedDATUM[1] . '.</span>' .
                            '<span class="pcco-year">' . $aExplodedDATUM[0] . '</span>' .
                        '</p>' .

                        '<p class="pcco-snapshot-default-name">' . $aEvent['TURNIERNAME'] . '</p>' .
                    '</div>' .
                '</div>';

            // Start new container after 4 events
            // because of width:25%; per element
            $iSpacerCount = ++$iEventCount%4;

            if (empty($iSpacerCount) && count($aData)-$iEventCount !== 0)
            {
                $sEcho .= '</div><div class="pcco-snapshot-container">';
            }

            if (!empty($aOptions['maxentries']) && intval($aOptions['maxentries']) === $iEventCount)
            {
                break;
            }
        }
    }

    // Fill empty spaces per row with dummies
    // because of display:table; layout of div
    if (4-$iSpacerCount<4)
    {
        for ($i=0; $i<4-$iSpacerCount; $i++)
        {
            $sEcho .= '<div class="pcco-snapshot-spacer"></div>';
        }
    }

    $sEcho .=
            '</div>' .
        '</div>';

    return $sEcho;
}
add_shortcode('pccosnapshot', 'pcco_snapshot');


/**
 * Login form
 */
function pcco_login()
{
    $classPCCo = new PCCo;
    $sConfig = $classPCCo->check_plugin_config();
    return $sConfig . $classPCCo->pcco_content_prepare();
}
add_shortcode('pccologin', 'pcco_login');


/**
 * Teetimes
 */
function pcco_iframe_teetimes()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('teetimes');
}
add_shortcode('pccoiframe_teetimes', 'pcco_iframe_teetimes');


/**
 * Trainer
 */
function pcco_iframe_trainer()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('trainer');
}
add_shortcode('pccoiframe_trainer', 'pcco_iframe_trainer');


/**
 * Trainerweek
 */
function pcco_iframe_trainerweek()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('trainerweek');
}
add_shortcode('pccoiframe_trainerweek', 'pcco_iframe_trainerweek');


/**
 * Calendar
 */
function pcco_iframe_calendar()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('calendar');
}
add_shortcode('pccoiframe_calendar', 'pcco_iframe_calendar');


/**
 * Startlist
 */
function pcco_iframe_startlist()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('startlist');
}
add_shortcode('pccoiframe_startlist', 'pcco_iframe_startlist');


/**
 * Results
 */
function pcco_iframe_resultlist()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('resultlist');
}
add_shortcode('pccoiframe_resultlist', 'pcco_iframe_resultlist');


/**
 * Tournament registration
 */
function pcco_iframe_entry()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('entry');
}
add_shortcode('pccoiframe_entry', 'pcco_iframe_entry');


/**
 * Course
 */
function pcco_iframe_course()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('course');
}
add_shortcode('pccoiframe_course', 'pcco_iframe_course');


/**
 * HCP list
 */
function pcco_iframe_hcplist()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('hcplist');
}
add_shortcode('pccoiframe_hcplist', 'pcco_iframe_hcplist');


/**
 * Member list search field
 */
function pcco_iframe_memberlist()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('memberlist');
}
add_shortcode('pccoiframe_memberlist', 'pcco_iframe_memberlist');


/**
 * Member list edit personal entry
 */
function pcco_iframe_memberdata()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('memberdata');
}
add_shortcode('pccoiframe_memberdata', 'pcco_iframe_memberdata');


/**
 * Newsletter / mailgroup settings
 */
function pcco_iframe_mailgroup()
{
    $classPCCo = new PCCo;
    return $classPCCo->pcco_iframe('mailgroup');
}
add_shortcode('pccoiframe_mailgroup', 'pcco_iframe_mailgroup');


/**
 * Individual iFrame integration
 */
function pcco_iframe_userdefined($aAttributes = array())
{
    $classPCCo = new PCCo;

    if (
           empty($aAttributes['iframe'])
        || filter_var($aAttributes['iframe'], FILTER_VALIDATE_URL) === false
        || strpos($aAttributes['iframe'], 'mobile.pccaddie.net') !== 8
        && strpos($aAttributes['iframe'], 'www.pccaddie.net') !== 8
    ) {
        $aAttributes['iframe'] = 'notValid';
    }

    else if (strpos($aAttributes['iframe'], '?') === false)
    {
        $aAttributes['iframe'] .= '?';
    }

    else
    {
        $aAttributes['iframe'] .= '&';
    }

    return $classPCCo->pcco_iframe('userdefined', $aAttributes['iframe']);
}
add_shortcode('pccoiframe_userdefined', 'pcco_iframe_userdefined');
