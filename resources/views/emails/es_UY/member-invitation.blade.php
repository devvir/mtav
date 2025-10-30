{{-- Copilot - pending review --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Invitación de Miembro</title>
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
                            <h1 style="margin: 0; font-size: 28px; font-weight: bold; color: #ffffff;">Bienvenido a {{ $project->name }}</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">Hola {{ $member->firstname ?? '' }},</p>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">Te agregaron a <strong>{{ $project->name }}</strong> y te asignaron a la familia <strong>{{ $family->name }}</strong>.</p>

                            <!-- Highlight Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0;">
                                <tr>
                                    <td bgcolor="#f3f4f6" style="padding: 20px; border: 1px solid #e5e7eb;">
                                        <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #333333;">Sobre la plataforma</p>
                                        <p style="margin: 0; font-size: 14px; line-height: 22px; color: #333333;">Usamos MTAV para mantenernos en contacto, coordinar tareas y organizar el trabajo en nuestro proyecto de vivienda. Esta plataforma nos ayuda a comunicarnos y gestionar nuestra comunidad juntos.</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 24px; color: #333333;">Para acceder a la plataforma, por favor completá tu registro haciendo clic en el botón de abajo:</p>

                            <!-- Button -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 10px 0 25px 0;">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" bgcolor="#2563eb" style="border-radius: 4px;">
                                                    <a href="{{ $confirmationUrl }}" target="_blank" style="font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; padding: 14px 40px; display: inline-block;">Completar Registro</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Accessibility Link -->
                            <p style="margin: 0 0 20px 0; font-size: 13px; line-height: 20px; color: #6b7280;">
                                Si el botón no funciona, copiá y pegá este enlace en tu navegador:<br/>
                                <a href="{{ $confirmationUrl }}" style="color: #2563eb; word-break: break-all;">{{ $confirmationUrl }}</a>
                            </p>

                            <p style="margin: 0 0 10px 0; font-size: 16px; line-height: 24px; color: #333333;">Vas a poder:</p>
                            <ul style="margin: 0 0 20px 0; padding-left: 20px;">
                                <li style="margin: 0 0 8px 0; font-size: 16px; line-height: 24px; color: #333333;">Establecer tu contraseña</li>
                                <li style="margin: 0 0 8px 0; font-size: 16px; line-height: 24px; color: #333333;">Completar la información de tu perfil</li>
                                <li style="margin: 0 0 8px 0; font-size: 16px; line-height: 24px; color: #333333;">Agregar una foto de perfil (opcional)</li>
                            </ul>

                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 24px; color: #333333;">Nos vemos en la plataforma.</p>

                            <p style="margin: 0; font-size: 16px; line-height: 24px; color: #333333;">
                                {{ $project->name }}
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#f9fafb" style="padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; font-size: 13px; line-height: 20px; color: #6b7280;">Este es un mensaje automático. Por favor no respondas a este correo.</p>
                            <p style="margin: 0; font-size: 13px; line-height: 20px; color: #6b7280;">Si no esperabas esta invitación, por favor ignora este correo.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
