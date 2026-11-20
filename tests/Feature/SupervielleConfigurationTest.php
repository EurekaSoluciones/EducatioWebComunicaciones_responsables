<?php

namespace Tests\Feature;

use Tests\TestCase;

class SupervielleConfigurationTest extends TestCase
{
    public function test_test_login_configuration_preserves_exact_strings_and_empty_lists(): void
    {
        $keys = ['EURE_SUPERVIELLE_TEST_MODE', 'EURE_SUPERVIELLE_TEST_LOGINS'];
        $before = [];
        foreach ($keys as $key) {
            $before[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
        }
        try {
            foreach ([
                ['true', ' 29648818, ,029648818, otro ', true, ['29648818', '029648818', 'otro']],
                ['true', ' , ', true, []],
                ['false', '29648818', false, ['29648818']],
                [null, null, false, []],
            ] as [$mode, $logins, $expectedMode, $expectedLogins]) {
                foreach (array_combine($keys, [$mode, $logins]) as $key => $value) {
                    if ($value === null) {
                        unset($_ENV[$key], $_SERVER[$key]);
                        putenv($key);
                    } else {
                        $_ENV[$key] = $_SERVER[$key] = $value;
                        putenv($key.'='.$value);
                    }
                }
                $settings = require config_path('supervielle.php');
                $this->assertSame($expectedMode, $settings['test_mode']);
                $this->assertSame($expectedLogins, $settings['test_logins']);
            }
        } finally {
            foreach ($before as $key => [$env, $server, $process]) {
                if ($env === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $env;
                }
                if ($server === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $server;
                }
                putenv($process === false ? $key : $key.'='.$process);
            }
        }
    }

    public function test_only_s_enables_payments_and_legacy_flag_cannot_enable_them(): void
    {
        $keys = ['EURE_SUPERVIELLE', 'EURE_SUPERVIELLE_ENABLED'];
        $before = [];
        foreach ($keys as $key) {
            $before[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
        }
        try {
            $_ENV['EURE_SUPERVIELLE_ENABLED'] = $_SERVER['EURE_SUPERVIELLE_ENABLED'] = 'true';
            putenv('EURE_SUPERVIELLE_ENABLED=true');
            foreach ([null, '', 'N', 'n', 'false', 'true', 'invalid', 'S', 's', ' S '] as $value) {
                if ($value === null) {
                    unset($_ENV['EURE_SUPERVIELLE'], $_SERVER['EURE_SUPERVIELLE']);
                    putenv('EURE_SUPERVIELLE');
                } else {
                    $_ENV['EURE_SUPERVIELLE'] = $_SERVER['EURE_SUPERVIELLE'] = $value;
                    putenv('EURE_SUPERVIELLE='.$value);
                }
                $settings = require config_path('supervielle.php');
                $this->assertSame(in_array($value, ['S', 's', ' S '], true), $settings['enabled']);
            }
        } finally {
            foreach ($before as $key => [$env, $server, $process]) {
                if ($env === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $env;
                }
                if ($server === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $server;
                }
                putenv($process === false ? $key : $key.'='.$process);
            }
        }
    }
}
