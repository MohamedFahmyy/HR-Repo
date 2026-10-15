<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): Response
    {
        $baseDir = Application::getInstance()->getBasePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views';
        $templatePath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $template) . '.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("View template not found: {$templatePath}");
        }

        // Global view variables
        $data['currentUser'] = Auth::getInstance()->user();
        $data['appConfig'] = [
            'name' => config('app.name'),
            'url' => config('app.url'),
            'locale' => Session::get('locale', config('app.locale', 'ar')),
        ];

        // Render template content
        extract($data, EXTR_SKIP);
        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        // If layout is specified, wrap content
        if ($layout) {
            $layoutPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $layout) . '.php';
            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout view not found: {$layoutPath}");
            }
            ob_start();
            require $layoutPath;
            $content = ob_get_clean();
        }

        return new Response($content);
    }

    public static function partial(string $partial, array $data = []): string
    {
        $baseDir = Application::getInstance()->getBasePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views';
        $partialPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $partial) . '.php';

        if (!file_exists($partialPath)) {
            return "<!-- Partial not found: {$partial} -->";
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $partialPath;
        return ob_get_clean();
    }
}
