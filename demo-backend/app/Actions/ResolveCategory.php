<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class ResolveCategory
{
    /**
     * Find or create the user's category for the given name.
     */
    public function handle(User $user, string $kind, string $name): Category
    {
        $name = Str::of($name)->squish()->toString();
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::lower($name);
        }

        return $user->categories()->firstOrCreate(
            ['kind' => $kind, 'slug' => $slug],
            ['name' => $name],
        );
    }
}
