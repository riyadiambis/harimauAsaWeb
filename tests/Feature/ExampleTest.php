<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Beranda PUBLIK — terbuka tanpa akun. Perilaku lengkapnya diuji di
     * tests/Feature/BerandaTest.php.
     */
    public function test_beranda_terbuka_untuk_tamu(): void
    {
        $this->get('/')->assertSuccessful();
    }
}
