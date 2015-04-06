<?php

function get_or_default(&$var, $default=null) {
    return isset($var) ? $var : $default;
}

?>