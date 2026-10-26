<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;

Route::get('/debug-users', function () {
    $users = User::all();
    $html = "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Active</th><th>Inst ID</th><th>Roles</th></tr>";
    foreach ($users as $u) {
        $roles = $u->roles->pluck('name')->implode(', ');
        $html .= "<tr><td>{$u->id}</td><td>{$u->name}</td><td>{$u->email}</td><td>{$u->username}</td><td>{$u->is_active}</td><td>{$u->institution_id}</td><td>{$roles}</td></tr>";
    }
    $html .= "</table>";
    return $html;
});
