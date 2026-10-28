<?php

namespace App\Services;

use App\Exceptions\LdapLoginException;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use LdapRecord\LdapRecordException;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use Throwable;

class LdapLoginService
{
    /**
     * Dipakai saat direktori tidak bisa dihubungi atau menolak akun layanan.
     * Dibedakan dari "username atau password salah" supaya pengguna tahu
     * masalahnya bukan pada kredensial yang ia masukkan.
     */
    private const DIRECTORY_UNAVAILABLE_MESSAGE = 'Direktori LDAP tidak dapat dihubungi. Silakan coba beberapa saat lagi atau hubungi administrator sistem.';

    private const DIRECTORY_MISCONFIGURED_MESSAGE = 'Konfigurasi direktori LDAP belum lengkap. Hubungi administrator sistem.';

    /**
     * @var array<int, string>
     */
    private array $usernameAttributes = [
        'samaccountname',
        'uid',
        'userprincipalname',
    ];

    public function attempt(string $username, #[\SensitiveParameter] string $password): User
    {
        $localUser = User::query()
            ->where('username', $username)
            ->first();

        if (! $localUser) {
            throw new LdapLoginException(401, 'Akun belum diizinkan untuk login ke aplikasi ini.');
        }

        // Akun statis diverifikasi langsung dengan kata sandi lokal supaya tetap
        // bisa masuk walau tidak terdaftar di direktori LDAP.
        if ($localUser->punyaPasswordLokal()) {
            if (! Hash::check($password, $localUser->password)) {
                throw new LdapLoginException(401, 'Username atau password salah.');
            }

            return $localUser;
        }

        $ldapUser = $this->findLdapUser($username);

        if (! $ldapUser) {
            throw new LdapLoginException(401, 'Username atau password salah.');
        }

        // attempt() mengembalikan false untuk kredensial yang salah, dan hanya
        // melempar exception bila sambungan atau akun layanan bermasalah. Jadi
        // kedua keadaan itu tidak tertukar.
        try {
            $authenticated = $ldapUser->getConnection()->auth()->attempt($ldapUser->getDn(), $password);
        } catch (LdapRecordException $exception) {
            throw $this->directoryUnavailable($exception);
        }

        if (! $authenticated) {
            throw new LdapLoginException(401, 'Username atau password salah.');
        }

        return $localUser;
    }

    private function findLdapUser(string $username): ?LdapUser
    {
        $baseDns = $this->candidateBaseDns();

        // Tanpa base DN, pencarian tidak pernah dijalankan dan hasilnya akan
        // terlihat seperti "user tidak ditemukan". Itu menyesatkan, karena
        // sebenarnya konfigurasinya yang belum diisi.
        if ($baseDns === []) {
            throw new LdapLoginException(503, self::DIRECTORY_MISCONFIGURED_MESSAGE);
        }

        try {
            foreach ($baseDns as $baseDn) {
                foreach ($this->usernameAttributes as $attribute) {
                    $user = LdapUser::query()
                        ->in($baseDn)
                        ->where($attribute, '=', $username)
                        ->first();

                    if ($user) {
                        return $user;
                    }
                }
            }
        } catch (LdapRecordException $exception) {
            throw $this->directoryUnavailable($exception);
        }

        return null;
    }

    /**
     * Penyebab teknisnya tetap masuk log supaya bisa ditelusuri administrator,
     * sementara pengguna hanya menerima pesan yang bisa ia tindaklanjuti.
     */
    private function directoryUnavailable(Throwable $exception): LdapLoginException
    {
        report($exception);

        return new LdapLoginException(503, self::DIRECTORY_UNAVAILABLE_MESSAGE);
    }

    /**
     * @return array<int, string>
     */
    private function candidateBaseDns(): array
    {
        $baseDns = Collection::make([
            trim((string) config('ldap.connections.default.base_dn', '')),
        ]);

        try {
            $rootDse = LdapUser::getRootDse();
            $baseDns->push(trim((string) $rootDse->getFirstAttribute('defaultnamingcontext', '')));
        } catch (Throwable) {
            //
        }

        return $baseDns
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
