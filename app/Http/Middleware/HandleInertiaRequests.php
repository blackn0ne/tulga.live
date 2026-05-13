<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $quotes = [
            [
                'message' => 'Тәртіп пен айқындық бар жерде нәтиже де тұрақты болады.',
                'author' => 'Жүйе қағидасы',
            ],
            [
                'message' => 'Күн сайынғы шағын ілгерілеу үлкен жетістікке бастайды.',
                'author' => 'Команда ұстанымы',
            ],
            [
                'message' => 'Ыңғайлы интерфейс жұмысты тездетеді, ал тазалық қателікті азайтады.',
                'author' => 'Өнім қағидасы',
            ],
        ];

        $quote = $quotes[array_rand($quotes)];

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => $quote,
            'auth' => [
                'user' => $request->user(),
            ],
        ]);
    }
}
