<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Siakad;
use App\Models\User;
use App\Models\Prodi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    /**
     * Override attemptLogin untuk mengecek kredensial sebelum status aktif
     */
    protected function attemptLogin(Request $request)
    {
        try {
            $credentials = $request->only($this->username(), 'password');
            $username = $request->{$this->username()};

            // Cek user di local DB
            $localUser = User::where($this->username(), $username)->first();

            if ($localUser) {
                // Jika password null, cek ke mitra
                if ($localUser->password === null) {
                    try {
                        // Cek ke DB mitra dengan password MD5
                        $mitraUser = Siakad::checkMitraUserWithPassword($username, md5($request->password));

                        if ($mitraUser) {
                            // Jika akun tidak aktif
                            if (!$localUser->is_active) {
                                throw ValidationException::withMessages([
                                    $this->username() => ['Akun Anda tidak aktif. Silakan hubungi administrator.']
                                ]);
                            }

                            // Login jika password cocok di mitra
                            Auth::login($localUser);
                            return true;
                        }
                    } catch (\Exception $e) {
                        Log::error('Mitra login error: ' . $e->getMessage());
                    }
                } else {
                    // Cek password local seperti biasa
                    if (Auth::validate($credentials)) {
                        // Cek status aktif
                        if (!$localUser->is_active) {
                            throw ValidationException::withMessages([
                                $this->username() => ['Akun Anda tidak aktif. Silakan hubungi administrator.']
                            ]);
                        }

                        // Login dengan kredensial local
                        return Auth::attempt(
                            array_merge($credentials, ['is_active' => 1]),
                            $request->boolean('remember')
                        );
                    }
                }
            } else {
                // User tidak ada di local, cek ke mitra
                $mitraUser = Siakad::checkMitraUserWithPassword($username, md5($request->password));

                if ($mitraUser) {
                    // Buat user baru dari data mitra
                    $newUser = User::create([
                        'name' => $mitraUser->nama_user,
                        'username' => $mitraUser->username,
                        'prodi' => $mitraUser->nama_unit,
                        'fakultas' => $mitraUser->nama_parent_unit,
                        'email' => $mitraUser->email,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Login dengan user baru
                    Auth::login($newUser);
                    return true;
                }
            }

            // Jika login gagal
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.failed')]
            ]);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * LOGIN DENGAN BUTTON DI MITRA
     */
    public function loginMitra($username)
    {
        try {
            // Cek di DB SPMI dulu
            $localUser = User::where('username', $username)->first();

            if ($localUser) {
                // Jika user ditemukan di lokal, langsung login
                Auth::login($localUser, false);

                // Sync multi-prodi dari mitra
                $this->syncUserProdiFromMitra($localUser);

                return redirect()->route('home')
                    ->with('success', 'Login berhasil!');
            }

            // Jika tidak ada di lokal, cek di DB mitra
            $mitraUser = Siakad::checkMitraUser($username);

            if ($mitraUser) {
                // Buat user baru dari data mitra
                $newUser = User::create([
                    'name' => $mitraUser->nama_user,
                    'username' => $mitraUser->username,
                    'prodi' => $mitraUser->nama_unit,
                    'fakultas' => $mitraUser->nama_parent_unit,
                    'email' => $mitraUser->email,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Auth::login($newUser, false);

                // Sync multi-prodi dari mitra
                $this->syncUserProdiFromMitra($newUser);

                return redirect()->route('home')
                    ->with('success', 'Akun berhasil dibuat dan login berhasil!');
            }

            // Jika tidak ditemukan di kedua DB
            return redirect()->route('login')
                ->with('error', 'Username ' . $username . ' tidak terdaftar di sistem. Silakan hubungi administrator.');
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Terjadi kesalahan saat login. Silakan hubungi administrator.');
        }
    }

    /**
     * Override sendFailedLoginResponse untuk pesan error kredensial
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->with('error', trans('auth.failed'));
    }

    protected function redirectTo()
    {
        session()->flash('success', 'You are logged in!');
        return $this->redirectTo;
    }

    /**
     * Sync multi-prodi dari sistem mitra ke pivot table
     *
     * @param User $user
     * @return void
     */
    protected function syncUserProdiFromMitra(User $user)
    {
        try {
            // Get user ID dari sistem mitra
            $mitraUserId = Siakad::getMitraUserId($user->username);

            if (!$mitraUserId) {
                Log::warning("Cannot find mitra user ID for username: {$user->username}");
                return;
            }

            // Get semua prodi dari sc_userrole + home base
            $mitraProdis = Siakad::getUserProdisFromMitra($mitraUserId, $user->username);

            if (empty($mitraProdis)) {
                Log::info("No prodis found in mitra for user: {$user->username}");
                return;
            }

            // Sync ke pivot table
            DB::transaction(function () use ($user, $mitraProdis) {
                $existingProdis = $user->prodis->pluck('kode_prodi')->toArray();
                $isFirst = $user->prodis->isEmpty();

                foreach ($mitraProdis as $index => $mitraProdi) {
                    // Skip jika sudah ada
                    if (in_array($mitraProdi->kode_prodi, $existingProdis)) {
                        continue;
                    }

                    // Insert prodi baru
                    Prodi::create([
                        'user_id' => $user->id,
                        'kode_prodi' => $mitraProdi->kode_prodi,
                        'nama_prodi' => $mitraProdi->nama_prodi,
                        'kode_fakultas' => $mitraProdi->kode_fakultas,
                        'nama_fakultas' => $mitraProdi->nama_fakultas,
                        'is_default' => $isFirst && $index === 0, // Prodi pertama jadi default
                    ]);
                }

                // Set active_prodi session
                $defaultProdi = $user->fresh()->defaultProdi;
                if ($defaultProdi) {
                    session(['active_prodi' => $defaultProdi->kode_prodi]);
                    Log::info("Set active prodi for user {$user->username}: {$defaultProdi->kode_prodi}");
                }
            });

            Log::info("Successfully synced " . count($mitraProdis) . " prodis for user: {$user->username}");
        } catch (\Exception $e) {
            Log::error("Error syncing user prodi from mitra: " . $e->getMessage());
        }
    }

    /**
     * Override authenticated method untuk sync prodi setelah login
     *
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Sync multi-prodi dari mitra
        $this->syncUserProdiFromMitra($user);

        return redirect()->intended($this->redirectPath());
    }
}
