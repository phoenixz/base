<?php
/*
 * Audio Library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */

load_config('audio');

/*
 * Play an audio
 */
function audio_play($class = null){
    global $_CONFIG;

    try{
        /*
         * Check if there is no given class
         */
        if(!$class){
            $class = $_CONFIG['audio']['default'];
        }

        /*
         * Check if given class is in CONFIG[audio]
         */
        if(empty($_CONFIG['audio']['classes'][$class])){
            throw new bException(tr('audio_play(): This audio class does not exist "%class%"', array('%class%' => str_log($class))), 'audio');
        }

        $file = ROOT.'/data/audio/'.$_CONFIG['audio']['classes'][$class];

        /*
         * Check if audio file exists
         */
        if(!file_exists($file)){
            throw new bException(tr('audio_play(): This audio file does not exist "%file%"', array('%file%' => str_log($file))), 'audio');
        }

        /*
         * Detect if the audio is gonna be played local or remote
         */
        if(!getenv('SSH_CLIENT')){
            /*
             * Play the audio local
             */
            try{
                safe_exec($_CONFIG['audio']['command'].' '.$file);

            }catch(Exception $e){
                throw new bException(tr('audio_play(): Can not play audio file "%file%", commando "%command%" returned error: "%error%"', array('%file%' => str_log($file), '%command%' => str_log($_CONFIG['audio']['command']), '%error%' => str_log($e))), 'audio');
            }

        }else{
// :INVESTIGATE: To do later how to do this easy without losing connection
//            /*
//             * Play the audio on remote server
//             */
///*
// *  Need 3 things for connecting again:
// *  PORT
// *  IP (Server Name)
// *  Log user name
// */
//            $log_user_name = getenv('LOGNAME');
//            $port = str_rfrom('', '')
//
//            safe_exec('exit', $exitcode);
//
//            passthru('ssh -p port -t -R 24713:localhost:4713 user_name@server_name', $exitcode);
//
//            safe_exec('export PULSE_SERVER="tcp:localhost:24713"', $exitcode);
//
//            safe_exec($_CONFIG['audio']['command'].' '.$file, $exitcode);

        }


    }catch(Exception $e){
        throw new bException('audio_play(): Failed', $e);
    }
}

?>
