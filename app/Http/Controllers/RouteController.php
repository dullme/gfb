<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RouteController extends Controller
{

    public function index() {

        return view('welcome');
    }

    public function download() {

        return redirect(config('app_download'));
    }

    public function delete() {
        return app('db')->table('oauth_access_tokens')
            ->where('revoked', true)
            ->delete();
    }
}
