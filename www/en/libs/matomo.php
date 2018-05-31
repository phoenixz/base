<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Empty function
 */
function matomo_get_code($sites_id){
    try{
        if(!$sites_id){
            throw new bException(tr('matomo_get_code(): No sites_id specified'), 'not-specified');
        }

        if(!is_natural($sites_id)){
            throw new bException(tr('matomo_get_code(): Invalid sites_id ":sites_id" specified', array(':sites_id' => $sites_id)), 'not-specified');
        }

        return '    <!-- Matomo -->
                    <script type="text/javascript">
                      var _paq = _paq || [];
                      /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
                      _paq.push(["trackPageView"]);
                      _paq.push(["enableLinkTracking"]);
                      (function() {
                        var u="//analytics.capmega.com/";
                        _paq.push(["setTrackerUrl", u+"piwik.php"]);
                        _paq.push(["setSiteId", "'.$sites_id.'"]);
                        var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0];
                        g.type="text/javascript"; g.async=true; g.defer=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
                      })();
                    </script>
                    <noscript><p><img src="//analytics.capmega.com/piwik.php?idsite=6&amp;rec=1" style="border:0;" alt="" /></p></noscript>
                    <!-- End Matomo Code -->';

    }catch(Exception $e){
        throw new bException('matomo_get_code(): Failed', $e);
    }
}
?>
