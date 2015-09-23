<?php
/*
 *
 *
 *
 *
 */



/*
 * It must be customized in www.addthis.com/dashboard
 */
function share_addthis_buttons() {
    try{
        html_load_js('//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-55e77d50dd5d574b');
        return '<div class="addthis_sharing_toolbox"></div>';

    } catch(Exception $e) {
        throw new bException('share_addthis_buttons(): Failed', $e);
    }
}



/*
 *
 */
function share_sharethis_buttons($buttons) {
    try{
        $html = '';

        if(empty($buttons)) {
            throw new bException('share_sharethis_buttons(): No buttons specified');
        }

        foreach (array_force($buttons) as $button) {
            switch($button) {
                case 'facebook':
                    $html .= '<span class="st_facebook_large" displayText="Facebook"></span>';
                    break;

                case 'facebook_like':                           // FALLTHROUGH
                case 'facebook_vlike':                          // FALLTHROUGH
                case 'facebook_like_vertical':
                    $html .= '<span class="st_fblike_vcount" ></span>';
                    break;

                case 'facebook_hlike':                          // FALLTHROUGH
                case 'facebook_like_horizontal':
                    $html .= '<span class="st_fblike_hcount" ></span>';
                    break;

                case 'twitter':
                    $html .= '<span class="st_twitter_large" displayText="Tweet"></span>';
                    break;

                case 'googleplus':
                    $html .= '<span class="st_googleplus_large" displayText="Google +"></span>';
                    break;

                case 'googleplus_+1':
                    $html .= '<span class="st_plusone_button"></span>';
                    break;

                case 'googleplus_s+1':                          // FALLTHROUGH
                case 'googleplus_+1_small':
                    $html .= '<span class="st_plusone"></span>';
                    break;

                case 'googleplus_l+1':                          // FALLTHROUGH
                case 'googleplus_+1_large':
                    $html .= '<span class="st_plusone_large"></span>';
                    break;

                case 'googleplus_h+1':                          // FALLTHROUGH
                case 'googleplus_+1_hcount':
                    $html .= '<span class="st_plusone_hcount"></span>';
                    break;

                case 'googleplus_v+1':                          // FALLTHROUGH
                case 'googleplus_+1_vcount':
                    $html .= '<span class="st_plusone_vcount"></span>';
                    break;

                case 'reddit':
                    $html .= '<span class="st_reddit_large" displayText="Reddit"></span>';
                    break;

                case 'linkedin':
                    $html .= '<span class="st_linkedin_large" displayText="LinkedIn"></span>';
                    break;

                case 'email':
                    $html .= '<span class="st_email_large" displayText="Email"></span>';
                    break;

                case 'all':
                    $html .= share_sharethis_buttons('facebook,twitter,googleplus,reddit,linkedin,email,facebook_like,googleplus_v+1');
                    break;

                default:
                    throw new bException(tr('share_sharethis_buttons(): Unknown type "%type%" specified', array('%type%' => $type)), 'unknown');
            }
        }

        return $html;

    } catch(Exception $e) {
        throw new bException('share_sharethis_buttons(): Failed', $e);
    }
}



/*
 *
 */
function share_sharethis_js() {
    return '<script type="text/javascript">var switchTo5x=true;</script>
            <script type="text/javascript" src="https://ws.sharethis.com/button/buttons.js"></script>
            <script type="text/javascript">stLight.options({publisher: "61be857e-d1f6-4684-9f41-155e6f3352a4", doNotHash: false, doNotCopy: false, hashAddressBar: false});</script>';
}



/*
 *
 */
function share_addtoany_buttons($buttons) {
    try{
        html_load_js('//static.addtoany.com/menu/page.js');

        $html = '<div class="a2a_kit a2a_kit_size_32 a2a_default_style">';

        if(empty($buttons)) {
            throw new bException('share_addtoany_buttons(): No buttons specified');
        }

        foreach(array_force($buttons) as $button) {
            switch($button) {
                case 'facebook':
                    $html.=  '<a class="a2a_button_facebook"></a>';
                    break;

                case 'twitter':
                    $html .= '<a class="a2a_button_twitter"></a>';
                    break;

                case 'googleplus':
                    $html_load_jsl .= '<a class="a2a_button_google_plus"></a>';
                    break;

                case 'reddit':
                    $html .= '<a class="a2a_button_linkedin"></a>';
                    break;

                case 'linkedin':
                    $html .= '<a class="a2a_button_linkedin"></a>';
                    break;

                case 'email':
                    $html .= '<a class="a2a_button_email"></a>';
                    break;

                case 'all':
                    return share_addtoany_buttons('facebook,twitter,googleplus,reddit,linkedin,email');
                default:
                    throw new bException(tr('share_addtoany_buttons(): Unknown button "%button%" specified', array('%button%' => $button)), 'unknown');
            }
        }

        $html .= '</div>';

        return $html;

    } catch(Exception $e) {
        throw new bException('share_addtoany_buttons(): Failed', $e);
    }
}
?>
