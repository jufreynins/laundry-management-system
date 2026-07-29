<?php

namespace App\Enums;

enum AuditAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case LOGIN = 'login';
    case LOGIN_FAILED = 'login_failed';
    case PASSWORD_CHANGED = 'password_changed';
    case MFA_CHANGED = 'mfa_changed';
    case ROLE_CHANGED = 'role_changed';
    case PERMISSION_CHANGED = 'permission_changed';
    case OVERRIDE_STATUS = 'override_status';
    case PAYMENT_RECORDED = 'payment_recorded';
    case REFUND_ISSUED = 'refund_issued';
    case DISCOUNT_APPLIED = 'discount_applied';

    public function label(): string
    {
        return match($this) {
            self::CREATED => 'Created',
            self::UPDATED => 'Updated',
            self::DELETED => 'Deleted',
            self::LOGIN => 'Login',
            self::LOGIN_FAILED => 'Login Failed',
            self::PASSWORD_CHANGED => 'Password Changed',
            self::MFA_CHANGED => 'MFA Changed',
            self::ROLE_CHANGED => 'Role Changed',
            self::PERMISSION_CHANGED => 'Permission Changed',
            self::OVERRIDE_STATUS => 'Status Override',
            self::PAYMENT_RECORDED => 'Payment Recorded',
            self::REFUND_ISSUED => 'Refund Issued',
            self::DISCOUNT_APPLIED => 'Discount Applied',
        };
    }
}
