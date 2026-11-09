<?php
if (!function_exists('harvee_i18n_normalize_language')) {
    function harvee_i18n_normalize_language($language): string
    {
        $value = strtolower(trim((string) $language));

        $aliases = [
            'en' => 'en',
            'english' => 'en',
            'fil' => 'fil',
            'filipino' => 'fil',
            'tagalog' => 'fil',
            'tl' => 'fil',
            'ceb' => 'ceb',
            'cebuano' => 'ceb',
            'bisaya' => 'ceb',
        ];

        return $aliases[$value] ?? 'en';
    }

    function harvee_i18n_current_language(): string
    {
        return harvee_i18n_normalize_language($_COOKIE['harvee_language'] ?? 'en');
    }

    function harvee_i18n_html_language(): string
    {
        $language = harvee_i18n_current_language();

        if ($language === 'fil') {
            return 'tl';
        }

        if ($language === 'ceb') {
            return 'ceb';
        }

        return 'en';
    }

    function harvee_i18n_base_path(): string
    {
        $appRoot = realpath(dirname(__DIR__));
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';

        if ($appRoot && $scriptFilename !== '') {
            $scriptDir = realpath(dirname($scriptFilename));

            if ($scriptDir) {
                $normalizedAppRoot = str_replace('\\', '/', rtrim($appRoot, '\\/'));
                $normalizedScriptDir = str_replace('\\', '/', rtrim($scriptDir, '\\/'));

                if ($normalizedScriptDir === $normalizedAppRoot) {
                    return '';
                }

                if (strpos($normalizedScriptDir . '/', $normalizedAppRoot . '/') === 0) {
                    $relativeDirectory = trim(substr($normalizedScriptDir, strlen($normalizedAppRoot)), '/');

                    if ($relativeDirectory === '') {
                        return '';
                    }

                    $segments = array_values(array_filter(explode('/', $relativeDirectory), 'strlen'));
                    return str_repeat('../', count($segments));
                }
            }
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $directory = trim(str_replace('\\', '/', dirname($scriptName)), '/.');

        if ($directory === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $directory), 'strlen'));
        if (count($segments) <= 1) {
            return '';
        }

        return str_repeat('../', count($segments) - 1);
    }

    function harvee_i18n_script_markup(): string
    {
        $config = [
            'defaultLanguage' => harvee_i18n_current_language(),
            'labels' => [
                'en' => 'English',
                'fil' => 'Filipino',
                'ceb' => 'Bisaya',
            ],
        ];

        $configJson = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $scriptSrc = htmlspecialchars(harvee_i18n_base_path() . 'assets/js/i18n.js', ENT_QUOTES, 'UTF-8');

        return "\n"
            . '<script data-harvee-i18n-loaded="1">window.HARVEE_I18N_CONFIG = ' . $configJson . ';</script>' . "\n"
            . '<script data-harvee-i18n-loaded="1" src="' . $scriptSrc . '" defer></script>' . "\n";
    }

    function harvee_i18n_head_markup(): string
    {
        $basePath = harvee_i18n_base_path();
        $iconPath = htmlspecialchars($basePath . 'assets/images/logo.png', ENT_QUOTES, 'UTF-8');

        return "\n"
            . '<link data-harvee-head="1" rel="icon" type="image/png" href="' . $iconPath . '">' . "\n"
            . '<link data-harvee-head="1" rel="shortcut icon" type="image/png" href="' . $iconPath . '">' . "\n"
            . '<link data-harvee-head="1" rel="apple-touch-icon" href="' . $iconPath . '">' . "\n";
    }

    function harvee_i18n_buffer(string $html): string
    {
        if (stripos($html, 'data-harvee-i18n-loaded="1"') !== false) {
            return $html;
        }

        if (preg_match('/<html\b[^>]*\blang=(["\']).*?\1/i', $html)) {
            $html = preg_replace(
                '/<html\b([^>]*)\blang=(["\']).*?\2/i',
                '<html$1lang="' . harvee_i18n_html_language() . '"',
                $html,
                1
            );
        }

        if (stripos($html, 'data-harvee-head="1"') === false && stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', harvee_i18n_head_markup() . '</head>', $html, 1);
        }

        $snippet = harvee_i18n_script_markup();

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $snippet . '</body>', $html, 1);
        }

        if (stripos($html, '</html>') !== false) {
            return preg_replace('/<\/html>/i', $snippet . '</html>', $html, 1);
        }

        return $html;
    }

    function harvee_i18n_bootstrap(): void
    {
        static $started = false;

        if ($started || PHP_SAPI === 'cli') {
            return;
        }

        $started = true;
        ob_start('harvee_i18n_buffer');
    }
}
