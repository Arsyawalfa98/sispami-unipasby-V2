<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'prodi',
        'fakultas',
        'email',
        'email_verified_at',
        'password',
        'is_active',
        'jabatan',
        'last_name',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        if (is_null($this->last_name)) {
            return "{$this->name}";
        }

        return "{$this->name} {$this->last_name}";
    }

    /**
     * Set the user's password.
     *
     * @param string $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    /**
     * Get the roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Get all prodi that belong to the user.
     * Relasi Many-to-Many via pivot table user_prodi
     */
    public function prodis()
    {
        return $this->hasMany(Prodi::class, 'user_id');
    }

    /**
     * Get the default prodi for the user
     */
    public function defaultProdi()
    {
        return $this->hasOne(Prodi::class, 'user_id')->where('is_default', true);
    }

    /**
     * Get active prodi dari session, fallback ke default prodi
     *
     * @return Prodi|null
     */
    public function getActiveProdi()
    {
        $activeKodeProdi = session('active_prodi');

        if ($activeKodeProdi) {
            // Cari prodi yang sesuai dengan session
            $prodi = $this->prodis()->where('kode_prodi', $activeKodeProdi)->first();
            if ($prodi) {
                return $prodi;
            }
        }

        // Fallback ke default prodi
        return $this->defaultProdi;
    }

    /**
     * Get active kode prodi (untuk backward compatibility)
     * Ini menggantikan akses langsung ke Auth::user()->prodi
     *
     * @return string|null
     */
    public function getActiveKodeProdiAttribute()
    {
        $activeProdi = $this->getActiveProdi();
        return $activeProdi ? $activeProdi->kode_prodi : null;
    }

    /**
     * Get active nama prodi
     *
     * @return string|null
     */
    public function getActiveNamaProdiAttribute()
    {
        $activeProdi = $this->getActiveProdi();
        return $activeProdi ? $activeProdi->nama_prodi : null;
    }

    /**
     * Get active fakultas
     *
     * @return string|null
     */
    public function getActiveFakultasAttribute()
    {
        $activeProdi = $this->getActiveProdi();
        return $activeProdi ? $activeProdi->nama_fakultas : null;
    }

    /**
     * Check if user has access to specific prodi
     *
     * @param string $kodeProdi
     * @return bool
     */
    public function hasAccessToProdi($kodeProdi)
    {
        return $this->prodis()->where('kode_prodi', $kodeProdi)->exists();
    }

    /**
     * Get virtual roles untuk UI dropdown
     * Kombinasi role + prodi untuk multi-prodi support
     *
     * @return array
     */
    public function getVirtualRolesAttribute()
    {
        $virtualRoles = [];

        // Jika user punya prodi
        if ($this->prodis->isNotEmpty()) {
            foreach ($this->roles as $role) {
                foreach ($this->prodis as $prodi) {
                    $virtualRoles[] = [
                        'name' => $role->name,
                        'kode_prodi' => $prodi->kode_prodi,
                        'nama_prodi' => $prodi->nama_prodi,
                        'display_name' => "{$role->name} ({$prodi->kode_prodi})",
                        'is_active' => session('active_role') == $role->name &&
                                      session('active_prodi') == $prodi->kode_prodi,
                    ];
                }
            }
        } else {
            // Fallback: jika belum ada di pivot table, gunakan role biasa
            foreach ($this->roles as $role) {
                $virtualRoles[] = [
                    'name' => $role->name,
                    'kode_prodi' => null,
                    'nama_prodi' => null,
                    'display_name' => $role->name,
                    'is_active' => session('active_role') == $role->name,
                ];
            }
        }

        return $virtualRoles;
    }

    /**
     * Check if user has specific role
     */
    /**
     * Check if user has specific role
     */
    public function hasRole($role, $checkBoth = false)
    {
        // Mode 1: Periksa database terlebih dahulu (untuk validasi auditor dan kasus penting lainnya)
        if ($checkBoth) {
            // Periksa di database
            if (is_string($role) && $this->roles->contains('name', $role)) {
                return true;
            }

            // Periksa di session sebagai fallback
            if (session()->has('active_role') && session('active_role') === $role) {
                return true;
            }

            return false;
        }

        // Mode 2: Prioritaskan session (untuk switch role dan fitur UI lainnya)
        else {
            // Periksa session terlebih dahulu
            if (session()->has('active_role')) {
                return session('active_role') === $role;
            }

            // Fallback ke database
            if (is_string($role)) {
                return $this->roles->contains('name', $role);
            }

            return false;
        }
    }

    /**
     * Get the active role of the user
     *
     */
    public function getActiveRole()
    {
        return session('active_role') ?? ($this->roles->isNotEmpty() ? $this->roles->first()->name : null);
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hasPermission($permission)
    {
        // Jika ada active_role, hanya periksa permission dari role tersebut
        if (session()->has('active_role')) {
            $activeRoleName = session('active_role');

            // Cari role yang sesuai dengan nama active_role
            $activeRole = $this->roles->where('name', $activeRoleName)->first();

            // Jika role ditemukan, periksa apakah memiliki permission
            if ($activeRole) {
                return $activeRole->permissions->contains('name', $permission);
            }

            return false;
        }

        // Jika tidak ada active_role, gunakan logika lama
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has specific role in database, regardless of session
     * 
     * @param string $role
     * @return bool
     */
    public function hasRoleInDatabase($role)
    {
        return is_string($role) && $this->roles->contains('name', $role);
    }

    /**
     * Check if user has active role in session
     * 
     * @param string $role
     * @return bool
     */
    public function hasActiveRole($role)
    {
        return session()->has('active_role') && session('active_role') === $role;
    }
}
