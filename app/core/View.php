<?php

final class View
{
    /** Render a view inside a layout. Layout 'app' = portal shell, 'auth' = centered card, 'public', or null. */
    public static function render(string $view, array $data = [], ?string $layout = 'app'): void
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::partial('layouts/' . $layout, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = APP_PATH . '/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}
