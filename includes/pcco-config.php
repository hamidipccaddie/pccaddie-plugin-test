<?php

/**
 * Design menu integration
 */
function pcco_menu()
{
    add_theme_page
    (
        'PC CADDIE://online',   // $page_title
        'PC CADDIE://online',   // $menu_title
        'manage_options',       // $capability
        'pcco_options.php',     // $menu_slug
        'pcco_options'          // $function (callback)
    );
}
add_action('admin_menu', 'pcco_menu', 10, 1);


/**
 * Callback function for pcco_menu
 */
function pcco_options()
{

?>
    <div>
        <h1>PC CADDIE://online</h1>
        <form method="post" action="options.php" enctype="multipart/form-data">

            <?php

                $aOptions = get_option('pcco_options');
                global $aIgnore;

                if (!empty($aOptions))
                {
                    foreach ($aOptions as $sFieldName => $sFieldValue)
                    {
                        if (
                              !in_array($sFieldName, $aIgnore)
                            && empty($sFieldValue)
                        ) {
                            echo '<div class="pcco-warning">';
                            echo '[ ' . $sFieldName . ' ] ';
                            _e('Pflichtangabe', 'pccoBackend');
                            echo '</div>';
                        }
                    }
                }

                settings_fields('pcco_options');
                do_settings_sections('pcco_options.php');
            ?>

            <p>
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>

            <style>
                .pcco-warning {
                    padding: 10px;
                    margin: 0 20px 5px 0;
                    background-color: #fcf8e3;
                    border: 1px solid #faebcc;
                    border-radius: 4px;
                    color: #8a6d3b;
                }
            </style>
        </form>
    </div>
<?php

}


/**
 * Function to register the settings
 */
