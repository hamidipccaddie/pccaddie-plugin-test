<?php

/**
 * Create meta box for PCCo restrictions
 * Pages > Edit > PC CADDIE://online Zugriffsschutz
 */
function user_restriction_meta_box($oPost)
{
    wp_nonce_field(dirname(__file__), 'pcco[meta-box-nonce]');

    $sUserMember = get_post_meta($oPost->ID, 'user_member', true);
    $sUserAdditionalInfo = get_post_meta($oPost->ID, 'user_additional_info', true);

    ?>
        <div>
            <p>
                <label for="pcco[user_additional_info]"><?php _e('Zusatz-Info', 'pccoBackend') ?></label>
                <input name="pcco[user_additional_info]" type="text" value="<?= $sUserAdditionalInfo ?>">
            </p>

            <p>
                <label for="pcco[user_member]"><?php _e('Nur für Mitglieder', 'pccoBackend') ?></label>
                <input name="pcco[user_member]" type="checkbox" value="true" <?php echo $sUserMember === 'true' ? 'checked' : ''; ?>>
            </p>
        </div>

    <?php

}


/**
 * Add meta box for PCCo restrictions
 * Pages > Edit > PC CADDIE://online Zugriffsschutz
 */
function add_restriction_meta_box()
{
    add_meta_box
    (
        'restriction-meta-box',
        __('Meta Box Headline', 'pccoBackend'),
        'user_restriction_meta_box',
        'page',
        'side',
        'high',
        null
    );
}
add_action('add_meta_boxes', 'add_restriction_meta_box');


/**
 * Save inputs for meta box for PCCo restrictions
 */
function save_restriction_meta_box($iPostId, $oPost, $update)
{
    if (!isset($_REQUEST['pcco']['meta-box-nonce']) || !wp_verify_nonce($_REQUEST['pcco']['meta-box-nonce'], dirname(__file__)))
    {
        return $iPostId;
    }

    if (!current_user_can('edit_post', $iPostId))
    {
        return $iPostId;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    {
        return $iPostId;
    }

    if ($oPost->post_type !== 'page')
    {
        return $iPostId;
    }

    // Validate user inputs for $_REQUEST['pcco']
    $_REQUEST['pcco'] = pcco_validate_settings($_REQUEST['pcco']);

    // Save required member status
    $sMetaBoxUserMember = isset($_REQUEST['pcco']['user_member']) ? $_REQUEST['pcco']['user_member'] : '';
    update_post_meta($iPostId, 'user_member', $sMetaBoxUserMember);

    // Save additional member information
    $sMetaBoxAdditionalInfo = isset($_REQUEST['pcco']['user_additional_info']) ? $_REQUEST['pcco']['user_additional_info'] : '';
    update_post_meta($iPostId, 'user_additional_info', $sMetaBoxAdditionalInfo);
}
add_action('save_post', 'save_restriction_meta_box', 10, 3);


/**
 * Create 404 event
 */
function page_404_event()
{
    global $post;
    global $sTranslation;

    $classPCCo = new PCCo;
    $aOptions = get_option('pcco_options');
    $sRedirect = !empty($aOptions['redirect' . strtoupper($sTranslation)]) ? $aOptions['redirect' . strtoupper($sTranslation)] : '/';

    if (get_post_meta(@$post->ID, 'user_member', true))
    {
        $iMember = $classPCCo->get('PC_IS_MEMBER');
        $bMember = get_post_meta(@$post->ID, 'user_member', true);

        if ($bMember === 'true' && $iMember !== '1')
        {
            if (intval($aOptions['referer']) === 1)
            {
                $classPCCo->set('PC_REFERER', $_SERVER['REQUEST_URI'], time() + $aOptions['autologout']);
            }

            $classPCCo->set('PC_SECURED', 1, time() + $aOptions['autologout']);

            wp_safe_redirect
            (
                add_query_arg
                (
                    'pcco[403]',
                    'forbidden',
                    wp_login_url($sRedirect)
                )
            );

            exit();
        }
    }

    if (get_post_meta(@$post->ID, 'user_additional_info', true))
    {
        $bForbidden = true;
        $aMemberAdditionalInfo = explode(';', $classPCCo->get('PC_ZUSATZ'));
        $sRestrictionAdditionalInfo = get_post_meta(@$post->ID, 'user_additional_info', true);

        foreach ($aMemberAdditionalInfo as $sMemberAdditionalInfo)
        {
            $bForbidden = $bForbidden && stripos($sRestrictionAdditionalInfo, trim($sMemberAdditionalInfo)) === false;
        }

        if ($bForbidden)
        {
            if (intval($aOptions['referer']) === 1)
            {
                $classPCCo->set('PC_REFERER', $_SERVER['REQUEST_URI'], time() + $aOptions['autologout']);
            }

            $classPCCo->set('PC_SECURED', 1, time() + $aOptions['autologout']);

            wp_safe_redirect
            (
                add_query_arg
                (
                    'pcco[403]',
                    'forbidden',
                    wp_login_url($sRedirect)
                )
            );

            exit();
        }
    }
}
add_action('wp', 'page_404_event');


/**
 * Create message or redirect for restricted pages
 */
function page_403_forbidden($sMessage)
{
    global $sTranslation;

    if (isset($_REQUEST['pcco']['403']) && $_REQUEST['pcco']['403'] === 'forbidden')
    {
        $aOptions = get_option('pcco_options');

        if (!empty($aOptions['redirect' . strtoupper($sTranslation)]))
        {
            $sRedirect  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $sRedirect .= $_SERVER['HTTP_HOST'] . WP_BASE_DIR . $aOptions['redirect' . strtoupper($sTranslation)];

            header('HTTP/1.0 403 Forbidden');
            header('Location: ' . $sRedirect);
            exit();
        }

        $sMessage .= '
            <style>
                .login #nav,
                .login form {
                    display:none ;
                }
            </style>
        ';

        $sMessage .= sprintf('<p class="message">%s</p>', __('The page you tried to visit is restricted. Please log in or register to continue.'));
    }

    return $sMessage;
}
add_filter('login_message', 'page_403_forbidden');
