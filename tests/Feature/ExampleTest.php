<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Rute / tidak lagi merender halaman: sampai fitur 07 dan 11 dikerjakan ia
     * hanya mengarahkan orang ke tempat yang masuk akal. Perilaku lengkapnya
     * diuji di tests/Feature/Panel/PintuPanelTest.php.
     */
    public function test_beranda_mengarahkan_tamu_ke_halaman_masuk(): void
    {
        $this->get('/')->assertRedirect(route('masuk'));
    }
}
