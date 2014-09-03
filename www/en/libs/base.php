<?php
/*
 * Base Projects library
 *
 * This library file contains project specific data
 *
 * Use this one for Base only!
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 *
 */
function base_header(){
    return '<header>
    <h1><a href="index.php">BASE</a></h1>
</header>';
}



/*
 *
 */
function base_footer(){
    return '<footer>
    (C) 2013, Sven Oostenbrink
</footer>';
}



/*
 *
 */
function base_main($content){
    if(!empty($_SESSION['flash'])){
showdie($_SESSION['flash']);
    }

    return '<div role="main" id="main">'.$content.'</div>';
}


?>
