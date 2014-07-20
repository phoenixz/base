<?php
/*
 * Test library
 *
 * This library contains test functions to test the projects various systems
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 * Empty function
 */
function empty_function(){
    try{

    }catch(Exception $e){
        throw new lsException('empty(): Failed', $e);
    }
}
?>
