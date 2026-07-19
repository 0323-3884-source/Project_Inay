<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $status === 'approved' ? 'Program Staff Account Approved' : 'Program Staff Registration Update' }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f7fb;color:#071127;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f7fb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #dbe5f1;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:#071127;color:#ffffff;">
                            <strong style="display:block;font-size:20px;line-height:1.25;">Project INAY</strong>
                            <span style="display:block;margin-top:6px;color:#b7f7dc;font-size:12px;font-weight:bold;text-transform:uppercase;">Program Staff Account Review</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Hello {{ $staff->full_name }},</p>

                            @if($status === 'approved')
                                <h1 style="margin:0 0 12px;font-size:24px;line-height:1.2;color:#007f5f;">Your Program Staff account has been approved.</h1>
                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">You may now log in to the Project INAY Program Staff portal using your registered email address.</p>
                            @else
                                <h1 style="margin:0 0 12px;font-size:24px;line-height:1.2;color:#be123c;">Your Program Staff registration was not approved.</h1>
                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Please contact the Project INAY administrator if you need help updating or resubmitting your information.</p>
                                @if($reason)
                                    <p style="margin:0 0 16px;padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;color:#9a3412;font-size:14px;line-height:1.5;"><strong>Admin note:</strong> {{ $reason }}</p>
                                @endif
                            @endif

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;font-size:12px;font-weight:bold;text-transform:uppercase;">Healthcare Worker ID</td>
                                    <td style="padding:10px 12px;border:1px solid #e2e8f0;font-size:14px;font-weight:bold;">{{ $staff->staff_id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;font-size:12px;font-weight:bold;text-transform:uppercase;">Role</td>
                                    <td style="padding:10px 12px;border:1px solid #e2e8f0;font-size:14px;font-weight:bold;">{{ $staff->role_label }}</td>
                                </tr>
                            </table>

                            <p style="margin:20px 0 0;color:#64748b;font-size:13px;line-height:1.5;">This message was sent by the Project INAY admin console.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
