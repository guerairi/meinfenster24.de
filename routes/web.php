<?php

use Illuminate\Support\Facades\Route;

Route::get('/bewertungen', function () {
    return view('reviews.index');
});

