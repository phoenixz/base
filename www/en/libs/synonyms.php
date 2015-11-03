<?php
/*
 * This is the synonyms library which can rewrite texts automatically using a database backend
 *
 * With few exceptions at the end of this file, all functions have the synonym_ prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Replace the specified text with the specified synonyms
 *
 * If $synonyms is an array, it will be taken as the list of
 * synonyms that must be replaced.
 *
 * If its specified as a number it will be interpreted as the
 * chance a word will be replaced.
 */
function synonym($source, $params = array()){
    try{
        array_params($params);
        array_default($params, 'synonyms', null);
        array_default($params, 'chance'  , 50);
        array_default($params, 'skip'    , null);

        if(!is_numeric($params['chance']) or ($params['chance'] < 0) or ($params['chance'] > 100)){
            throw new bException('synonym(): Invalid chance specified, please specify a numeric value in between 0 and 100', 'invalid');
        }

        if($params['skip']){
            $params['skip'] = array_force(strtolower(str_force($params['skip'])));
        }

        /*
         * Do replace from specified synonyms list
         */
        if(!empty($params['synonyms'])){
            $params['synonyms'] = array_force($params['synonyms']);

            foreach($params['synonyms'] as $list){
                $list = array_force($list);

                foreach($list as $synonym){
                    if(mt_rand(0, 100) < $params['chance']){
                        $source = preg_replace('/\b'.$synonym.'\b/imus', array_get_random($list), $source);
                    }
                }
            }

            return $source;

        }else{
            /*
             * Replace all words in the text by synonyms
             */
            $minlength = 4;
            $retval    = '';

            if(preg_match_all('/(\W*)(\w+)(\W*)/imus', $source, $matches)){
                foreach($matches[2] as $id => $match){
                    if(strlen($match) >= $minlength){
                        if(mt_rand(0, 100) < $params['chance']){
                            if(!in_array(strtolower($match), $params['skip'])){
                                $match = synonym_get($match);
                            }
                        }
                    }

                    $retval .= $matches[1][$id].$match.$matches[3][$id];
                }
            }

            return $retval;
        }

    }catch(Exception $e){
        throw new bException('synonym(): Failed', $e);
    }
}



/*
 *
 */
function synonym_get($word){
    try{
        if(!$data = sql_list('SELECT `synonyms` FROM `synonyms` WHERE `word` = :word', array(':word' => $word))){
            if(!sql_get('SELECT COUNT(`id`) AS count FROM `synonyms`', 'count')){
                throw new bException('synonym_get(): Synonyms table is empty. Please run ./scripts/base/importers/synonyms', 'empty');
            }

            return $word;
        }

        /*
         * Pick a random entry from the results list
         */
        $data = array_random_value($data);
        $data = $data['synonyms'];
        $data = explode(',', $data);

        return trim(array_random_value($data));

    }catch(Exception $e){
        throw new bException('synonym_get(): Failed', $e);
    }
}



/*
 * Return a random word
 */
function synonym_random($count = 1, $nospaces = false){
    try{
        if($nospaces){
            if(!is_string($nospaces)){
                $nospaces = '';
            }
        }

        if(!$data = sql_list('SELECT `word` FROM `synonyms` ORDER BY RAND() LIMIT '.cfi($count))){
            throw new bException('synonym_get(): Synonyms table is empty. Please run ./scripts/base/importers/synonyms', 'empty');
        }

        if($count == 1){
            if($nospaces !== false){
                return str_replace(' ', $nospaces, array_pop($data));
            }

            return array_pop($data);
        }

        if($nospaces){
            foreach($data as $key => &$value){
                $value = str_replace(' ', $nospaces, $value);
            }

            unset($value);
        }

        return $data;

    }catch(Exception $e){
        throw new bException('synonym_random(): Failed', $e);
    }
}
?>
