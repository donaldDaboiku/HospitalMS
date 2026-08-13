<?php

namespace App\Core\Support;

final class Roles
{
    public const SUPER_ADMIN = 'SUPER_ADMIN';

    public const HOSPITAL_ADMIN = 'HOSPITAL_ADMIN';

    public const DOCTOR = 'DOCTOR';

    public const NURSE = 'NURSE';

    public const RECEPTIONIST = 'RECEPTIONIST';

    public const TRIAGE_NURSE = 'TRIAGE_NURSE';

    public const LAB_TECHNICIAN = 'LAB_TECHNICIAN';

    public const RADIOLOGIST = 'RADIOLOGIST';

    public const PHARMACIST = 'PHARMACIST';

    public const PHARMACY_ASSISTANT = 'PHARMACY_ASSISTANT';

    public const ACCOUNTANT = 'ACCOUNTANT';

    public const CASHIER = 'CASHIER';

    public const STORE_MANAGER = 'STORE_MANAGER';

    public const PROCUREMENT_OFFICER = 'PROCUREMENT_OFFICER';

    public const HR_MANAGER = 'HR_MANAGER';

    public const THEATRE_STAFF = 'THEATRE_STAFF';

    public const AMBULANCE_STAFF = 'AMBULANCE_STAFF';

    public const PATIENT = 'PATIENT';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::HOSPITAL_ADMIN,
            self::DOCTOR,
            self::NURSE,
            self::RECEPTIONIST,
            self::TRIAGE_NURSE,
            self::LAB_TECHNICIAN,
            self::RADIOLOGIST,
            self::PHARMACIST,
            self::PHARMACY_ASSISTANT,
            self::ACCOUNTANT,
            self::CASHIER,
            self::STORE_MANAGER,
            self::PROCUREMENT_OFFICER,
            self::HR_MANAGER,
            self::THEATRE_STAFF,
            self::AMBULANCE_STAFF,
            self::PATIENT,
        ];
    }

    public static function isStaff(string $role): bool
    {
        return $role !== self::PATIENT;
    }
}
