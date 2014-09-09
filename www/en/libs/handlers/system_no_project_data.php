<?php
if(!FORCE){
    throw new bException(tr('startup: Project data in "ROOT/config/project.php" has not been configured. Please ensure SEED has a value specified and PROJECTCODEVERSION is not "0.0.0"'), 'projectnotsetup');
}
?>
