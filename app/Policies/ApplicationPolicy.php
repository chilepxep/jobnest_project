<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApplicationPolicy
{
    /** Xem danh sách hồ sơ: phải là NTD thuộc công ty đã được duyệt (chặn cho_duyet / tu_choi / tam_khoa). */
    public function viewAny(User $user): bool
    {
        return $user->role === 'employer'
            && $user->employer?->company?->trang_thai === 'da_duyet';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Application $application): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Application $application): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Application $application): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Application $application): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Application $application): bool
    {
        return false;
    }

    /** Xem chi tiết / đổi trạng thái / tải CV: hồ sơ thuộc tin của công ty mình VÀ công ty vẫn đang da_duyet. */
    public function manage(User $user, Application $application): bool
    {
        $employer = $user->employer;
        $job = $application->relationLoaded('job') ? $application->job : $application->job()->first();

        return $employer !== null
            && $job !== null
            && (int) $employer->company_id === (int) $job->company_id
            && $employer->company?->trang_thai === 'da_duyet';
    }
}