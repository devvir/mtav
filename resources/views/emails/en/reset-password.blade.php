<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reset Password</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Main Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; max-width: 600px;">
                    <!-- Header -->
                    <tr>
                        <td align="center" bgcolor="#2563eb" style="padding: 40px 30px; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 28px; font-weight: bold; color: #ffffff;">Reset Password</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">Hello {{ $user->firstname ?? 'there' }},</p>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">You are receiving this email because we received a password reset request for your account.</p>

                            <!-- Button -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 25px 0;">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" bgcolor="#2563eb" style="border-radius: 4px;">
                                                    <a href="{{ $url }}" target="_blank" style="font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; padding: 14px 40px; display: inline-block;">Reset Password</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">This password reset link will expire in 60 minutes.</p>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">If you did not request a password reset, no further action is required.</p>

                            <!-- Link fallback -->
                            <p style="margin: 25px 0 0 0; font-size: 12px; line-height: 18px; color: #6b7280;">If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:</p>
                            <p style="margin: 5px 0 0 0; font-size: 12px; line-height: 18px; color: #6b7280; word-break: break-all;">{{ $url }}</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#f9fafb" style="padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 14px; color: #6b7280;">© {{ date('Y') }} {{ config('app.name') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
