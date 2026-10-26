<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-ortu', function() {
    $users = \App\Models\User::whereHas('roles', function($q) {
        $q->where('name', 'orang_tua');
    })->get();
    
    $html = "<h1>Orang Tua Accounts</h1><table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Guardian ID</th><th>Student ID</th></tr>";
    
    foreach ($users as $u) {
        $html .= "<tr><td>{$u->id}</td><td>{$u->name}</td><td>{$u->email}</td><td>{$u->guardian_id}</td><td>{$u->student_id}</td></tr>";
    }
    
    $html .= "</table>";
    
    $guardians = \App\Models\Guardian::all();
    $html .= "<h1>Guardians Table</h1><table border='1'><tr><th>ID</th><th>Name</th></tr>";
    foreach ($guardians as $g) {
        $html .= "<tr><td>{$g->id}</td><td>{$g->name}</td></tr>";
    }
    $html .= "</table>";
    
    $rels = \Illuminate\Support\Facades\DB::table('student_guardians')->get();
    $html .= "<h1>Student Guardians Pivot</h1><table border='1'><tr><th>Student ID</th><th>Guardian ID</th></tr>";
    foreach ($rels as $r) {
        $html .= "<tr><td>{$r->student_id}</td><td>{$r->guardian_id}</td></tr>";
    }
    $html .= "</table>";
    
    return $html;
});
