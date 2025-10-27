<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Siakad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function searchProdi(Request $request)
    {
        $search = $request->search;

        $prodis = Siakad::searchProdi($search);

        $formattedProdi = collect($prodis)->map(function ($prodi) {
            return [
                'id' => $prodi->kodeunit,
                'text' => $prodi->kodeunit . ' - ' . $prodi->namaunit,
                'fakultas' => [
                    'id' => $prodi->fakultas_kode,
                    'text' => $prodi->fakultas_kode . ' - ' . $prodi->fakultas_nama
                ]
            ];
        });

        return response()->json($formattedProdi);
    }

    public function index(Request $request)
    {
        // Query dasar dengan relasi roles
        $query = User::with('roles');

        // Filter berdasarkan role
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter berdasarkan pencarian
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('username', 'like', "%{$request->search}%");
            });
        }

        // Ambil data
        $users = $query->paginate(10);

        // Ambil data prodi untuk mapping
        $prodiList = collect(Siakad::getProdi())->keyBy('kodeunit');
        // Ambil daftar role untuk dropdown
        $roles = Role::pluck('name', 'name');

        return view('users.index', compact('users', 'roles', 'prodiList'));
    }
    public function show(User $user)
    {
        // Load relasi yang diperlukan
        $user->load(['roles.permissions']);

        return view('users.show', compact('user'));
    }
    public function create()
    {
        // Ambil data prodi dengan fakultasnya
        $prodis = Siakad::getProdi();
        $roles = Role::all();
        return view('users.create', compact('roles', 'prodis'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'email' => 'required|string|max:255|unique:users',
                'roles' => 'required|array',
                // 'jabatan' => 'nullable|string|max:255',
                'prodi' => 'required|string', // Validasi prodi
                'fakultas' => 'required|string', // Validasi fakultas
                'is_active' => 'boolean'
            ]);
            // Dapatkan data prodi dan fakultas lengkap
            $prodi = collect(Siakad::searchProdi($request->prodi))->first();
            if (!$prodi) {
                throw new \Exception('Program Studi tidak valid');
            }

            // Format data prodi dan fakultas dengan kode dan nama
            $prodiFormatted = $prodi->kodeunit . ' - ' . $prodi->namaunit;
            $fakultasFormatted = $prodi->fakultas_kode . ' - ' . $prodi->fakultas_nama;

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'last_name' => $request->name,
                'password' => $request->password,
                'email' => $request->email,
                // 'jabatan' => $request->jabatan,
                'prodi' => $prodiFormatted,
                'fakultas' => $fakultasFormatted,
                'is_active' => $request->has('is_active')
            ]);

            $user->roles()->sync($request->roles);

            ActivityLogService::log(
                'created',
                'users',
                'User baru dibuat: ' . $user->name,
                $user,
                null,
                $user->fresh()->toArray() // Menggunakan fresh() untuk mendapatkan data terbaru
            );

            return redirect()->route('users.index')->with('success', 'User Berhasil Di Buat');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'User Gagal Di Buat: ' . $e->getMessage());
        }
    }

    public function edit(User $user)
    {
        $prodis = Siakad::getProdi();
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles', 'prodis'));
    }

    public function update(Request $request, User $user)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => $request->password ? 'required|string|min:8|confirmed' : '',
                'roles' => 'required|array',
                'jabatan' => 'nullable|string|max:255',
                'is_active' => 'boolean'
            ]);

            $userData = [
                'name' => $request->name,
                'username' => $request->username,
                'jabatan' => $request->jabatan,
                'is_active' => $request->has('is_active')
            ];

            if ($request->filled('password')) {
                $userData['password'] = $request->password;
            }
            // Dapatkan data prodi dan fakultas lengkap
            $prodi = collect(Siakad::searchProdi($request->prodi))->first();
            if ($prodi) {
                $userData['prodi'] = $prodi->kodeunit . ' - ' . $prodi->namaunit;
                $userData['fakultas'] = $prodi->fakultas_kode . ' - ' . $prodi->fakultas_nama;
            }

            $oldData = $user->toArray(); // Simpan data lama sebelum update

            $user->update($userData);
            $user->roles()->sync($request->roles);

            ActivityLogService::log(
                'updated',
                'users',
                'User diupdate: ' . $user->name,
                $user,
                $oldData,
                $user->fresh()->toArray()
            );

            return redirect()->route('users.index')->with('success', 'User Berhasil Di Update');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'User Gagal Update: ' . $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        try {
            $userData = $user->toArray(); // Simpan data sebelum dihapus
            $user->roles()->detach();
            $user->delete();
            ActivityLogService::log(
                'deleted',
                'users',
                'User dihapus: ' . $user->name,
                $user,
                $userData,
                null
            );

            return redirect()->route('users.index')->with('success', 'User Berhasil Di Hapus');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'User Gagal Di Hapus: ' . $e->getMessage());
        }
    }

    public function getFakultas(Request $request)
    {
        $fakultas = Siakad::getFakultasByProdi($request->kode_unit);

        if (!empty($fakultas)) {
            return response()->json([
                'success' => true,
                'fakultas' => $fakultas[0]
            ]);
        }

        return response()->json(['success' => false]);
    }

    // Menambahkan method ini di UserController.php

    public function searchUsername(Request $request)
    {
        $search = $request->search;

        // Gunakan model Siakad untuk mencari username
        $users = Siakad::searchUsername($search);

        $formattedUsers = collect($users)->map(function ($user) {
            return [
                'id' => $user->username,
                'text' => $user->username . ' - ' . $user->nama_user,
                'user_data' => [
                    'name' => $user->nama_user,
                    'email' => $user->email,
                    'prodi' => $user->nama_unit,
                    'fakultas' => $user->nama_parent_unit
                ]
            ];
        });

        return response()->json($formattedUsers);
    }

    public function insertIntegrateForm()
    {
        // Ambil data prodi dengan fakultasnya
        $prodis = Siakad::getProdi();
        $roles = Role::all();
        return view('users.insert-integrate', compact('roles', 'prodis'));
    }

    public function insertIntegrateStore(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|max:255|unique:users',
                'name' => 'required|string|max:255',
                // email tidak wajib, tapi kalau ada tetap harus unique
                'email' => 'nullable|string|max:255|unique:users,email',
                // 'email' => 'required|string|max:255|unique:users',
                'roles' => 'required|array',
                'prodi' => 'required|string',
                'fakultas' => 'required|string',
                'is_active' => 'boolean'
            ]);
            
            // cek kalau ada email → pakai, kalau tidak ada → isi default
            $email = $request->filled('email') ? $request->email : 'default_' . time() . '@domain.com';

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'last_name' => $request->name,
                'password' => null, // Password is NULL for integrated users
                'email' => $email,
                'jabatan' => null, // Jabatan is NULL for integrated users
                'prodi' => $request->prodi,
                'fakultas' => $request->fakultas,
                'is_active' => $request->has('is_active')
            ]);

            $user->roles()->sync($request->roles);

            ActivityLogService::log(
                'created',
                'users',
                'User terintegrasi dibuat: ' . $user->name,
                $user,
                null,
                $user->fresh()->toArray()
            );

            return redirect()->route('users.index')->with('success', 'User Berhasil Diintegrasikan');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'User Gagal Diintegrasikan: ' . $e->getMessage());
        }
    }

    public function loginAs(User $user)
    {
        // Periksa apakah pengguna saat ini memiliki role Super Admin
        if (!Auth::user()->hasActiveRole('Super Admin')) {
            return redirect()->route('home')->with('error', 'Unauthorized access');
        }
        
        // Simpan ID user admin asli di session
        session(['admin_user_id' => Auth::id()]);
        
        // Login sebagai user yang dipilih
        Auth::login($user);
        
        // Tambahkan indikator bahwa sedang dalam mode "login as"
        session(['login_as_mode' => true]);
        
        return redirect()->route('home')->with('success', 'Anda sekarang login sebagai ' . $user->name);
    }
    
    public function returnToAdmin()
    {
        if (session()->has('admin_user_id')) {
            $adminId = session('admin_user_id');
            $admin = User::find($adminId);

            if ($admin) {
                Auth::login($admin);
                // Hapus session login as
                session()->forget(['admin_user_id', 'login_as_mode']);
                return redirect()->route('users.index')->with('success', 'Kembali ke akun admin');
            }
        }

        return redirect()->route('home')->with('error', 'Tidak dapat kembali ke akun admin');
    }

    /**
     * Sync multi-prodi untuk user tertentu dari sistem mitra
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncProdi(User $user)
    {
        try {
            // Check authorization - hanya Super Admin
            if (!Auth::user()->hasRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya Super Admin yang dapat melakukan sync prodi.'
                ], 403);
            }

            // Get user ID dari sistem mitra
            $mitraUserId = Siakad::getMitraUserId($user->username);

            if (!$mitraUserId) {
                return response()->json([
                    'success' => false,
                    'message' => "User ID tidak ditemukan di sistem mitra untuk username: {$user->username}"
                ], 404);
            }

            // Get semua prodi dari sc_userrole + home base
            $mitraProdis = Siakad::getUserProdisFromMitra($mitraUserId, $user->username);

            if (empty($mitraProdis)) {
                return response()->json([
                    'success' => false,
                    'message' => "Tidak ada prodi ditemukan untuk user: {$user->username}"
                ], 404);
            }

            // Sync ke pivot table
            $prodisCount = 0;
            $newProdis = [];

            \DB::transaction(function () use ($user, $mitraProdis, &$prodisCount, &$newProdis) {
                $existingProdis = $user->prodis->pluck('kode_prodi')->toArray();
                $isFirst = $user->prodis->isEmpty();

                foreach ($mitraProdis as $index => $mitraProdi) {
                    $isNew = !in_array($mitraProdi->kode_prodi, $existingProdis);

                    // Update or create
                    \App\Models\Prodi::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'kode_prodi' => $mitraProdi->kode_prodi,
                        ],
                        [
                            'nama_prodi' => $mitraProdi->nama_prodi,
                            'kode_fakultas' => $mitraProdi->kode_fakultas,
                            'nama_fakultas' => $mitraProdi->nama_fakultas,
                            'is_default' => $isFirst && $index === 0,
                        ]
                    );

                    if ($isNew) {
                        $newProdis[] = "{$mitraProdi->kode_prodi} - {$mitraProdi->nama_prodi}";
                    }

                    $prodisCount++;
                }
            });

            // Log activity
            ActivityLogService::log(
                'synced',
                'users',
                "Multi-prodi di-sync untuk user: {$user->username}",
                $user,
                null,
                ['prodis_count' => $prodisCount, 'new_prodis' => $newProdis]
            );

            return response()->json([
                'success' => true,
                'message' => "Berhasil sync {$prodisCount} prodi untuk {$user->username}",
                'data' => [
                    'total_prodis' => $prodisCount,
                    'new_prodis' => $newProdis,
                    'prodis' => $mitraProdis
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Error syncing prodi for user {$user->username}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat sync prodi: ' . $e->getMessage()
            ], 500);
        }
    }
}
