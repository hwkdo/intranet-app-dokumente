<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente\Admin;

use App\Models\User;
use Flux\Flux;
use Hwkdo\IntranetAppDokumente\Data\AppSettings;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Hwkdo\IntranetAppDokumente\Support\DocumentPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Berechtigungen extends Component
{
    public string $permissionUpload = '';

    public string $permissionKenntnisnahme = '';

    public string $permissionChooseGvp = '';

    public bool $showUserModal = false;

    public string $managingRoleKey = '';

    public string $managingRoleName = '';

    /** @var array<int|string> */
    public array $selectedUserIds = [];

    /**
     * @var array<string, string>
     */
    private const MANAGEABLE_ROLE_KEYS = [
        'uploader' => 'Upload',
        'kenntnisnahme' => 'Kenntnisnahme',
        'gvp_chooser' => 'GVP-Auswahl',
    ];

    public function mount(): void
    {
        $this->authorize('manage-app-dokumente');

        $settings = IntranetAppDokumenteSettings::resolvedAppSettings();

        $this->permissionUpload = trim((string) ($settings->permissionUpload ?? '')) !== ''
            ? (string) $settings->permissionUpload
            : DocumentPermissions::defaultUpload();

        $this->permissionKenntnisnahme = trim((string) ($settings->permissionKenntnisnahme ?? '')) !== ''
            ? (string) $settings->permissionKenntnisnahme
            : DocumentPermissions::defaultKenntnisnahme();

        $this->permissionChooseGvp = trim((string) ($settings->permissionChooseGvp ?? '')) !== ''
            ? (string) $settings->permissionChooseGvp
            : DocumentPermissions::defaultChooseGvp();
    }

    public function save(): void
    {
        $this->authorize('manage-app-dokumente');

        $this->validate([
            'permissionUpload' => ['required', 'string', 'max:255'],
            'permissionKenntnisnahme' => ['required', 'string', 'max:255'],
            'permissionChooseGvp' => ['required', 'string', 'max:255'],
        ]);

        foreach ([
            $this->permissionUpload,
            $this->permissionKenntnisnahme,
            $this->permissionChooseGvp,
        ] as $permissionName) {
            Permission::findOrCreate(trim($permissionName), 'web');
        }

        $current = IntranetAppDokumenteSettings::resolvedAppSettings();

        $upload = trim($this->permissionUpload);
        $kenntnisnahme = trim($this->permissionKenntnisnahme);
        $chooseGvp = trim($this->permissionChooseGvp);

        $settings = AppSettings::from(array_merge($current->toArray(), [
            'permissionUpload' => $upload === DocumentPermissions::defaultUpload() ? null : $upload,
            'permissionKenntnisnahme' => $kenntnisnahme === DocumentPermissions::defaultKenntnisnahme() ? null : $kenntnisnahme,
            'permissionChooseGvp' => $chooseGvp === DocumentPermissions::defaultChooseGvp() ? null : $chooseGvp,
        ]));

        IntranetAppDokumenteSettings::persistAppSettings($settings);

        Flux::toast(
            heading: 'Gespeichert',
            text: 'Berechtigungen wurden aktualisiert.',
            variant: 'success',
        );
    }

    public function openUserManager(string $roleKey): void
    {
        $this->authorize('manage-app-dokumente');

        abort_unless(array_key_exists($roleKey, self::MANAGEABLE_ROLE_KEYS), 404);

        $roleName = (string) config("intranet-app-dokumente.roles.{$roleKey}.name", '');
        abort_if($roleName === '', 404);

        $role = Role::findOrCreate($roleName, 'web');
        $permissions = config("intranet-app-dokumente.roles.{$roleKey}.permissions", []);
        if (is_array($permissions) && $permissions !== []) {
            $role->syncPermissions($permissions);
        }

        $userClass = $this->userClass();

        $this->managingRoleKey = $roleKey;
        $this->managingRoleName = $roleName;
        $this->selectedUserIds = $userClass::role($roleName)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $this->showUserModal = true;
    }

    public function closeUserManager(): void
    {
        $this->showUserModal = false;
        $this->managingRoleKey = '';
        $this->managingRoleName = '';
        $this->selectedUserIds = [];
    }

    public function saveRoleUsers(): void
    {
        $this->authorize('manage-app-dokumente');

        abort_unless(
            $this->managingRoleName !== '' && array_key_exists($this->managingRoleKey, self::MANAGEABLE_ROLE_KEYS),
            404,
        );

        $role = Role::findOrCreate($this->managingRoleName, 'web');
        $userClass = $this->userClass();

        $desiredIds = collect($this->selectedUserIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $currentIds = $userClass::role($role->name)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        foreach (array_diff($currentIds, $desiredIds) as $userId) {
            $userClass::query()->find($userId)?->removeRole($role);
        }

        foreach (array_diff($desiredIds, $currentIds) as $userId) {
            $userClass::query()->find($userId)?->assignRole($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Flux::toast(
            heading: 'Benutzer aktualisiert',
            text: "Zuweisungen für „{$role->name}“ wurden gespeichert.",
            variant: 'success',
        );

        $this->closeUserManager();
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function uploadRoles(): Collection
    {
        return DocumentPermissions::roleNamesFor(trim($this->permissionUpload));
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function kenntnisnahmeRoles(): Collection
    {
        return DocumentPermissions::roleNamesFor(trim($this->permissionKenntnisnahme));
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function chooseGvpRoles(): Collection
    {
        return DocumentPermissions::roleNamesFor(trim($this->permissionChooseGvp));
    }

    /**
     * @return Collection<int, object>
     */
    #[Computed]
    public function usersForRoleSelect(): Collection
    {
        $userClass = $this->userClass();
        $selected = collect($this->selectedUserIds)
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $userClass::query()
            ->where('active', true)
            ->orderBy('nachname')
            ->orderBy('vorname')
            ->get(['id', 'vorname', 'nachname', 'email'])
            ->sortBy(function ($user) use ($selected): string {
                $selectedRank = in_array((int) $user->id, $selected, true) ? '0' : '1';
                $nachname = mb_strtolower((string) ($user->nachname ?? ''));
                $vorname = mb_strtolower((string) ($user->vorname ?? ''));

                return $selectedRank.'-'.$nachname.'-'.$vorname;
            })
            ->values()
            ->map(fn ($user): object => (object) [
                'id' => (int) $user->id,
                'label' => $this->formatUserLabel($user),
            ]);
    }

    public function managingRoleLabel(): string
    {
        return self::MANAGEABLE_ROLE_KEYS[$this->managingRoleKey] ?? $this->managingRoleName;
    }

    protected function formatUserLabel(object $user): string
    {
        $name = trim(($user->vorname ?? '').' '.($user->nachname ?? ''));
        if ($name === '') {
            $name = 'Benutzer #'.$user->id;
        }

        $email = trim((string) ($user->email ?? ''));

        return $email !== '' ? $name.' ('.$email.')' : $name;
    }

    /**
     * @return class-string
     */
    protected function userClass(): string
    {
        return config('intranet-app-dokumente.user_model', User::class);
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.admin.berechtigungen');
    }
}
