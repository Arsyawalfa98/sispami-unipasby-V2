# SISPAMI UNIPASBY (Sistem Penjaminan Mutu Internal Universitas PGRI Adi Buana)

Aplikasi web berbasis Laravel yang dirancang untuk mengelola siklus Sistem Penjaminan Mutu Internal (SPMI) di lingkungan Universitas PGRI Adi Buana Surabaya.

## Teknologi Utama
- Framework: Laravel
- Bahasa: PHP
- Database: MySQL / MariaDB
- Frontend: Bootstrap, jQuery, Blade Templates

## Fitur Utama
- Manajemen Jadwal Audit Mutu Internal (AMI)
- Pemenuhan Dokumen Akreditasi
- Alur Ganda untuk Upload Auditor (mendukung alur berbasis grup dan jadwal spesifik)
- Penilaian Kriteria & Monev Temuan (KTS & Tercapai)
- Laporan Status Dokumen & Kelengkapan
- Forecasting & Visualisasi Data
- Duplikasi Grup Kriteria Dokumen beserta Kebutuhannya
- Manajemen Pengguna, Peran, dan Hak Akses
- Multi-Prodi Management (mendukung user dengan multiple program studi)

## Developer Tools

### Data Consistency Checker
Command untuk memvalidasi konsistensi data antara Jadwal AMI, Kriteria Dokumen, dan Lembaga Akreditasi Detail.

```bash
# Check konsistensi data
php artisan check:data-consistency

# Check dengan saran perbaikan
php artisan check:data-consistency --fix
```

**Kapan digunakan:**
- Sebelum testing setelah input data master baru
- Troubleshooting ketika jadwal tidak muncul
- Sebelum deployment ke production

### Validation Trait
Trait untuk validasi prodi di controller:

```php
use App\Traits\ValidatesProdiRegistration;

class JadwalAmiController extends Controller
{
    use ValidatesProdiRegistration;

    public function store(Request $request)
    {
        // Validasi sebelum save
        $validation = $this->validateProdiForJadwal(
            $request->prodi,
            $request->standar_akreditasi
        );

        if (!$validation['valid']) {
            return back()->with('error', $validation['message']);
        }

        // Continue save...
    }
}
```

### Multi-Prodi Sync
Command untuk sync data prodi dari sistem mitra ke user:

```bash
# Sync semua user
php artisan user:sync-prodi --all

# Sync user tertentu
php artisan user:sync-prodi --user=username123

# Force re-sync (update existing data)
php artisan user:sync-prodi --all --force
```

**Kapan digunakan:**
- Setelah ada perubahan role KPR di sistem mitra
- User baru yang belum pernah login
- Troubleshooting prodi tidak muncul di user

**Helper Functions:**
```php
// Di controller atau blade
$kodeProdi = getActiveProdi();        // Returns: "F.1"
$namaFakultas = getActiveFakultas();  // Returns: "Fakultas Ilmu Pendidikan"
$prodiData = getActiveProdiData();    // Returns: object dengan kode & nama

// Alternative via User model
Auth::user()->active_kode_prodi
Auth::user()->active_fakultas
```

**Recent Fixes (27 Oktober 2025):**
- **Role Fakultas Fix**: Function `getActiveFakultas()` sekarang mendukung role Fakultas dengan mengambil fakultas langsung dari `$user->fakultas`, menjaga backward compatibility untuk role lainnya
- **Mitra Portal Integration**: Redirect `/` dan `/login` ke Mitra Portal dengan emergency access option untuk akses langsung saat urgent

Lihat `dokumentasi.md` untuk detail lengkap penggunaan tools.