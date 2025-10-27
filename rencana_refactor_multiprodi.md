# Rencana Refaktorisasi: Dukungan Multi-Prodi untuk Satu Pengguna

Dokumen ini merinci rencana teknis untuk mengatasi batasan sistem SISPAMI yang hanya mendukung satu prodi per pengguna, agar selaras dengan sistem mitra yang mendukung banyak prodi.

## 1. Masalah yang Ditemukan

-   **Asumsi Sistem:** Arsitektur SISPAMI saat ini dibangun di atas asumsi bahwa **1 Pengguna = 1 Prodi**. Hal ini tercermin pada kolom `prodi` di tabel `users` yang hanya menyimpan satu nilai (string).
-   **Konflik dengan Sistem Mitra:** Sistem mitra (`gate`) memiliki struktur yang lebih fleksibel, di mana satu pengguna dapat memiliki peran di beberapa unit/prodi sekaligus (tersimpan di `gate.sc_userrole`).
-   **Dampak:** Ketika pengguna dengan multi-prodi dari sistem mitra login ke SISPAMI, hanya satu prodi yang dikenali. Akibatnya, pengguna tersebut tidak dapat melihat atau mengelola data dari semua prodi yang menjadi haknya, yang menyebabkan kebingungan dan fungsionalitas yang tidak lengkap.

### Struktur Tabel Mitra (`gate`)
Sebagai referensi, berikut adalah struktur tabel di sistem mitra yang menjadi sumber data multi-prodi. Tabel `sc_userrole` adalah kunci yang menghubungkan pengguna, peran, dan unit/prodi.

```sql
-- gate.sc_user definition
CREATE TABLE gate.sc_user (
    userid serial4 NOT NULL,
    username varchar(100) NULL,
    userdesc varchar(100) NULL,
    "password" varchar(32) NULL,
    email varchar(100) NULL,
    isactive numeric(1) NULL,
    idpegawai int4 NULL,
    -- ... kolom lain
    CONSTRAINT pk_sc_user PRIMARY KEY (userid)
);

-- gate.sc_role definition
CREATE TABLE gate.sc_role (
    koderole varchar(25) NOT NULL,
    namarole varchar(50) NULL,
    -- ... kolom lain
    CONSTRAINT pk_sc_role PRIMARY KEY (koderole)
);

-- gate.sc_userrole definition
CREATE TABLE gate.sc_userrole (
    kodeunit varchar(10) NOT NULL, -- Merepresentasikan Prodi
    koderole varchar(25) NOT NULL, -- Merepresentasikan Role
    userid int4 NOT NULL,          -- Merepresentasikan User
    -- ... kolom lain
    CONSTRAINT pk_sc_userrole PRIMARY KEY (kodeunit, koderole, userid),
    CONSTRAINT fk_role_user FOREIGN KEY (koderole) REFERENCES gate.sc_role(koderole),
    CONSTRAINT fk_unit_role FOREIGN KEY (kodeunit) REFERENCES gate.ms_unit(kodeunit),
    CONSTRAINT fk_user_role FOREIGN KEY (userid) REFERENCES gate.sc_user(userid)
);
```

## 2. Solusi yang Diusulkan

Solusi yang akan diimplementasikan adalah dengan memanfaatkan mekanisme **"Switch Role"** yang sudah ada di UI untuk menciptakan "konteks prodi" yang bisa dipilih oleh pengguna.

-   **Pengalaman Pengguna (UX):**
    -   Seorang pengguna dengan role "Admin Prodi" yang mengelola prodi "PENJAS" dan "PPKN" akan melihat dua pilihan di menu dropdown profilnya:
        -   `Admin Prodi (PENJAS)`
        -   `Admin Prodi (PPKN)`
    -   Ketika pengguna memilih salah satu opsi (misal: `Admin Prodi (PENJAS)`), seluruh sesi aplikasi akan berjalan dalam konteks prodi PENJAS. Semua data yang ditampilkan akan terfilter untuk prodi tersebut.
    -   Pengguna dapat dengan mudah beralih konteks ke prodi lain melalui menu yang sama tanpa perlu login ulang.
