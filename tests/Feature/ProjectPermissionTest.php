<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Tests\TestCase;

class ProjectPermissionTest extends TestCase
{
    public function test_admin_can_view_all_projects(): void
    {
        $admin = $this->createUser('admin');
        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->get("/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_project_member_can_view_project(): void
    {
        $user = $this->createUser('auditor');
        $project = Project::factory()->create();
        $project->members()->attach($user->id, ['role_in_project' => 'auditor']);

        $response = $this->actingAs($user)->get("/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_non_member_cannot_view_project(): void
    {
        $user = $this->createUser('auditor');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get("/projects/{$project->id}");

        $response->assertStatus(403);
    }

    public function test_non_member_cannot_access_project_exports(): void
    {
        $user = $this->createUser('auditor');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get("/projects/{$project->id}/export.csv");

        $response->assertStatus(403);
    }

    public function test_non_member_cannot_access_photo_streaming(): void
    {
        $user = $this->createUser('auditor');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get("/projects/{$project->id}/photos/1/original");

        $response->assertStatus(403);
    }

    public function test_project_creator_can_update_project(): void
    {
        $user = $this->createUser('auditor');
        $project = Project::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/edit");

        $response->assertStatus(200);
    }

    public function test_reviewer_can_change_project_status_to_final(): void
    {
        $user = $this->createUser('reviewer');
        $project = Project::factory()->create(['status' => 'review']);
        $project->members()->attach($user->id, ['role_in_project' => 'reviewer']);

        $response = $this->actingAs($user)->put("/projects/{$project->id}", [
            'code' => $project->code,
            'name' => $project->name,
            'auditee_agency' => $project->auditee_agency,
            'period_start' => $project->period_start->format('Y-m-d'),
            'period_end' => $project->period_end->format('Y-m-d'),
            'status' => 'final',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'final']);
    }

    public function test_edits_blocked_when_project_final_for_non_admin(): void
    {
        $user = $this->createUser('auditor');
        $project = Project::factory()->create(['status' => 'final']);
        $project->members()->attach($user->id, ['role_in_project' => 'auditor']);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/photos", [
            'photos' => [],
        ]);

        $response->assertStatus(403);
    }
}