function pcco_register_settings()
{
    // Register the settings with validation callback
    register_setting('pcco_options', 'pcco_options', 'pcco_validate_settings');

    // Add settings section
    add_settings_section('pcco_text_section', '', '', 'pcco_options.php');

    // Get options
    $aLanguage = array();
    $sOptionName = 'pcco_options';
    $aOptions = get_option($sOptionName);

    if (isset($aOptions['language']))
    {
        $aLanguage = explode(',', trim($aOptions['language'], ','));
    }

    // Create textbox field
    $aFields = array();

    $aFields['pccouser']                        = array
    (
        'type'                                  => 'text',
        'id'                                    => 'pccouser',
        'name'                                  => 'pccouser',
        'placeholder'                           => __('Benutzername', 'pccoBackend'),
        'class'                                 => 'pcco-text'
    );

   $aFields['pccopassword']                     = array
   (
        'type'                                  => 'password',
        'id'                                    => 'pccopassword',
        'name'                                  => 'pccopassword',
        'placeholder'                           => __('Passwort', 'pccoBackend'),
        'class'                                 => 'pcco-text'
    );

    $aFields['clubnumbercountry']               = array
    (
        'type'                                  => 'text',
        'id'                                    => 'clubnumbercountry',
        'name'                                  => 'clubnumbercountry',
        'placeholder'                           => '0490000',
        'class'                                 => 'pcco-text'
    );

    $aFields['datefrom']                        = array
    (
        'type'                                  => 'text',
        'id'                                    => 'datefrom',
        'name'                                  => 'datefrom',
        'placeholder'                           => '0',
        'class'                                 => 'pcco-text'
    );

    $aFields['dateto']                          = array
    (
        'type'                                  => 'text',
        'id'                                    => 'dateto',
        'name'                                  => 'dateto',
        'placeholder'                           => '0',
        'class'                                 => 'pcco-text'
    );

    $aFields['minentries']                      = array
    (
        'type'                                  => 'text',
        'id'                                    => 'minentries',
        'name'                                  => 'minentries',
        'placeholder'                           => '0',
        'class'                                 => 'pcco-text'
    );

    $aFields['maxentries']                      = array
    (
        'type'                                  => 'text',
        'id'                                    => 'maxentries',
        'name'                                  => 'maxentries',
        'placeholder'                           => '0',
        'class'                                 => 'pcco-text'
    );

    $aFields['autologout']                      = array
    (
        'type'                                  => 'text',
        'id'                                    => 'autologout',
        'name'                                  => 'autologout',
        'placeholder'                           => '0',
        'class'                                 => 'pcco-text'
    );

    $aFields['language']                        = array
    (
        'type'                                  => 'text',
        'id'                                    => 'language',
        'name'                                  => 'language',
        'placeholder'                           => 'de, fr, en',
        'class'                                 => 'pcco-text'
    );

    if (!empty($aLanguage))
    {
        foreach ($aLanguage as $sLanguage)
        {
            $aFields['eventurl'][$sLanguage]    = array
            (
                'type'                          => 'text',
                'id'                            => 'eventurl' . strtoupper(trim($sLanguage)),
                'name'                          => 'eventurl' . strtoupper(trim($sLanguage)),
                'placeholder'                   => 'event/',
                'class'                         => 'pcco-text'
            );

            $aFields['redirect'][$sLanguage]    = array
            (
                'type'                          => 'text',
                'id'                            => 'redirect' . strtoupper(trim($sLanguage)),
                'name'                          => 'redirect' . strtoupper(trim($sLanguage)),
                'placeholder'                   => 'redirect/',
                'class'                         => 'pcco-text'
            );

            $aFields['afterlogin'][$sLanguage]  = array
            (
                'type'                          => 'text',
                'id'                            => 'afterlogin' . strtoupper(trim($sLanguage)),
                'name'                          => 'afterlogin' . strtoupper(trim($sLanguage)),
                'placeholder'                   => 'afterlogin/',
                'class'                         => 'pcco-text'
            );
        }
    }

    $aFields['referer']                         = array
    (
        'type'                                  => 'select',
        'id'                                    => 'referer',
        'name'                                  => 'referer',
        'placeholder'                           => '',
        'class'                                 => 'pcco-select',
        'value'                                 =>
            array
            (
                '0' => '',
                '1' => __('Ja', 'pccoBackend'),
                '2' => __('Nein', 'pccoBackend')
            )
    );

    $aFields['layout']                          = array
    (
        'type'                                  => 'textarea',
        'id'                                    => 'layout',
        'name'                                  => 'layout',
        'placeholder'                           => '',
        'class'                                 => 'pcco-textarea'
    );

    foreach ($aFields as $sKey => $aField)
    {
        if (
               $sKey === 'eventurl'
            || $sKey === 'redirect'
            || $sKey === 'afterlogin'
        ) {
            foreach ($aField as $sLanguage => $aField2)
            {
                add_settings_field
                (
                    $aField2['id'],
                    __($sKey, 'pccoBackend') . ' [' . trim($sLanguage) . ']',
                    'pcco_display_setting',
                    'pcco_options.php',
                    'pcco_text_section',
                    $aField2
                );
            }
        }

        else
        {
            add_settings_field
            (
                $sKey,
                __($sKey, 'pccoBackend'),
                'pcco_display_setting',
                'pcco_options.php',
                'pcco_text_section',
                $aField
            );
        }
    }
}
add_action('admin_init', 'pcco_register_settings');


/**
 * Display option input fields
 */
function pcco_display_setting($args)
{
    extract($args);

    $sOptionName = 'pcco_options';
    $aOptions = get_option($sOptionName);

    switch ($type)
    {
        case 'text':
        case 'password':

            echo
                '<input class="regular-text ' . $class .
                '" type="' . $type .
                '" id="' . $id .
                '" name="' . $sOptionName . '[' . $id . ']' .
                '" placeholder="' . $placeholder .
                '" value="' . $aOptions[$id] ?? '' .
                '" />';

            break;

        case 'select':

            echo '<select id="' . $id . '" class="' . $class . '" name="' . $sOptionName . '[' . $id . ']' . '">';

            if (!empty($value))
            {
                foreach ($value as $iValue => $sOption)
                {
                    echo '<option value="' . $iValue . '"' . (intval($aOptions[$id] ?? '') === $iValue ? 'selected="selected"' : '') . '>' . $sOption . '</option>';
                }
            }

            echo '</select>';
            break;

        case 'hidden':

            echo
                '<input class="' . $class .
                '" type="' . $type .
                '" id="' . $id .
                '" name="' . $sOptionName . '[' . $id . ']' .
                '" value="' . $aOptions[$id] .
                '" />';

            break;

        default:

            echo
                '<textarea  cols="30" rows="4" class="regular-text ' . $class .
                '" name="' . $sOptionName . '[' . $id . ']' .
                '" placeholder="' . $placeholder .
                '">' . $aOptions[$id] ?? '' .
                '</textarea>';
    }
}
