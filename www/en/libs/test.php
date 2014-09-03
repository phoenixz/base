<?php
/*
 * Test library
 *
 * This library contains various test functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



load_libs('file');

$GLOBALS['tests']['errors'] = array('all'     => array(),
                                    'test'    => array(),
                                    'library' => array());

file_ensure_path(ROOT.'data/tests/contents');

define('TESTPATH', ROOT.'data/tests/content/');



/*
 * Execute the specified test and show results
 */
function test($name, $description, $function){
    try{
		log_console($name.' [TEST] '.$description, '', '', false);

        if(!is_callable($function)){
            throw new bException('test(): Specified function is not a function but a "'.gettype($function).'"');
        }

		$function();

		log_console(' [ OK ]', '', 'green');

    }catch(Exception $e){
		log_console(' [ FAIL ]', '', 'red');

		$e = array('name'        => $name,
                   'description' => $description,
                   'trace'       => (method_exists($e, 'getMessages') ? $e->getMessages() : ''));

		$e['failure'] = isset_get($e['trace'][0]);

        array_shift($e['trace']);

        $GLOBALS['tests']['errors']['all'][]     = $e;
        $GLOBALS['tests']['errors']['test'][]    = $e;
        $GLOBALS['tests']['errors']['library'][] = $e;

//showdie($e);
		return $e;
    }
}



/*
 * Show if the specified test completed with errors or not
 */
function test_completed($name, $type = 'test'){
	log_console($name.' ['.$type.' COMPLETED] ', '', 'white', false);

    if(!isset($GLOBALS['tests']['errors'][$type])){
        throw new bException('test_completed(): Invalid type "" specified. Specify one of "test", "library" or "all"');
    }

	$errors = $GLOBALS['tests']['errors'][$type];

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
		log_console(' [ FAIL ]', '', 'red');

	}else{
		log_console(' [ OK ]'  , '', 'green');
	}

    /*
     * Clear error lists
     */
    switch($type){
        case 'all':
            $GLOBALS['tests']['errors']['all'] = array();
            // FALLTHROUGH

        case 'library':
            $GLOBALS['tests']['errors']['library'] = array();
            // FALLTHROUGH

        case 'test':
            $GLOBALS['tests']['errors']['test'] = array();
            // FALLTHROUGH

    }
}
?>
