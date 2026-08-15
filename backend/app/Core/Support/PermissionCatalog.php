<?php

namespace App\Core\Support;

final class PermissionCatalog
{
    /**
     * Permission groups seeded in Phase 1 so later modules inherit a stable RBAC surface.
     *
     * @var array<string, list<string>>
     */
    public const GROUPS = [
        'dashboard' => ['view'],
        'patient' => ['view', 'create', 'edit', 'delete'],
        'appointment' => ['view', 'create', 'edit', 'cancel'],
        'clinical' => ['view', 'create', 'edit'],
        'triage' => ['view', 'create', 'edit'],
        'lab' => ['order', 'collect', 'result', 'verify', 'approve'],
        'radiology' => ['order', 'report', 'approve'],
        'pharmacy' => ['prescribe', 'verify', 'dispense', 'return'],
        'inventory' => ['view', 'create', 'adjust', 'transfer'],
        'procurement' => ['view', 'create', 'approve'],
        'ward' => ['view', 'assign', 'transfer'],
        'admission' => ['view', 'create', 'discharge'],
        'emergency' => ['view', 'create', 'edit'],
        'billing' => ['view', 'create', 'approve', 'refund'],
        'payment' => ['view', 'create', 'reverse'],
        'insurance' => ['view', 'create', 'claim', 'approve'],
        'accounting' => ['view', 'create', 'close'],
        'theatre' => ['view', 'schedule', 'edit'],
        'bloodbank' => ['view', 'create', 'dispense'],
        'ambulance' => ['view', 'dispatch'],
        'hr' => ['view', 'create', 'edit'],
        'user' => ['view', 'create', 'edit', 'delete'],
        'role' => ['view', 'assign'],
        'reports' => ['view', 'export'],
        'settings' => ['manage'],
        'audit' => ['view'],
        'department' => ['view', 'manage'],
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (self::GROUPS as $group => $actions) {
            foreach ($actions as $action) {
                $permissions[] = $group.'.'.$action;
            }
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function forRole(string $role): array
    {
        return match ($role) {
            Roles::SUPER_ADMIN, Roles::HOSPITAL_ADMIN => self::all(),
            Roles::DOCTOR => self::merge(
                'dashboard.view',
                'patient.view', 'patient.edit',
                'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.cancel',
                'clinical.view', 'clinical.create', 'clinical.edit',
                'triage.view',
                'lab.order', 'lab.verify',
                'radiology.order',
                'pharmacy.prescribe',
                'ward.view',
                'admission.view', 'admission.create', 'admission.discharge',
                'emergency.view', 'emergency.create', 'emergency.edit',
                'theatre.view', 'theatre.schedule',
                'billing.view',
                'reports.view',
                'department.view',
            ),
            Roles::NURSE => self::merge(
                'dashboard.view',
                'patient.view',
                'appointment.view',
                'clinical.view',
                'triage.view', 'triage.create', 'triage.edit',
                'ward.view', 'ward.assign', 'ward.transfer',
                'admission.view',
                'emergency.view', 'emergency.edit',
                'department.view',
            ),
            Roles::RECEPTIONIST => self::merge(
                'dashboard.view',
                'patient.view', 'patient.create', 'patient.edit',
                'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.cancel',
                'billing.view',
                'payment.view',
                'insurance.view',
                'department.view',
            ),
            Roles::TRIAGE_NURSE => self::merge(
                'dashboard.view',
                'patient.view',
                'appointment.view',
                'triage.view', 'triage.create', 'triage.edit',
                'emergency.view', 'emergency.create', 'emergency.edit',
                'department.view',
            ),
            Roles::LAB_TECHNICIAN => self::merge(
                'dashboard.view',
                'patient.view',
                'lab.order', 'lab.collect', 'lab.result', 'lab.verify',
                'department.view',
            ),
            Roles::RADIOLOGIST => self::merge(
                'dashboard.view',
                'patient.view',
                'radiology.order', 'radiology.report', 'radiology.approve',
                'department.view',
            ),
            Roles::PHARMACIST => self::merge(
                'dashboard.view',
                'patient.view',
                'pharmacy.verify', 'pharmacy.dispense', 'pharmacy.return',
                'inventory.view',
                'department.view',
            ),
            Roles::PHARMACY_ASSISTANT => self::merge(
                'dashboard.view',
                'pharmacy.dispense',
                'inventory.view',
            ),
            Roles::ACCOUNTANT => self::merge(
                'dashboard.view',
                'billing.view', 'billing.approve', 'billing.refund',
                'payment.view', 'payment.reverse',
                'insurance.view', 'insurance.claim',
                'accounting.view', 'accounting.create', 'accounting.close',
                'reports.view', 'reports.export',
            ),
            Roles::CASHIER => self::merge(
                'dashboard.view',
                'billing.view',
                'payment.view', 'payment.create',
            ),
            Roles::STORE_MANAGER => self::merge(
                'dashboard.view',
                'inventory.view', 'inventory.create', 'inventory.adjust', 'inventory.transfer',
                'procurement.view',
                'reports.view',
            ),
            Roles::PROCUREMENT_OFFICER => self::merge(
                'dashboard.view',
                'inventory.view',
                'procurement.view', 'procurement.create',
            ),
            Roles::HR_MANAGER => self::merge(
                'dashboard.view',
                'user.view', 'user.create', 'user.edit',
                'hr.view', 'hr.create', 'hr.edit',
                'department.view',
            ),
            Roles::THEATRE_STAFF => self::merge(
                'dashboard.view',
                'patient.view',
                'theatre.view', 'theatre.schedule', 'theatre.edit',
                'ward.view',
            ),
            Roles::AMBULANCE_STAFF => self::merge(
                'dashboard.view',
                'ambulance.view', 'ambulance.dispatch',
                'emergency.view',
            ),
            Roles::PATIENT => [],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private static function merge(string ...$permissions): array
    {
        return array_values(array_unique($permissions));
    }
}
