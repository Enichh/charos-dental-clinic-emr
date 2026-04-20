<?php

namespace CharosEMR\Application\Shared\Services;

use CharosEMR\Application\Shared\Interfaces\MailerInterface;

class VerificationCodeService
{
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRY_MINUTES = 15;

    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    public function sendVerificationCode(string $email, string $code, string $purpose = 'verification'): bool
    {
        $subject = match ($purpose) {
            'signup' => 'Verify Your Email - Charos Dental Clinic',
            'login' => 'Your Login Code - Charos Dental Clinic',
            default => 'Verification Code - Charos Dental Clinic'
        };

        $body = $this->generateEmailBody($code, $purpose);
        return $this->mailer->send($email, $subject, $body);
    }

    public function getExpiryTime(): \DateTime
    {
        return (new \DateTime())->modify('+' . self::CODE_EXPIRY_MINUTES . ' minutes');
    }

    private function generateEmailBody(string $code, string $purpose): string
    {
        $title = match ($purpose) {
            'signup' => 'Verify Your Email',
            'login' => 'Your Login Code',
            default => 'Verification Code'
        };

        $purposeText = match ($purpose) {
            'signup' => 'Thank you for signing up with Charos Dental Clinic. Please use the verification code below to complete your registration.',
            'login' => 'Please use the verification code below to log in to your account securely.',
            default => 'Please use the verification code below.'
        };

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title} - Charos Dental Clinic</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; color: #333333;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <!-- Header -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                    <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">Charos Dental Clinic</h1>
                    <p style="margin: 10px 0 0 0; color: #e0e0e0; font-size: 16px;">Your Smile, Our Priority</p>
                </div>

                <!-- Content -->
                <div style="padding: 40px 30px;">
                    <h2 style="margin: 0 0 20px 0; color: #667eea; font-size: 24px;">{$title}</h2>
                    
                    <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">{$purposeText}</p>

                    <!-- Verification Code Box -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; margin: 30px 0; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);">
                        <p style="margin: 0 0 15px 0; font-size: 14px; color: #e0e0e0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Your Verification Code</p>
                        <div style="font-size: 36px; font-weight: 700; color: #ffffff; letter-spacing: 8px; margin: 0;">
                            {$code}
                        </div>
                    </div>

                    <div style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 15px; margin: 25px 0;">
                        <p style="margin: 0; font-size: 14px; color: #856404; line-height: 1.5;">
                            <strong style="color: #856404;">⚠️ Security Notice:</strong> This code will expire in 15 minutes for your security. Do not share this code with anyone.
                        </p>
                    </div>

                    <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">
                        If you did not request this code, please ignore this email or contact our support team immediately.
                    </p>
                </div>

                <!-- Footer -->
                <div style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                    <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666;">
                        <strong>Charos Dental Clinic</strong><br>
                        Contact: support@charosdental.com<br>
                        Phone: +1 (555) 123-4567
                    </p>
                    <p style="margin: 15px 0 0 0; font-size: 12px; color: #999999;">
                        This is an automated message. Please do not reply to this email.
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
