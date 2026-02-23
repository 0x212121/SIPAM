<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@audit.local')->first();
        $auditor = User::where('email', 'auditor@audit.local')->first();
        $reviewer = User::where('email', 'reviewer@audit.local')->first();
        $readonly = User::where('email', 'readonly@audit.local')->first();

        $project = Project::firstOrCreate(
            ['code' => 'AUD-2024-001'],
            [
                'name' => 'Sample Audit Project',
                'auditee_agency' => 'Ministry of Finance',
                'period_start' => '2024-01-01',
                'period_end' => '2024-12-31',
                'status' => 'draft',
                'created_by' => $admin->id,
            ]
        );

        // Add members to project
        $members = [
            ['user_id' => $auditor->id, 'role_in_project' => 'auditor'],
            ['user_id' => $reviewer->id, 'role_in_project' => 'reviewer'],
            ['user_id' => $readonly->id, 'role_in_project' => 'readonly'],
        ];

        foreach ($members as $member) {
            ProjectMember::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'user_id' => $member['user_id'],
                ],
                $member
            );
        }
    }
}
