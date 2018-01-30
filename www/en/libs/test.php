<?php
/*
 * Test library
 *
 * This library contains various test functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_libs('file');

$core->register['timers']['tests']['errors'] = array('all'     => array(),
                                                     'test'    => array(),
                                                     'library' => array());

file_ensure_path(ROOT.'data/tests/contents');

define('TESTPATH', ROOT.'data/tests/content/');



/*
 * Execute the specified test and show results
 */
function test($name, $description, $function){
    try{
        log_console($name.' [TEST] '.$description);

        if(!is_callable($function)){
            throw new bException('test(): Specified function is not a function but a "'.gettype($function).'"');
        }

        $function();

        log_console(' [ OK ]', 'green');

    }catch(Exception $e){
        log_console(' [ FAIL ]', 'red');

        $e = array('name'        => $name,
                   'description' => $description,
                   'trace'       => (method_exists($e, 'getMessages') ? $e->getMessages() : ''));

        $e['failure'] = isset_get($e['trace'][0]);

        array_shift($e['trace']);

        $core->register['timers']['tests']['errors']['all'][]     = $e;
        $core->register['timers']['tests']['errors']['test'][]    = $e;
        $core->register['timers']['tests']['errors']['library'][] = $e;

//showdie($e);
        return $e;
    }
}



/*
 * Show if the specified test completed with errors or not
 */
function test_completed($name, $type = 'test'){
    log_console($name.' ['.$type.' COMPLETED] ', 'white');

    if(!isset($core->register['timers']['tests']['errors'][$type])){
        throw new bException('test_completed(): Invalid type "" specified. Specify one of "test", "library" or "all"');
    }

    $errors = $core->register['timers']['tests']['errors'][$type];

    if(!is_array($errors)){
        throw new bException('test_completed(): The specified error list should have datatype array but has datatype "'.gettype($errors).'"');
    }

    if($errors){
        /*
         * Cleanup empty entries which means "no error"
         */
        foreach($errors as $key => $value){
            if(!$value){
                unset($errors[$key]);
            }
        }
    }

    if($errors){
        log_console(' [ FAIL ]', 'red');

    }else{
        log_console(' [ OK ]'  , 'green');
    }

    /*
     * Clear error lists
     */
    switch($type){
        case 'all':
            $core->register['timers']['tests']['errors']['all'] = array();
            // FALLTHROUGH

        case 'library':
            $core->register['timers']['tests']['errors']['library'] = array();
            // FALLTHROUGH

        case 'test':
            $core->register['timers']['tests']['errors']['test'] = array();
            // FALLTHROUGH

    }
}
?>
