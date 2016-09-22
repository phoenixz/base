<?php
try{
    /*
     * Correct $_SERVER['PHP_SELF'], sometimes seems empty
     */
    if(empty($_SERVER['PHP_SELF'])){
        if(!isset($_SERVER['_'])){
            throw new Exception('No $_SERVER[PHP_SELF] or $_SERVER[_] found', 'notfound');
        }

         $_SERVER['PHP_SELF'] =  $_SERVER['_'];
    }

    foreach($argv as $argid => $arg){
        /*
         * (Usually first) argument may contain the startup of this script, which we may ignore
         */
        if($arg == $_SERVER['PHP_SELF']){
            continue;
        }

        switch($arg){
            case '-V':
                // FALLTHROUGH
            case 'verbose':
                // FALLTHROUGH
            case '--verbose':
                // FALLTHROUGH
                $GLOBALS['quiet'] = false;
                break;

            case '-l':
                // FALLTHROUGH
            case 'signin':
                /*
                 * Set current session user
                 */
                $user     = $argv[$argid + 1];
                $password = $argv[$argid + 2];

                unset($argv[$argid]);
                unset($argv[$argid + 1]);
                unset($argv[$argid + 2]);
                break;

            case 'language':
                /*
                 * Set language to be used
                 */
                if(isset($language)){
                    throw new Exception('Environment specified twice');
                }

                if(!isset($argv[$argid + 1])){
                    throw new Exception('startup: The "language" argument requires a language right after it');

                }else{
                    $language = $argv[$argid + 1];
                }

                unset($argv[$argid]);
                unset($argv[$argid + 1]);
                break;

            case '-E':
                // FALLTHROUGH
            case '--env':
                /*
                 * Set environment and reset next
                 */
                if(isset($environment)){
                    throw new Exception('Environment specified twice');
                }

                if(!isset($argv[$argid + 1])){
                    throw new Exception('startup: The "environment" argument requires an existing environment name right after it');

                }else{
                    $environment = $argv[$argid + 1];
                }

                unset($argv[$argid]);
                unset($argv[$argid + 1]);
                break;

            default:
                /*
                 * We can ignore this parameter
                 */
                break;
        }
    }

    unset($arg);
    unset($argid);

}catch(Exception $e){
    die("startup: Command line parser failed with \"".$e->getMessage()."\"\n");
}
?>
