<?php
/*
 * Translator library
 *
 * This library contains all functions related to the translations of this and / or other sites
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 * Add
 */
function translator_insert(){
    try{

    }catch(Exception $e){
        throw new lsException('translator_insert(): Failed', $e);
    }
}



/*
 * Process the specified project
 */
function translator_process($project){
    try{
        if(!$project){
            /*
             * THIS project then
             */
        }

    }catch(Exception $e){
        throw new lsException('translator_process(): Failed', $e);
    }
}

?>
