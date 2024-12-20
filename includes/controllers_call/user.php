<?php

function controller_user($act, $d) {
    if ($act == 'add_window') return User::user_add_window();
    if ($act == 'edit_window') return User::user_edit_window($d);
    if ($act == 'delete_user') return User::delete($d);
    if ($act == 'update_user') return User::update($d);
    if ($act == 'create_user') return User::create($d);
    return '';
}
