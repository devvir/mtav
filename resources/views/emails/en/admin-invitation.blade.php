{{-- Copilot - pending review --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Invitation</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Main Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; max-width: 600px;">
                    <!-- Header -->
                    <tr>
                        <td align="center" bgcolor="#7c3aed" style="padding: 40px 30px; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 28px; font-weight: bold; color: #ffffff;">Admin Access</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">Hello {{ $admin->firstname ?? 'there' }},</p>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">
                                You've been granted administrator access to MTAV.
                            </p>

                            <!-- Highlight Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0;">
                                <tr>
                                    <td bgcolor="#f3f4f6" style="padding: 20px; border: 1px solid #e5e7eb;">
                                        <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #333333;">Your responsibilities</p>
                                        <p style="margin: 0 0 15px 0; font-size: 14px; line-height: 22px; color: #333333;">As an administrator, you'll be able to manage members, families, units, and other aspects of the housing project(s).</p>

                                        @if($projects && $projects->count() > 0)
                                        <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: bold; color: #333333;">You'll be managing:</p>
                                        <ul style="margin: 0; padding-left: 20px;">
                                            @foreach($projects as $project)
                                            <li style="margin: 0 0 6px 0; font-size: 14px; line-height: 22px; color: #333333;">{{ $project->name }}</li>
                                            @endforeach
                                        </ul>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 24px; color: #333333;">To access the platform, please complete your registration by clicking the button below:</p>

                            <!-- Button -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 10px 0 25px 0;">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" bgcolor="#7c3aed" style="border-radius: 4px;">
                                                    <a href="{{ $confirmationUrl }}" target="_blank" style="font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; padding: 14px 40px; display: inline-block;">Complete Registration</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Accessibility Link -->
                            <p style="margin: 0 0 20px 0; font-size: 13px; line-height: 20px; color: #6b7280;">
                                If the button doesn't work, copy and paste this link into your browser:<br/>
                                <a href="{{ $confirmationUrl }}" style="color: #7c3aed; word-break: break-all;">{{ $confirmationUrl }}</a>
                            </p>

                            <p style="margin: 0 0 10px 0; font-size: 16px; line-height: 24px; color: #333333;">You'll be able to:</p>
                            <ul style="margin: 0 0 20px 0; padding-left: 20px;">
                                <li style="margin: 0 0 8px 0; font-size: 16px; line-height: 24px; color: #333333;">Set your password</li>
                                <li style="margin: 0 0 8px 0; font-size: 16px; line-height: 24px; color: #333333;">Complete your contact information</li>
                                <li style="margin: 0 0 8px 0; font-size: 16px; line-height: 24px; color: #333333;">Add a profile picture (optional)</li>
                            </ul>

                            <p style="margin: 0; font-size: 16px; line-height: 24px; color: #333333;">
                                MTAV
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#f9fafb" style="padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; font-size: 13px; line-height: 20px; color: #6b7280;">This is an automated message. Please do not reply to this email.</p>
                            <p style="margin: 0; font-size: 13px; line-height: 20px; color: #6b7280;">If you didn't expect this invitation, please contact your system administrator.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
