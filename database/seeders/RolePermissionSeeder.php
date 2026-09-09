<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions grouped by feature
        $permissions = [
            // User Management
            'user.view', 'user.create', 'user.edit', 'user.delete', 'user.toggle-active',

            // Store Management
            'store.view', 'store.create', 'store.edit', 'store.delete',

            // Audit Management
            'audit.view-all', 'audit.view-assigned', 'audit.create', 'audit.edit', 'audit.delete', 'audit.change-status',

            // Audit Category
            'audit-category.view', 'audit-category.create', 'audit-category.edit', 'audit-category.delete',

            // SOP/SE
            'sop.view', 'sop.create', 'sop.edit', 'sop.delete',

            // Findings
            'finding.view-all', 'finding.view-assigned', 'finding.create', 'finding.edit',
            'finding.delete', 'finding.verify', 'finding.close',

            // Action Plans
            'action-plan.view', 'action-plan.create', 'action-plan.edit',

            // Evidence
            'evidence.upload', 'evidence.verify', 'evidence.view',

            // Reports
            'report.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // --- ADMIN ---
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $managementPermissions = [
            'audit.view-all',
            'audit.create',
            'audit.edit',
            'audit.delete',
            'audit.change-status',
            'store.view',
            'store.create',
            'store.edit',
            'finding.view-all',
            'finding.edit',
            'finding.delete',
            'finding.verify',
            'finding.close',
            'action-plan.view',
            'action-plan.edit',
            'evidence.view',
            'sop.view',
            'audit-category.view',
            'report.view',
        ];

        // --- CHIEF (CHIEF AUDITOR) ---
        $chief = Role::firstOrCreate(['name' => 'chief', 'guard_name' => 'web']);
        $chief->syncPermissions($managementPermissions);

        // --- ASMEN (ASISTEN MANAGER) ---
        $asmen = Role::firstOrCreate(['name' => 'asmen', 'guard_name' => 'web']);
        $asmen->syncPermissions($managementPermissions);

        // --- AUDITOR ---
        $auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditor->syncPermissions([
            'audit.view-assigned',
            'finding.view-assigned',
            'finding.create',
            'finding.edit',
            'finding.delete',
            'finding.verify',
            'action-plan.view',
            'evidence.upload',
            'evidence.verify',
            'evidence.view',
            'sop.view',
            'audit-category.view',
            'report.view',
        ]);

        // --- COORDINATOR (KOORDINATOR AUDIT) ---
        $coordinator = Role::firstOrCreate(['name' => 'coordinator', 'guard_name' => 'web']);
        $coordinator->syncPermissions($managementPermissions);

        // Clean up or keep auditee permissions for safety
        $auditee = Role::firstOrCreate(['name' => 'auditee', 'guard_name' => 'web']);
        $auditee->syncPermissions([
            'audit.view-assigned',
            'finding.view-assigned',
            'evidence.view',
        ]);

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
