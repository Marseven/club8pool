<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PagesController extends Controller
{
    public function rules(): Response
    {
        return Inertia::render('Public/Rules');
    }

    public function faq(): Response
    {
        return Inertia::render('Public/Faq');
    }

    public function cgu(): Response
    {
        return Inertia::render('Public/Cgu');
    }

    public function privacy(): Response
    {
        return Inertia::render('Public/Privacy');
    }
}
