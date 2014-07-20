<?php
/*
 * wordai library
 *
 * This library contains driver functions for the wordai service
 * See http://wordai.com and http://wordai.com/users/api.php
 *
 * wordai basic example taken from http://wordai.com/users/api.php and
 * rewritten by Sven Oostenbrink for use in base projects
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Send the specified text to the wordai API, and return the results
 *
 * From documentation on http://wordai.com/users/api.php:
 *
 * s        (Required)   - The text that you would like WordAi to spin.
 * quality  (Required)   - A numeric value between 0 and 100. The lower the number, the more unique, and the higher the number, the more readable.
 * email    (Required)   - Your login email. Used to authenticate.
 * password (Required)   - Your password. You must either use this OR hash (see below)
 * hash     (NOT NEEDED) - md5(substr(md5("pass"),0,15)); is the algorithm to calculate your hash. It is a more secure way to send your password if you don't want to use your password. THIS IS USED AUTOMATICALLY!
 * output                - Set to "json" if you want json output. Otherwise do not set and you will get plaintext.
 * nonested              - Set to "on" to turn off nested spinning (will help readability but hurt uniqueness).
 * sentence              - Set to "on" if you want paragraph editing, where WordAi will add, remove, or switch around the order of sentences in a paragraph (recommended!)
 * paragraph             - Set to "on" if you want WordAi to do paragraph spinning - perfect for if you plan on using the same spintax many times
 * returnspin            - Set to "true" if you want to just receive a spun version of the article you provided. Otherwise it will return spintax.
 * nooriginal            - Set to "on" if you do not want to include the original word in spintax (if synonyms are found). This is the same thing as creating a "Super Unique" spin.
 * protected             - Comma separated protected words (do not put spaces inbetween the words)
 * synonyms              - Add your own synonyms (Syntax: word1|synonym1,word two|first synonym 2|2nd syn). (comma separate the synonym sets and | separate the individuals synonyms)
 */
function wordai($params, $email, $password, $quality = 50, $json = true) {
    global $_CONFIG;

    try{
        load_config('wordai');

        array_params($params, 'text');
        array_default($params, 'quality'  , $quality);
        array_default($params, 'email'    , not_empty($email   , $_CONFIG['wordai']['email']));
        array_default($params, 'password' , not_empty($password, $_CONFIG['wordai']['password']));
        array_default($params, 'json'     , $json);
        array_default($params, 'sentence' , true);
        array_default($params, 'paragraph', true);

        if(empty($params['text'])){
            throw new lsException('wordai(): No text specified');
        }

        if(!is_numeric($params['quality']) or ($params['quality'] < 0) or ($params['quality'] > 100)){
            throw new lsException('wordai(): Invalid quality specified, ensure it is a number between 0 and 100');
        }

        $params['quality'] = floor($params['quality']);

        if(empty($params['email'])){
            throw new lsException('wordai(): No email specified');
        }

        if(empty($params['password'])){
            throw new lsException('wordai(): No password specified');
        }

        load_libs('curl');

        /*
         * Main, required options
         */
        $post = array('text'   => urlencode($text),
                      'email'  => $params['email'],
                      'hash'   => md5(substr(md5($params['password']), 0, 15)), // Who the fuck thought this up?
                      'output' => ($params['json'] ? 'json' : ''));

        /*
         * Other options
         */
        if(isset_get($params['json'])){
            load_libs('json');
        }

        if(isset_get($params['nonested'])){
            $post['nonested'] = 'on';
        }

        if(isset_get($params['sentence'])){
            $post['sentence'] = 'on';
        }

        if(isset_get($params['paragraph'])){
            $post['paragraph'] = 'on';
        }

        if(isset_get($params['paragraph'])){
            $post['paragraph'] = 'on';
        }

        if(isset_get($params['returnspin'])){
            $post['returnspin'] = 'true';
        }

        if(isset_get($params['nooriginal'])){
            $post['nooriginal'] = 'on';
        }

        if(isset_get($params['protected'])){
            $post['protected'] = str_force($params['protected'], ',');
        }

        if(isset_get($params['synonyms'])){
// :TEST: Not 100% sure if this will produce the correct syntax, test first!
throw new lsException('wordai(): Test the "synonymns" option befure using it, since the formatter for this option has not been tested yet!');

            foreach($params['synonyms'] as $word => $synonyms){
                $post['synonyms'][] = $word.'|'.str_force($post['synonyms'], ',');
            }

            $post['synonyms'] = str_force($post['synonyms'], '|');
        }

        /*
         * Get and return the results!
         */
        $curl = curl_get(array('url'  => 'http://wordai.com/users/regular-api.php',
                               'post' => $post));

        $curl['data'] = json_decode_custom($curl['data']);

        if(strtolower(trim($curl['data']['status'])) != 'success'){
            throw new lsException('wordai(): wordai API returned status "'.str_log($curl['data']['status']).'" with error "'.str_log(isset_get($curl['data']['error'])).'"', 'failed', $curl['data']);
        }

        return $curl['data'];

    }catch(Exception $e){
        throw new lsException('wordai(): Failed', $e);
    }
}
?>
