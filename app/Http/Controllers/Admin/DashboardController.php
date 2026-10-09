<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Instruktur;
use App\Models\Kegiatan;
use App\Models\Partner;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'summaries' => [
                ['label' => 'Skema Sertifikasi', 'module' => 'kegiatan', 'total' => Kegiatan::count(), 'active' => Kegiatan::active()->count()],
                ['label' => 'Struktur Organisasi', 'module' => 'anggota', 'total' => Anggota::count(), 'active' => Anggota::active()->count()],
                ['label' => 'Instruktur & Asesor', 'module' => 'instruktur', 'total' => Instruktur::count(), 'active' => Instruktur::active()->count()],
                ['label' => 'Mitra', 'module' => 'mitra', 'total' => Partner::count(), 'active' => Partner::active()->count()],
            ],
        ]);
    }
}
