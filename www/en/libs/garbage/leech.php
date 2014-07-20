<?php
/*
 * Leech library
 *
 * This library contains standard leech functions
 *
 * Written and Copyright by Sven Oostenbrink
 */


function leech_site($website_domain, $domain_range = '', $recurse_level = 5, $limit_rate = 500, $user_agent='Mozilla/5.0'){
    try{
        if(!$website_domain or !is_string($website_domain) or (strlen($website_domain) < 4)){
            throw new lsException('leech_site(): No or invalid website_domain specified');
        }

        /*
         * Validate resurse level
         */
        if((!is_numeric($recurse_level) and ($recurse_level != 'inf')) or ($recurse_level < 0)){
            throw new lsException('leech_site(): Invalid recurse_level specified');
        }

        $recurse_level = (integer) $recurse_level;

        /*
         * Validate resurse level
         */
        if(!is_numeric($limit_rate) or ($limit_rate < 0)){
            throw new lsException('leech_site(): Invalid limit_rate specified');
        }

        $limit_rate = (integer) $limit_rate;

        if($domain_range){
            /*
             * Make sure the website domain is part of the domain range
             */
            if(!is_array($domain_range)){
                $domain_range = str_explode(',', $domain_range);
            }

            if(!in_array($website_domain, $domain_range)){
                $domain_range[] = $website_domain;
            }

            $domain_range = implode(',', $domain_range);
        }

        shell_exec('nohup wget -k '.($limit_rate ? '--limit-rate='.$limit_rate.'k ' : '').'--progress=dot -t5 --no-cache '.($user_agent ? '-U "'.$user_agent.'" ' : '').($domain_range ? '-H -D '.$domain_range.' ' : '').''.($recurse_level ? '-r --level='.$recurse_level.' ' : '').'-p '.$website_domain.' &');

    }catch(Exception $e){
        throw new lsException('leech_site(): Failed', $e);
    }
}
?>
