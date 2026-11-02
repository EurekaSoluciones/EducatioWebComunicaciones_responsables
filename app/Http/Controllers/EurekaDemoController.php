<?php

namespace App\Http\Controllers;

use App\Support\EurekaTheme;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class EurekaDemoController extends Controller
{
    public function __invoke(EurekaTheme $themes): Response
    {
        return $this->renderDemo($themes, 'sidebar');
    }

    public function alternative(EurekaTheme $themes): Response
    {
        return $this->renderDemo($themes, 'pranzo');
    }

    private function renderDemo(EurekaTheme $themes, string $layout): Response
    {
        try {
            $theme = $themes->current();
        } catch (RuntimeException $exception) {
            abort(503, $exception->getMessage());
        }

        return Inertia::render('demo/Ui', [
            'theme' => $theme,
            'institution' => config('eureka.institution'),
            'homeUrl' => route('home'),
            'demoLayout' => $layout,
            'demoUrls' => [
                'sidebar' => route('demo.eureka'),
                'pranzo' => route('demo.eureka.alternative'),
            ],
        ]);
    }
}
