<?php
$actions = [];

function add_action($hook_name, $callback) {
    global $actions;
    $actions[$hook_name][] = $callback;
}

function do_action($hook_name) {
    global $actions;
    if (isset($actions[$hook_name])) {
        foreach ($actions[$hook_name] as $callback) {
            call_user_func($callback);
        }
    }
}
?>
