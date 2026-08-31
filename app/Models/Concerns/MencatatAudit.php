<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * B-10: perubahan wajib meninggalkan jejak (pelaku, waktu, nilai sebelum,
 * nilai sesudah).
 *
 * Dipasang sebagai event model dan bukan dipanggil manual dari controller,
 * supaya jalur mana pun ikut tercatat — panel Filament, seeder, tinker, maupun
 * job terjadwal. Kalau bergantung pada pemanggilan manual, satu jalur yang
 * terlewat berarti lubang diam-diam di audit.
 */
trait MencatatAudit
{
    public static function bootMencatatAudit(): void
    {
        // Ketiga listener selalu didaftarkan, penyaringannya di dalam closure.
        // Menanyakan peristiwaDiaudit() di sini berarti membuat instance model
        // saat model itu sendiri sedang di-boot, dan Eloquent menolaknya.
        static::created(function (Model $model): void {
            if (! $model->mengauditPeristiwa('created')) {
                return;
            }

            $model->tulisAudit('dibuat', null, $model->nilaiDiaudit($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            if (! $model->mengauditPeristiwa('updated')) {
                return;
            }

            $sesudah = $model->nilaiDiaudit($model->getChanges());

            // Perubahan di kolom yang tidak diawasi tidak menghasilkan baris.
            if ($sesudah === []) {
                return;
            }

            $sebelum = array_intersect_key($model->getOriginal(), $sesudah);

            $model->tulisAudit('diubah', $sebelum, $sesudah);
        });

        static::deleted(function (Model $model): void {
            if (! $model->mengauditPeristiwa('deleted')) {
                return;
            }

            $model->tulisAudit('dihapus', $model->nilaiDiaudit($model->getOriginal()), null);
        });
    }

    public function mengauditPeristiwa(string $peristiwa): bool
    {
        return in_array($peristiwa, $this->peristiwaDiaudit(), true);
    }

    /**
     * Kolom yang perubahannya wajib dicatat. Model yang memakai trait ini
     * harus menimpanya.
     *
     * @return array<int, string>
     */
    abstract public function kolomDiaudit(): array;

    /**
     * @return array<int, string>
     */
    public function peristiwaDiaudit(): array
    {
        return ['updated'];
    }

    /**
     * @param  array<string, mixed>  $atribut
     * @return array<string, mixed>
     */
    public function nilaiDiaudit(array $atribut): array
    {
        return array_intersect_key($atribut, array_flip($this->kolomDiaudit()));
    }

    /**
     * @param  array<string, mixed>|null  $sebelum
     * @param  array<string, mixed>|null  $sesudah
     */
    protected function tulisAudit(string $aksi, ?array $sebelum, ?array $sesudah): void
    {
        AuditLog::create([
            'actor_id' => auth()->id(),
            'aksi' => $aksi,
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'before' => $sebelum,
            'after' => $sesudah,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
