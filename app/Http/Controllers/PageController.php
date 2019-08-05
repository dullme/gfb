<?php


namespace App\Http\Controllers;


use App\Models\Page;

class PageController
{

    public function show($id)
    {
        $page = Page::findOrFail($id);

        return view('page', compact('page'));
    }
}
