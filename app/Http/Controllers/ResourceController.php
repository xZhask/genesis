<?php

namespace App\Http\Controllers;

use App\Enums\ResourceType;
use App\Models\Resource;
use Illuminate\View\View;

class ResourceController extends Controller
{
    private const CIRCULARS_ON_PAGE = 6;

    public function index(): View
    {
        $circulars = Resource::published()->publicWeb()->ofType(ResourceType::Circular)
            ->latest('published_on')->latest('id')
            ->limit(self::CIRCULARS_ON_PAGE + 1)
            ->get();

        // La lista más reciente de cada grado
        $supplies = Resource::published()->ofType(ResourceType::Supplies)
            ->orderByDesc('school_year')->latest('published_on')->latest('id')
            ->get()
            ->unique('grade')
            ->keyBy('grade');

        $ordered = fn (ResourceType $type) => Resource::published()->ofType($type)->orderBy('position')->orderBy('id')->get();

        return view('resources.index', [
            'circulars' => $circulars->take(self::CIRCULARS_ON_PAGE),
            'moreCirculars' => $circulars->count() > self::CIRCULARS_ON_PAGE,
            'supplies' => $supplies,
            'uniforms' => $ordered(ResourceType::Uniform),
            'schedules' => $ordered(ResourceType::Schedule),
            'levels' => config('school.levels'),
        ]);
    }

    public function circulars(): View
    {
        return view('resources.circulars', [
            'circulars' => Resource::published()->publicWeb()->ofType(ResourceType::Circular)
                ->latest('published_on')->latest('id')
                ->paginate(15),
        ]);
    }
}
