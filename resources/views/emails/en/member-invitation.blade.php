{{-- Copilot - pending review --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Member Invitation</title>
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
                            <h1 style="margin: 0; font-size: 28px; font-weight: bold; color: #ffffff;">Welcome to {{ $project->name }}</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">Hello {{ $member->firstname ?? 'there' }},</p>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">You've been added to <strong>{{ $project->name }}</strong> and assigned to the <strong>{{ $family->name }}</strong> family.</p>

                            <!-- Highlight Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0;">
                                <tr>
                                    <td bgcolor="#f3f4f6" style="padding: 20px; border: 1px solid #e5e7eb;">
                                        <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #333333;">About the platform</p>
                                        <p style="margin: 0; font-size: 14px; line-height: 22px; color: #333333;">We use MTAV to stay in contact, coordinate tasks, and organize the work in our housing project. This platform helps us communicate and manage our community together.</p>
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
                                                <td align="center" bgcolor="#2563eb" style="border-radius: 4px;">
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
                                <a href="{{ $confirmationUrl }}" style="color: #2563eb; word-break: break-all;">{{ $confirmationUrl }}</a>
                            </p>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">You'll be able to set your password and start communicating with other project members, register your unit preferences, and participate in community decisions.</p>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">See you on the platform!</p>

                            <p style="margin: 0; font-size: 16px; line-height: 24px; color: #333333;">
                                {{ $project->name }}
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#f9fafb" style="padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; font-size: 13px; line-height: 20px; color: #6b7280;">This is an automated message. Please do not reply to this email.</p>
                            <p style="margin: 0; font-size: 13px; line-height: 20px; color: #6b7280;">If you didn't expect this invitation, please ignore this email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