-   **Struktur Data:** Di database SISPAMI, pengguna tersebut tetap hanya memiliki **satu role** "Admin Prodi". Daftar prodinya akan disimpan dalam satu kolom sebagai data terstruktur.

## 3. Rencana Implementasi Teknis

Berikut adalah langkah-langkah teknis yang akan diambil, beserta file yang perlu diperiksa dan dimodifikasi.

### Langkah 1: Modifikasi Struktur Database
-   **Tindakan:** Membuat *migration* baru untuk mengubah tipe data kolom `prodi` pada tabel `users` dari `string` menjadi `JSON`. Ini memungkinkan penyimpanan multi-prodi dalam bentuk array.
-   **File yang akan Dibuat:** `database/migrations/[timestamp]_modify_prodi_column_in_users_table.php`.
-   **Konsep untuk Dipelajari:** Laravel Migrations (Schema Builder `change()`).

### Langkah 2: Penyesuaian Logika Login
-   **Tindakan:** Mengubah `LoginController` agar saat mengambil data dari sistem mitra, ia mengambil **semua** prodi yang terkait dengan pengguna dan menyimpannya sebagai array ke dalam kolom `users.prodi` yang baru.
-   **File untuk Dicek:**
    -   `app/Http/Controllers/Auth/LoginController.php`
    -   `app/Models/Siakad.php` (atau model lain yang berinteraksi dengan database `gate`).

### Langkah 3: Membuat "Virtual Roles"
-   **Tindakan:** Membuat sebuah **Accessor** pada model `User` dengan nama `getVirtualRolesAttribute`. Accessor ini akan secara dinamis menghasilkan daftar "Role Semu" (contoh: "Admin Prodi (PENJAS)") berdasarkan kombinasi role asli pengguna dan daftar prodi yang tersimpan di kolom `prodi` (JSON).
-   **File untuk Dicek:** `app/Models/User.php`.
-   **Konsep untuk Dipelajari:** Eloquent Accessors & Mutators.

### Langkah 4: Penyesuaian UI Dropdown
-   **Tindakan:** Mengubah kode pada layout utama yang menampilkan dropdown "Switch Role" agar menggunakan data dari accessor `virtual_roles` yang baru, bukan dari relasi `roles` yang lama.
-   **File untuk Dicek:** `resources/views/layouts/admin.blade.php`.

### Langkah 5: Penyesuaian Logika "Switch Role"
-   **Tindakan:** Memodifikasi `UserRoleController` agar dapat menerima dan mem-parsing "role semu" (contoh: "Admin Prodi (PENJAS)"). Controller ini akan memisahkan nama role asli ('Admin Prodi') dan nama prodi ('PENJAS'), lalu menyimpannya ke dalam dua session terpisah: `session(['active_role' => '...'])` dan `session(['active_prodi' => '...'])`.
-   **File untuk Dicek:** `app/Http/Controllers/UserRoleController.php`.

### Langkah 6: Penyesuaian Logika Aplikasi Inti
-   **Tindakan:** Mengubah semua logika di dalam Controller, Service, dan Repository yang sebelumnya bergantung pada `Auth::user()->prodi` menjadi bergantung pada `session('active_prodi')`.
-   **File untuk Dicek:**
    -   Berbagai Controller di `app/Http/Controllers/` (contoh: `PemenuhanDokumenController`, `MonevController`, `JadwalAmiController`, dll).
    -   Berbagai Service di `app/Services/` dan Repository di `app/Repositories/`.
    -   **Strategi:** Melakukan pencarian global (global search) di seluruh proyek untuk kata kunci `Auth::user()->prodi` untuk menemukan semua file yang perlu diubah.
