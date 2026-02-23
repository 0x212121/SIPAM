<?php

namespace App\Providers;

use App\Models\PhotoEvidence;
use App\Models\Project;
use App\Policies\PhotoEvidencePolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Project::class => ProjectPolicy::class,
        PhotoEvidence::class => PhotoEvidencePolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
