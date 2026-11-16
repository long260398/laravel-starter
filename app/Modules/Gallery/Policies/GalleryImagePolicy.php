<?php

namespace App\Modules\Gallery\Policies;

use App\Models\User;
use App\Modules\Gallery\Models\GalleryImage;

/**
 * GalleryImage không có SoftDeletes (xem model) nên không có restore/forceDelete
 * — delete() thường đã là hành động cuối cùng khi gỡ một ảnh khỏi album.
 */
class GalleryImagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GalleryImage $image): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GalleryImage $image): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GalleryImage $image): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return true;
    }
}
